import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, firstValueFrom } from 'rxjs';
import { StorageService } from './storage.service';
import { environment } from '../../environments/environment';
import { DEMO_INVENTORY, DEMO_USER, createInitialDemoTasks } from './demo-mock.data';
import { GpsTelemetryPoint, OfflineQueueService } from './offline-queue.service';

const isDebug = !environment.production;

export interface FsmClientConfig {
  company_name?: string;
  support_email?: string;
  support_phone?: string;
  map_style?: string;
  gps_refresh_interval: number;
  geofence_check_meters: number;
  require_proof_photo: boolean;
  require_customer_signature: boolean;
  currency: string;
  default_country_code: string;
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private storage = inject(StorageService);
  private offlineQueue = inject(OfflineQueueService);

  private baseUrl = environment.apiUrl;
  private demoUser = { ...DEMO_USER };
  private demoTasks = createInitialDemoTasks();
  private demoInventory = DEMO_INVENTORY.map(item => ({ ...item }));

  private clientConfig: FsmClientConfig = {
    company_name: 'FiberOps Global Carrier',
    support_email: 'noc@bentengsiber.com',
    support_phone: '+1-800-555-0199',
    map_style: 'openstreetmap',
    gps_refresh_interval: 15,
    geofence_check_meters: 0,
    require_proof_photo: true,
    require_customer_signature: true,
    currency: 'USD',
    default_country_code: '62',
  };

  readonly assignedCount$ = new BehaviorSubject<number>(0);

  setAssignedCount(count: number) {
    this.assignedCount$.next(Math.max(0, Number(count) || 0));
  }

  getBaseUrl(): string {
    return this.baseUrl.replace(/\/api$/, '');
  }

  getClientConfig(): FsmClientConfig {
    return this.clientConfig;
  }

  private applyClientConfig(cfg: any) {
    if (cfg && typeof cfg === 'object') {
      this.clientConfig = { ...this.clientConfig, ...cfg };
    }
  }

  private idempotentHeaders(customKey?: string): { headers: HttpHeaders } {
    const key = customKey || this.offlineQueue.generateUuid();
    return {
      headers: new HttpHeaders({ 'Idempotency-Key': key }),
    };
  }

  private noCacheHeaders(): { headers: HttpHeaders } {
    return {
      headers: new HttpHeaders({
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
      }),
    };
  }

  async isDemoMode(): Promise<boolean> {
    return (await this.storage.get('demo_mode')) === true;
  }

  // Auth
  async login(email: string, password: string) {
    const url = `${this.baseUrl}/login`;
    try {
      const response: any = await firstValueFrom(this.http.post(url, { email, password }));
      if (response?.config) {
        this.applyClientConfig(response.config);
      }
      return response;
    } catch (error) {
      if (isDebug) console.error('[ApiService] Login request failed:', error);
      throw error;
    }
  }

  async logout() {
    if (await this.isDemoMode()) {
      await this.storage.remove('demo_mode');
      return { message: 'Demo session ended' };
    }
    return firstValueFrom(this.http.post(`${this.baseUrl}/logout`, {}));
  }

  async getMe() {
    if (await this.isDemoMode()) {
      return { data: this.demoUser, config: this.clientConfig };
    }
    const res: any = await firstValueFrom(this.http.get(`${this.baseUrl}/me?_ts=${Date.now()}`, this.noCacheHeaders()));
    if (res?.config) {
      this.applyClientConfig(res.config);
    }
    return res;
  }

  async updateProfile(data: any) {
    if (await this.isDemoMode()) {
      this.demoUser = { ...this.demoUser, ...data };
      return { data: this.demoUser };
    }
    return firstValueFrom(this.http.put(`${this.baseUrl}/profile`, data));
  }

  async updatePassword(currentPassword: string, password: string, passwordConfirmation: string) {
    if (await this.isDemoMode()) {
      return { message: 'Password updated in Demo Mode.' };
    }
    return firstValueFrom(
      this.http.put(`${this.baseUrl}/profile/password`, {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      })
    );
  }

  // Tasks
  async getTasks(status?: string) {
    if (await this.isDemoMode()) {
      const list = status
        ? this.demoTasks.filter(t => t.status === status)
        : this.demoTasks.filter(t => t.status !== 'completed');
      return { data: list };
    }
    const qs = new URLSearchParams();
    if (status) qs.set('status', status);
    qs.set('_ts', String(Date.now()));
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks?${qs.toString()}`, this.noCacheHeaders()));
  }

  async getTaskSyncSignal(): Promise<{
    version: string;
    active_count: number;
    assigned_count: number;
    latest_task_id: number;
    latest_updated_at: string | null;
  }> {
    if (await this.isDemoMode()) {
      const active = this.demoTasks.filter(t => t.status !== 'completed');
      const assigned = active.filter(t => t.status === 'assigned');
      return {
        version: `demo-${active.length}-${assigned.length}`,
        active_count: active.length,
        assigned_count: assigned.length,
        latest_task_id: active[0]?.id || 0,
        latest_updated_at: null,
      };
    }
    return firstValueFrom(
      this.http.get<any>(`${this.baseUrl}/tasks/sync-signal?_ts=${Date.now()}`, this.noCacheHeaders())
    );
  }

  async getTaskById(id: number) {
    if (await this.isDemoMode()) {
      const found = this.demoTasks.find(t => t.id === Number(id)) || this.demoTasks[0];
      return { data: found };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${id}?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  private updateDemoTaskStatus(id: number, status: string, progress?: number) {
    const task = this.demoTasks.find(t => t.id === Number(id));
    if (task) {
      task.status = status;
      if (progress !== undefined) task.progress_percent = progress;
    }
    return { data: task };
  }

  async acceptTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'accepted', 10);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/accept`, {}, this.idempotentHeaders()));
  }

  async rejectTask(id: number, reason?: string) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'rejected', 0);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/reject`, { reason }, this.idempotentHeaders()));
  }

  async startTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'on-going', 25);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/start`, {}, this.idempotentHeaders()));
  }

  async completeTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'completed', 100);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/complete`, {}, this.idempotentHeaders()));
  }

  async updateProgress(id: number, note: string, percent: number) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(id));
      if (task) {
        task.progress_percent = percent;
        task.progress_updates = task.progress_updates || [];
        task.progress_updates.unshift({
          id: Date.now(),
          note,
          progress_percent: percent,
          created_at: new Date().toISOString(),
          user: this.demoUser,
        });
      }
      return { data: task };
    }
    return firstValueFrom(
      this.http.post(
        `${this.baseUrl}/tasks/${id}/progress`,
        { note, progress_percent: percent },
        this.idempotentHeaders()
      )
    );
  }

  async getHistory() {
    if (await this.isDemoMode()) {
      return { data: this.demoTasks.filter(t => t.status === 'completed') };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/history?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  // GPS Telemetry (Single & Offline Batch Queue Flush)
  async updateLocation(
    latitudeOrPayload: number | GpsTelemetryPoint,
    longitude?: number
  ) {
    const payload: any =
      typeof latitudeOrPayload === 'number'
        ? {
            latitude: latitudeOrPayload,
            longitude: longitude ?? 0,
            captured_at: new Date().toISOString(),
            client_uuid: this.offlineQueue.generateUuid(),
            source: 'foreground',
          }
        : latitudeOrPayload;

    if (await this.isDemoMode()) {
      return { status: 'ok', ...payload };
    }
    return firstValueFrom(this.http.post(`${this.baseUrl}/location`, payload));
  }

  async batchUpdateLocation(points: GpsTelemetryPoint[]) {
    if (await this.isDemoMode()) {
      return { status: 'ok', accepted: points.length, skipped: 0 };
    }
    return firstValueFrom(this.http.post(`${this.baseUrl}/location/batch`, { points }));
  }

  // Proof of Work
  async uploadProof(taskId: number, file: File, description: string, slot?: 'before' | 'after' | 'additional') {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const proof = {
        id: Date.now(),
        description,
        slot: slot || 'additional',
        resolved_slot: slot || 'additional',
        image_url: URL.createObjectURL(file),
        created_at: new Date().toISOString(),
      };
      if (task) {
        task.proof_of_work = task.proof_of_work || [];
        task.proof_of_work.push(proof);
      }
      return { data: proof };
    }
    const formData = new FormData();
    formData.append('image', file);
    formData.append('description', description);
    if (slot) {
      formData.append('slot', slot);
    }
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${taskId}/proof`, formData, this.idempotentHeaders()));
  }

  async getProof(taskId: number) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      return { data: task?.proof_of_work || [] };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/proof?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  // Inventory & Materials (BOM)
  async getInventory(): Promise<any> {
    if (await this.isDemoMode()) {
      return { data: this.demoInventory };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/inventory?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  async getTaskMaterials(taskId: number): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const materials = task?.materials || [];
      const totalCost = materials.reduce((sum: number, m: any) => sum + ((m.inventory_item?.unit_price || 0) * m.quantity), 0);
      return { data: materials, total_cost: totalCost };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/materials?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  async addTaskMaterial(
    taskId: number,
    data: { inventory_item_id: number; quantity: number; serial_number?: string; notes?: string },
    idempotencyKey?: string
  ): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const inv = this.demoInventory.find(i => i.id === Number(data.inventory_item_id));
      if (inv) inv.stock = Math.max(0, inv.stock - data.quantity);
      const mat = { id: Date.now(), task_id: taskId, ...data, inventory_item: inv };
      if (task) {
        task.materials = task.materials || [];
        task.materials.push(mat);
      }
      return { data: mat, current_stock: inv?.stock ?? 0 };
    }
    return firstValueFrom(
      this.http.post(`${this.baseUrl}/tasks/${taskId}/materials`, data, this.idempotentHeaders(idempotencyKey))
    );
  }

  async deleteTaskMaterial(taskId: number, materialId: number): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      if (task && task.materials) {
        const idx = task.materials.findIndex((m: any) => m.id === materialId);
        if (idx !== -1) {
          const removed = task.materials.splice(idx, 1)[0];
          const inv = this.demoInventory.find(i => i.id === Number(removed.inventory_item_id));
          if (inv) inv.stock += removed.quantity;
        }
      }
      return { message: 'Removed in demo mode' };
    }
    return firstValueFrom(this.http.delete(`${this.baseUrl}/tasks/${taskId}/materials/${materialId}`));
  }

  // Customer Digital Signature
  async saveTaskSignature(taskId: number, signature: string, signerName: string): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      if (task) {
        task.customer_signature = signature;
        task.signer_name = signerName;
      }
      return { message: 'Signature saved in demo mode', data: task };
    }
    return firstValueFrom(
      this.http.post(
        `${this.baseUrl}/tasks/${taskId}/signature`,
        { signature, signer_name: signerName },
        this.idempotentHeaders()
      )
    );
  }
}
