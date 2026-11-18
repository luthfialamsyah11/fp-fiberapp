import { Injectable, inject } from '@angular/core';
import { StorageService } from './storage.service';

export interface GpsTelemetryPoint {
  latitude: number;
  longitude: number;
  accuracy_m?: number | null;
  speed_mps?: number | null;
  heading_deg?: number | null;
  captured_at: string;
  task_id?: number | null;
  client_uuid: string;
  source: 'foreground' | 'background' | 'heartbeat';
}

@Injectable({ providedIn: 'root' })
export class OfflineQueueService {
  private storage = inject(StorageService);

  private readonly GPS_QUEUE_KEY = 'fsm_offline_gps_queue_v1';
  private readonly MAX_GPS_BUFFER = 1000;
  private memoryQueue: GpsTelemetryPoint[] = [];
  private loaded = false;

  async init(): Promise<void> {
    if (this.loaded) return;
    try {
      const saved = await this.storage.get(this.GPS_QUEUE_KEY);
      if (Array.isArray(saved)) {
        this.memoryQueue = saved;
      }
    } catch {
      this.memoryQueue = [];
    }
    this.loaded = true;
  }

  async enqueueGpsPoint(point: GpsTelemetryPoint): Promise<number> {
    await this.init();
    if (!this.memoryQueue.some(p => p.client_uuid === point.client_uuid)) {
      this.memoryQueue.push(point);
      if (this.memoryQueue.length > this.MAX_GPS_BUFFER) {
        this.memoryQueue = this.memoryQueue.slice(-this.MAX_GPS_BUFFER);
      }
      await this.persist();
    }
    return this.memoryQueue.length;
  }

  async peekGpsBatch(maxItems = 50): Promise<GpsTelemetryPoint[]> {
    await this.init();
    return this.memoryQueue.slice(0, maxItems);
  }

  async removeGpsPoints(uuids: string[]): Promise<void> {
    await this.init();
    const set = new Set(uuids);
    this.memoryQueue = this.memoryQueue.filter(p => !set.has(p.client_uuid));
    await this.persist();
  }

  async getPendingCount(): Promise<number> {
    await this.init();
    return this.memoryQueue.length;
  }

  generateUuid(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = (Math.random() * 16) | 0;
      const v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  private async persist(): Promise<void> {
    try {
      await this.storage.set(this.GPS_QUEUE_KEY, this.memoryQueue);
    } catch {
      // Ignore quota warnings
    }
  }
}
