import { Injectable, inject } from '@angular/core';
import { Capacitor, registerPlugin } from '@capacitor/core';
import { Geolocation, Position } from '@capacitor/geolocation';
import { App } from '@capacitor/app';
import type { BackgroundGeolocationPlugin } from '@capacitor-community/background-geolocation';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from './api.service';
import { GpsTelemetryPoint, OfflineQueueService } from './offline-queue.service';

const BackgroundGeolocation = registerPlugin<BackgroundGeolocationPlugin>('BackgroundGeolocation');

export type GpsHealthStatus =
  | 'offline'
  | 'starting'
  | 'ok'
  | 'no_permission'
  | 'gps_off'
  | 'no_network'
  | 'stale';

@Injectable({ providedIn: 'root' })
export class LocationService {
  private api = inject(ApiService);
  private offlineQueue = inject(OfflineQueueService);

  private watchId: string | null = null;
  private bgWatcherId: string | null = null;
  private heartbeatInterval: any = null;
  private isTracking = false;
  private isBackground = false;
  private isFlushing = false;
  private lastUpdateTime = 0;
  private lastSuccessfulSyncTime = 0;
  private activeTaskId: number | null = null;
  private lastKnownPosition: Position | null = null;
  private wakeLockSentinel: any = null;
  private listenersRegistered = false;

  readonly status$ = new BehaviorSubject<GpsHealthStatus>('offline');
  readonly statusMessage$ = new BehaviorSubject<string>('Offline');

  setActiveTaskId(taskId: number | null) {
    this.activeTaskId = taskId;
  }

  getLastKnownPosition(): Position | null {
    return this.lastKnownPosition;
  }

  getStatus(): GpsHealthStatus {
    return this.status$.value;
  }

  private setHealth(status: GpsHealthStatus, message: string) {
    if (this.status$.value !== status) {
      this.status$.next(status);
    }
    if (this.statusMessage$.value !== message) {
      this.statusMessage$.next(message);
    }
  }

  /**
   * Check and request foreground location permission on native devices.
   */
  async ensurePermissions(): Promise<'granted' | 'denied'> {
    if (!Capacitor.isNativePlatform()) {
      return 'granted';
    }
    try {
      const current = await Geolocation.checkPermissions();
      if (current.location === 'granted' || current.coarseLocation === 'granted') {
        return 'granted';
      }
      const requested = await Geolocation.requestPermissions();
      if (requested.location === 'granted' || requested.coarseLocation === 'granted') {
        return 'granted';
      }
      this.setHealth('no_permission', 'Location permission denied');
      return 'denied';
    } catch {
      return 'granted';
    }
  }

  /**
   * Open native Android/iOS app settings so technician can grant "Allow all the time" location & disable battery restrictions.
   */
  async openLocationSettings(): Promise<void> {
    if (!Capacitor.isNativePlatform()) return;
    try {
      await BackgroundGeolocation.openSettings();
    } catch (e) {
      console.warn('[LocationService] openSettings unavailable:', e);
    }
  }

  private registerLifecycleListeners() {
    if (this.listenersRegistered) return;
    this.listenersRegistered = true;

    // 1. Web / PWA Visibility & Reconnect Listeners
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => {
        if (this.isTracking) {
          this.flushOfflineQueue();
          this.captureAndDispatch('foreground');
        }
      });

      window.addEventListener('offline', () => {
        if (this.isTracking) {
          this.setHealth('no_network', 'No internet — buffering GPS offline');
        }
      });

      document.addEventListener('visibilitychange', () => {
        this.isBackground = document.hidden;
        if (this.isTracking) {
          if (!document.hidden) {
            this.requestWakeLock();
            this.flushOfflineQueue();
          }
          this.captureAndDispatch(document.hidden ? 'background' : 'foreground');
        }
      });
    }

    // 2. Native Android / Capacitor App State Listener
    try {
      App.addListener('appStateChange', ({ isActive }) => {
        this.isBackground = !isActive;
        if (this.isTracking) {
          this.captureAndDispatch(isActive ? 'foreground' : 'background');
          if (isActive) {
            this.flushOfflineQueue();
          }
        }
      });
    } catch {
      // Browser environment fallback
    }
  }

  async startTracking() {
    if (this.isTracking) return;
    this.isTracking = true;
    this.setHealth('starting', 'Acquiring GPS...');

    await this.offlineQueue.init();
    this.registerLifecycleListeners();
    await this.requestWakeLock();

    const perm = await this.ensurePermissions();
    if (perm === 'denied') {
      this.isTracking = false;
      return;
    }

    // 1. Flush any previously queued offline coordinates
    this.flushOfflineQueue();

    // 2. Immediate initial GPS fix
    await this.captureAndDispatch('foreground');
    if (!this.isTracking) return;

    // 3. Native Android/iOS Background Foreground-Service Watcher (keeps GPS alive when screen is locked/minimized)
    let startedNativeBg = false;
    if (Capacitor.isNativePlatform()) {
      startedNativeBg = await this.startNativeBackgroundWatcher();
    }

    // 4. Foreground Geolocation Watcher (Web/PWA or native fallback)
    if (!startedNativeBg && this.isTracking) {
      await this.startForegroundWatcher();
    }

    if (!this.isTracking) return;

    // 5. Adaptive Heartbeat & Offline Queue Flush Loop (keeps presence alive even while stationary at an ODP pole)
    this.stopHeartbeat();
    this.heartbeatInterval = setInterval(async () => {
      if (!this.isTracking) return;
      const now = Date.now();
      const heartbeatMs = Math.max(12000, this.getAdaptiveIntervalMs() * 2);

      if (now - this.lastUpdateTime >= heartbeatMs) {
        await this.captureAndDispatch(this.isBackground ? 'background' : 'heartbeat');
      } else {
        await this.flushOfflineQueue();
      }

      // Mark stale if no successful sync in > 2 minutes while online
      if (
        this.lastSuccessfulSyncTime > 0 &&
        now - this.lastSuccessfulSyncTime > 120000 &&
        this.status$.value === 'ok'
      ) {
        this.setHealth('stale', 'GPS signal stale (> 2m)');
      }
    }, 15000);
  }

  private async startNativeBackgroundWatcher(): Promise<boolean> {
    try {
      const id = await BackgroundGeolocation.addWatcher(
        {
          backgroundTitle: 'FiberOps Live Tracking Active',
          backgroundMessage: 'Sharing real-time technician GPS with NOC Dispatch.',
          requestPermissions: true,
          stale: false,
          distanceFilter: 15,
        },
        async (location, error) => {
          if (!this.isTracking) return;

          if (error) {
            if (error.code === 'NOT_AUTHORIZED') {
              this.setHealth('no_permission', 'Location permission needed (Allow all the time)');
            } else {
              this.setHealth('gps_off', 'GPS unavailable — enable location');
            }
            return;
          }

          if (location) {
            const syntheticPos: Position = {
              timestamp: location.time || Date.now(),
              coords: {
                latitude: location.latitude,
                longitude: location.longitude,
                accuracy: location.accuracy ?? 20,
                altitude: location.altitude ?? null,
                altitudeAccuracy: location.altitudeAccuracy ?? null,
                heading: location.bearing ?? null,
                speed: location.speed ?? null,
                magneticHeading: null,
                trueHeading: null,
                headingAccuracy: null,
                course: location.bearing ?? null,
              },
            };

            this.lastKnownPosition = syntheticPos;
            const now = Date.now();
            const minIntervalMs = this.getAdaptiveIntervalMs();
            if (now - this.lastUpdateTime < minIntervalMs) return;
            this.lastUpdateTime = now;

            await this.dispatchPosition(syntheticPos, this.isBackground ? 'background' : 'foreground');
          }
        }
      );

      if (!this.isTracking) {
        await BackgroundGeolocation.removeWatcher({ id }).catch(() => {});
        return false;
      }

      this.bgWatcherId = id;
      return true;
    } catch (e) {
      console.warn('[LocationService] BackgroundGeolocation unavailable, falling back to foreground watch:', e);
      return false;
    }
  }

  private async startForegroundWatcher(): Promise<void> {
    try {
      const id = await Geolocation.watchPosition(
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 3000 },
        async (position, err) => {
          if (!this.isTracking) return;
          if (err) {
            const code = (err as any)?.code;
            const msg = String((err as any)?.message || '').toLowerCase();
            if (code === 1 || msg.includes('denied') || msg.includes('permission')) {
              this.setHealth('no_permission', 'Location permission needed');
            } else {
              this.setHealth('gps_off', 'GPS signal unavailable');
            }
            return;
          }
          if (position?.coords) {
            this.lastKnownPosition = position;
            const now = Date.now();
            const minIntervalMs = this.getAdaptiveIntervalMs();
            if (now - this.lastUpdateTime < minIntervalMs) return;
            this.lastUpdateTime = now;

            await this.dispatchPosition(position, this.isBackground ? 'background' : 'foreground');
          }
        }
      );

      if (!this.isTracking) {
        await Geolocation.clearWatch({ id }).catch(() => {});
        return;
      }
      this.watchId = id;
    } catch (e: any) {
      console.error('[LocationService] Failed to start Geolocation watch:', e);
      this.setHealth('gps_off', 'GPS unavailable');
    }
  }

  private getAdaptiveIntervalMs(): number {
    const cfgSeconds = this.api.getClientConfig().gps_refresh_interval || 15;
    // Faster telemetry when executing an active task (minimum 5s), standard interval when idle
    const baseSeconds = this.activeTaskId ? Math.min(cfgSeconds, 8) : cfgSeconds;
    return Math.max(5000, baseSeconds * 1000);
  }

  private async captureAndDispatch(source: 'foreground' | 'background' | 'heartbeat') {
    try {
      const pos = await this.getCurrentPosition();
      if (!this.isTracking || !pos?.coords) return;
      this.lastKnownPosition = pos;
      this.lastUpdateTime = Date.now();
      await this.dispatchPosition(pos, source);
    } catch (err: any) {
      if (err?.status === 401 || err?.status === 403) {
        this.stopTracking();
        return;
      }
      const code = err?.code;
      const msg = String(err?.message || '').toLowerCase();
      if (code === 1 || msg.includes('denied') || msg.includes('permission')) {
        this.setHealth('no_permission', 'Location permission needed');
      } else if (this.status$.value !== 'ok') {
        this.setHealth('gps_off', 'GPS off or weak signal');
      }
    }
  }

  private async dispatchPosition(position: Position, source: 'foreground' | 'background' | 'heartbeat') {
    const { latitude, longitude, accuracy, speed, heading } = position.coords;

    // Client-side sanity guard against Null Island (0,0) or > 1000m wildly inaccurate fixes
    if (Math.abs(latitude) < 0.0001 && Math.abs(longitude) < 0.0001) return;
    if (accuracy && accuracy > 1000) return;

    const point: GpsTelemetryPoint = {
      latitude,
      longitude,
      accuracy_m: accuracy ? Math.round(accuracy * 10) / 10 : null,
      speed_mps: speed !== null && speed !== undefined && speed >= 0 ? Math.round(speed * 100) / 100 : null,
      heading_deg: heading !== null && heading !== undefined && heading >= 0 ? Math.round(heading) : null,
      captured_at: new Date(position.timestamp || Date.now()).toISOString(),
      task_id: this.activeTaskId,
      client_uuid: this.offlineQueue.generateUuid(),
      source,
    };

    try {
      await this.api.updateLocation(point);
      this.lastSuccessfulSyncTime = Date.now();
      this.setHealth('ok', accuracy && accuracy > 250 ? `Online (Low GPS ±${Math.round(accuracy)}m)` : 'Online • GPS Live');
      await this.flushOfflineQueue();
    } catch (apiErr: any) {
      if (apiErr?.status === 401 || apiErr?.status === 403) {
        this.stopTracking();
        return;
      }
      // Network drop / blank spot: persist to offline queue for chronological batch replay
      if (!apiErr?.status || apiErr.status === 0 || apiErr.status >= 500) {
        this.setHealth('no_network', 'No network — GPS buffered offline');
        await this.offlineQueue.enqueueGpsPoint(point);
      }
    }
  }

  async flushOfflineQueue() {
    if (this.isFlushing) return;
    const pendingCount = await this.offlineQueue.getPendingCount();
    if (pendingCount === 0) return;

    this.isFlushing = true;
    try {
      const batch = await this.offlineQueue.peekGpsBatch(50);
      if (batch.length > 0) {
        await this.api.batchUpdateLocation(batch);
        await this.offlineQueue.removeGpsPoints(batch.map(p => p.client_uuid));
      }
    } catch {
      // Retain in storage for next reconnect cycle
    } finally {
      this.isFlushing = false;
    }
  }

  async getCurrentPosition(): Promise<Position> {
    const pos = await Geolocation.getCurrentPosition({
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 5000,
    });
    if (pos?.coords) {
      this.lastKnownPosition = pos;
    }
    return pos;
  }

  async stopTracking() {
    this.isTracking = false;
    if (this.bgWatcherId !== null) {
      const idToRemove = this.bgWatcherId;
      this.bgWatcherId = null;
      try {
        await BackgroundGeolocation.removeWatcher({ id: idToRemove });
      } catch (e) {
        console.warn('[LocationService] removeWatcher warning:', e);
      }
    }
    if (this.watchId !== null) {
      try {
        await Geolocation.clearWatch({ id: this.watchId });
      } catch (e) {
        console.warn('[LocationService] clearWatch warning:', e);
      }
      this.watchId = null;
    }
    this.stopHeartbeat();
    this.releaseWakeLock();
    this.setHealth('offline', 'Offline');
  }

  private stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }

  private async requestWakeLock() {
    try {
      if (typeof navigator !== 'undefined' && 'wakeLock' in navigator && !this.wakeLockSentinel) {
        this.wakeLockSentinel = await (navigator as any).wakeLock.request('screen');
      }
    } catch {
      // WakeLock not supported or declined
    }
  }

  private releaseWakeLock() {
    try {
      if (this.wakeLockSentinel) {
        this.wakeLockSentinel.release().catch(() => {});
        this.wakeLockSentinel = null;
      }
    } catch {
      this.wakeLockSentinel = null;
    }
  }
}
