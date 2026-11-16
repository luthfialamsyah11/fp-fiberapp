import { Injectable, inject } from '@angular/core';
import { Geolocation } from '@capacitor/geolocation';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class LocationService {
  private api = inject(ApiService);

  private watchId: string | null = null;
  private heartbeatInterval: any = null;
  private isTracking = false;
  private lastUpdateTime = 0;

  async startTracking() {
    if (this.isTracking) return;
    this.isTracking = true;

    // 1. Immediate GPS sync on start
    try {
      const initialPos = await this.getCurrentPosition();
      if (!this.isTracking) return;
      if (initialPos?.coords) {
        await this.api.updateLocation(
          initialPos.coords.latitude,
          initialPos.coords.longitude
        );
        this.lastUpdateTime = Date.now();
      }
    } catch (err: any) {
      if (err?.status === 401 || err?.status === 403) {
        this.stopTracking();
        return;
      }
      console.warn('[LocationService] Initial GPS sync warning:', err);
    }

    if (!this.isTracking) return;

    // 2. Hardware Geolocation Watcher
    try {
      const id = await Geolocation.watchPosition(
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 },
        async (position, err) => {
          if (!this.isTracking) return;
          if (err) {
            console.error('[LocationService] GPS watch error:', err);
            return;
          }
          if (position?.coords) {
            const now = Date.now();
            // Throttle to avoid flooding network (minimum 5s between motion pings)
            if (now - this.lastUpdateTime < 5000) return;
            this.lastUpdateTime = now;

            try {
              await this.api.updateLocation(
                position.coords.latitude,
                position.coords.longitude
              );
            } catch (apiErr: any) {
              console.error('[LocationService] Failed to send location:', apiErr);
              if (apiErr?.status === 401 || apiErr?.status === 403) {
                console.warn('[LocationService] Authorization expired, stopping tracking.');
                this.stopTracking();
              }
            }
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

    // 3. Stationary Heartbeat (every 15s) to keep online presence alive in Admin Dispatcher
    this.stopHeartbeat();
    this.heartbeatInterval = setInterval(async () => {
      if (!this.isTracking) return;
      const now = Date.now();
      // If no motion update happened in last 12s, send a heartbeat ping
      if (now - this.lastUpdateTime >= 12000) {
        try {
          const pos = await this.getCurrentPosition();
          if (!this.isTracking) return;
          if (pos?.coords) {
            this.lastUpdateTime = now;
            await this.api.updateLocation(pos.coords.latitude, pos.coords.longitude);
          }
        } catch (e: any) {
          if (e?.status === 401 || e?.status === 403) {
            console.warn('[LocationService] Authorization expired during heartbeat, stopping tracking.');
            this.stopTracking();
          }
        }
      }
    }, 15000);
  }

  async getCurrentPosition() {
    return await Geolocation.getCurrentPosition();
  }

  async stopTracking() {
    if (this.watchId !== null) {
      try {
        await Geolocation.clearWatch({ id: this.watchId });
      } catch (e) {
        console.warn('[LocationService] clearWatch warning:', e);
      }
      this.watchId = null;
    }
    this.stopHeartbeat();
    this.isTracking = false;
  }

  private stopHeartbeat() {
    if (this.heartbeatInterval) {
      clearInterval(this.heartbeatInterval);
      this.heartbeatInterval = null;
    }
  }
}
