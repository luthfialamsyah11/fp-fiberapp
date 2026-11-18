import { Injectable, inject } from '@angular/core';
import { Geolocation, Position } from '@capacitor/geolocation';
import { App } from '@capacitor/app';
import { ApiService } from './api.service';
import { GpsTelemetryPoint, OfflineQueueService } from './offline-queue.service';

@Injectable({ providedIn: 'root' })
export class LocationService {
  private api = inject(ApiService);
  private offlineQueue = inject(OfflineQueueService);

  private watchId: string | null = null;
  private heartbeatInterval: any = null;
  private isTracking = false;
  private isBackground = false;
  private isFlushing = false;
  private lastUpdateTime = 0;
  private activeTaskId: number | null = null;
  private lastKnownPosition: Position | null = null;
  private wakeLockSentinel: any = null;
  private listenersRegistered = false;

  setActiveTaskId(taskId: number | null) {
    this.activeTaskId = taskId;
  }

  getLastKnownPosition(): Position | null {
    return this.lastKnownPosition;
  }

  private registerLifecycleListeners() {
    if (this.listenersRegistered) return;
    this.listenersRegistered = true;

    // 1. Web / PWA Visibility & Reconnect Listeners
    if (typeof window !== 'undefined') {
      window.addEventListener('online', () => {
        if (this.isTracking) {
          this.flushOfflineQueue();
        }
      });

      document.addEventListener('visibilitychange', () => {
        this.isBackground = document.hidden;
        if (this.isTracking) {
          if (!document.hidden) {
            this.requestWakeLock();
            this.flushOfflineQueue();
          }
          // Capture immediate transition coordinate fix
          this.captureAndDispatch(document.hidden ? 'background' : 'foreground');
        }
      });
    }

    // 2. Native Android / Capacitor App State Listener for Background GPS Continuity
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
    await this.offlineQueue.init();
    this.registerLifecycleListeners();
    await this.requestWakeLock();

    // 1. Flush any previously queued offline coordinates
    this.flushOfflineQueue();

    // 2. Immediate initial GPS fix
    await this.captureAndDispatch('foreground');
    if (!this.isTracking) return;

    // 3. Hardware Geolocation Watcher (Foreground & Native Background Service)
    try {
      const id = await Geolocation.watchPosition(
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 3000 },
        async (position, err) => {
          if (!this.isTracking) return;
          if (err) {
            console.warn('[LocationService] GPS watch warning:', err);
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
    } catch (e) {
      console.error('[LocationService] Failed to start Geolocation watch:', e);
    }

    if (!this.isTracking) return;

    // 4. Adaptive Heartbeat & Offline Queue Flush Loop
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
    }, 15000);
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
      }
    }
  }

  private async dispatchPosition(position: Position, source: 'foreground' | 'background' | 'heartbeat') {
    const { latitude, longitude, accuracy, speed, heading } = position.coords;

    // Client-side sanity guard against Null Island (0,0) or > 250m degraded accuracy
    if (Math.abs(latitude) < 0.0001 && Math.abs(longitude) < 0.0001) return;
    if (accuracy && accuracy > 250) return;

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
      await this.flushOfflineQueue();
    } catch (apiErr: any) {
      if (apiErr?.status === 401 || apiErr?.status === 403) {
        this.stopTracking();
        return;
      }
      // Network drop / blank spot: persist to offline queue for batch replay
      if (!apiErr?.status || apiErr.status === 0 || apiErr.status >= 500) {
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
