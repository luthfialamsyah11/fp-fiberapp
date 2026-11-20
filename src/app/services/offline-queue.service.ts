import { Injectable, inject } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
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

export type OfflineMutationType =
  | 'accept'
  | 'reject'
  | 'start'
  | 'progress'
  | 'proof'
  | 'material_add'
  | 'signature'
  | 'complete';

export interface OfflineWorkOrderMutation {
  id: string;
  idempotency_key: string;
  task_id: number;
  type: OfflineMutationType;
  payload: any;
  created_at: string;
  attempts: number;
  last_error?: string;
}

@Injectable({ providedIn: 'root' })
export class OfflineQueueService {
  private storage = inject(StorageService);

  private readonly GPS_QUEUE_KEY = 'fsm_offline_gps_queue_v1';
  private readonly MUTATION_QUEUE_KEY = 'fsm_offline_mutations_v1';
  private readonly CACHE_TASKS_KEY = 'fsm_cached_tasks_v1';
  private readonly CACHE_INVENTORY_KEY = 'fsm_cached_inventory_v1';
  private readonly MAX_GPS_BUFFER = 1000;
  private readonly MAX_MUTATIONS = 200;

  private memoryQueue: GpsTelemetryPoint[] = [];
  private mutationQueue: OfflineWorkOrderMutation[] = [];
  private loaded = false;

  readonly pendingMutationsCount$ = new BehaviorSubject<number>(0);

  async init(): Promise<void> {
    if (this.loaded) return;
    try {
      const [savedGps, savedMut] = await Promise.all([
        this.storage.get(this.GPS_QUEUE_KEY),
        this.storage.get(this.MUTATION_QUEUE_KEY),
      ]);
      if (Array.isArray(savedGps)) {
        this.memoryQueue = savedGps;
      }
      if (Array.isArray(savedMut)) {
        this.mutationQueue = savedMut;
      }
    } catch {
      this.memoryQueue = [];
      this.mutationQueue = [];
    }
    this.loaded = true;
    this.pendingMutationsCount$.next(this.mutationQueue.length);
  }

  // ── GPS Breadcrumb Queue ──────────────────────────────────────────────────

  async enqueueGpsPoint(point: GpsTelemetryPoint): Promise<number> {
    await this.init();
    if (!this.memoryQueue.some(p => p.client_uuid === point.client_uuid)) {
      this.memoryQueue.push(point);
      if (this.memoryQueue.length > this.MAX_GPS_BUFFER) {
        this.memoryQueue = this.memoryQueue.slice(-this.MAX_GPS_BUFFER);
      }
      await this.persistGps();
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
    await this.persistGps();
  }

  async getPendingCount(): Promise<number> {
    await this.init();
    return this.memoryQueue.length;
  }

  // ── Offline-First Work Order Mutation Queue (FIFO + Idempotency-Key) ─────

  async enqueueMutation(
    taskId: number,
    type: OfflineMutationType,
    payload: any,
    idempotencyKey?: string
  ): Promise<OfflineWorkOrderMutation> {
    await this.init();
    const key = idempotencyKey || this.generateUuid();
    const item: OfflineWorkOrderMutation = {
      id: this.generateUuid(),
      idempotency_key: key,
      task_id: taskId,
      type,
      payload,
      created_at: new Date().toISOString(),
      attempts: 0,
    };
    this.mutationQueue.push(item);
    if (this.mutationQueue.length > this.MAX_MUTATIONS) {
      this.mutationQueue = this.mutationQueue.slice(-this.MAX_MUTATIONS);
    }
    await this.persistMutations();
    return item;
  }

  async getPendingMutations(): Promise<OfflineWorkOrderMutation[]> {
    await this.init();
    return [...this.mutationQueue];
  }

  async removeMutation(id: string): Promise<void> {
    await this.init();
    this.mutationQueue = this.mutationQueue.filter(m => m.id !== id);
    await this.persistMutations();
  }

  async markMutationAttempt(id: string, errorMessage?: string): Promise<void> {
    await this.init();
    const target = this.mutationQueue.find(m => m.id === id);
    if (target) {
      target.attempts += 1;
      target.last_error = errorMessage;
      await this.persistMutations();
    }
  }

  // ── Offline Local Read Caches (Tasks & Warehouse Inventory) ───────────────

  async cacheTasks(tasks: any[]): Promise<void> {
    try {
      await this.storage.set(this.CACHE_TASKS_KEY, tasks);
    } catch {}
  }

  async getCachedTasks(): Promise<any[]> {
    try {
      const data = await this.storage.get(this.CACHE_TASKS_KEY);
      return Array.isArray(data) ? data : [];
    } catch {
      return [];
    }
  }

  async cacheTaskDetail(taskId: number, task: any): Promise<void> {
    try {
      await this.storage.set(`fsm_cached_task_${taskId}`, task);
    } catch {}
  }

  async getCachedTaskDetail(taskId: number): Promise<any | null> {
    try {
      return (await this.storage.get(`fsm_cached_task_${taskId}`)) || null;
    } catch {
      return null;
    }
  }

  async cacheInventory(items: any[]): Promise<void> {
    try {
      await this.storage.set(this.CACHE_INVENTORY_KEY, items);
    } catch {}
  }

  async getCachedInventory(): Promise<any[]> {
    try {
      const data = await this.storage.get(this.CACHE_INVENTORY_KEY);
      return Array.isArray(data) ? data : [];
    } catch {
      return [];
    }
  }

  // ── Client-Side Canvas Image Compression for Offline Proof Storage ────────

  async compressImageToDataUrl(file: File, maxWidth = 1280, quality = 0.78): Promise<string> {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onerror = () => reject(new Error('Failed to read image file'));
      reader.onload = () => {
        const rawDataUrl = String(reader.result || '');
        const img = new Image();
        img.onerror = () => resolve(rawDataUrl);
        img.onload = () => {
          try {
            const scale = img.width > maxWidth ? maxWidth / img.width : 1;
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(img.width * scale));
            canvas.height = Math.max(1, Math.round(img.height * scale));
            const ctx = canvas.getContext('2d');
            if (!ctx) {
              resolve(rawDataUrl);
              return;
            }
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            resolve(canvas.toDataURL('image/jpeg', quality));
          } catch {
            resolve(rawDataUrl);
          }
        };
        img.src = rawDataUrl;
      };
      reader.readAsDataURL(file);
    });
  }

  dataUrlToFile(dataUrl: string, fileName = 'offline_proof.jpg'): File {
    const parts = dataUrl.split(',');
    const mimeMatch = parts[0].match(/:(.*?);/);
    const mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
    const bstr = atob(parts[1] || '');
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], fileName, { type: mime });
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

  private async persistGps(): Promise<void> {
    try {
      await this.storage.set(this.GPS_QUEUE_KEY, this.memoryQueue);
    } catch {}
  }

  private async persistMutations(): Promise<void> {
    this.pendingMutationsCount$.next(this.mutationQueue.length);
    try {
      await this.storage.set(this.MUTATION_QUEUE_KEY, this.mutationQueue);
    } catch {}
  }
}
