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

  // Push Notification Device Tokens (FCM)
  async registerDeviceToken(token: string, platform = 'android', deviceName?: string) {
    if (await this.isDemoMode()) return { message: 'Demo mode' };
    return firstValueFrom(
      this.http.post(`${this.baseUrl}/devices/token`, {
        token,
        platform,
        device_name: deviceName || navigator.userAgent.slice(0, 100),
      })
    );
  }

  async unregisterDeviceToken(token: string) {
    if (await this.isDemoMode()) return { message: 'Demo mode' };
    return firstValueFrom(
      this.http.delete(`${this.baseUrl}/devices/token`, { body: { token } })
    );
  }

  private isOfflineNetworkError(err: any): boolean {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) return true;
    const status = err?.status;
    return status === 0 || status === undefined;
  }

  // Tasks (with Offline Read Cache Fallback)
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
    try {
      const res: any = await firstValueFrom(
        this.http.get(`${this.baseUrl}/tasks?${qs.toString()}`, this.noCacheHeaders())
      );
      if (!status && Array.isArray(res?.data)) {
        this.offlineQueue.cacheTasks(res.data);
      }
      return res;
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        const cached = await this.offlineQueue.getCachedTasks();
        if (cached.length > 0) {
          return { data: status ? cached.filter(t => t.status === status) : cached, _offline_cached: true };
        }
      }
      throw err;
    }
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
    try {
      const res: any = await firstValueFrom(
        this.http.get(`${this.baseUrl}/tasks/${id}?_ts=${Date.now()}`, this.noCacheHeaders())
      );
      if (res?.data) {
        this.offlineQueue.cacheTaskDetail(id, res.data);
      }
      return res;
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        const cached = await this.offlineQueue.getCachedTaskDetail(id);
        if (cached) {
          return { data: cached, _offline_cached: true };
        }
      }
      throw err;
    }
  }

  private updateDemoTaskStatus(id: number, status: string, progress?: number) {
    const task = this.demoTasks.find(t => t.id === Number(id));
    if (task) {
      task.status = status;
      if (progress !== undefined) task.progress_percent = progress;
    }
    return { data: task };
  }

  async acceptTask(id: number, idempotencyKey?: string) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'accepted', 10);
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/accept`, {}, this.idempotentHeaders(key)));
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(id, 'accept', {}, key);
        return { _queued: true, message: 'Saved offline — will sync automatically when signal returns.' };
      }
      throw err;
    }
  }

  async rejectTask(id: number, reason?: string, idempotencyKey?: string) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'rejected', 0);
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/reject`, { reason }, this.idempotentHeaders(key)));
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(id, 'reject', { reason }, key);
        return { _queued: true, message: 'Decline saved offline — will sync automatically.' };
      }
      throw err;
    }
  }

  async startTask(id: number, idempotencyKey?: string) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'on-going', 25);
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/start`, {}, this.idempotentHeaders(key)));
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(id, 'start', {}, key);
        return { _queued: true, message: 'Start work saved offline — will sync automatically.' };
      }
      throw err;
    }
  }

  async completeTask(id: number, idempotencyKey?: string) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'completed', 100);
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/complete`, {}, this.idempotentHeaders(key)));
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(id, 'complete', {}, key);
        return { _queued: true, message: 'Completion queued offline — will seal BAST once online.' };
      }
      throw err;
    }
  }

  async updateProgress(id: number, note: string, percent: number, idempotencyKey?: string) {
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
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(
        this.http.post(
          `${this.baseUrl}/tasks/${id}/progress`,
          { note, progress_percent: percent },
          this.idempotentHeaders(key)
        )
      );
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(id, 'progress', { note, progress_percent: percent }, key);
        return { _queued: true, message: 'Progress saved offline — will sync automatically.' };
      }
      throw err;
    }
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

  // Proof of Work (with Offline Compressed Queue Fallback)
  async uploadProof(
    taskId: number,
    file: File,
    description: string,
    slot?: 'before' | 'after' | 'additional',
    opticalPowerDbm?: number | null,
    idempotencyKey?: string
  ) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const proof = {
        id: Date.now(),
        description,
        slot: slot || 'additional',
        resolved_slot: slot || 'additional',
        optical_power_dbm: opticalPowerDbm ?? null,
        image_url: URL.createObjectURL(file),
        created_at: new Date().toISOString(),
      };
      if (task) {
        task.proof_of_work = task.proof_of_work || [];
        task.proof_of_work.push(proof);
      }
      return { data: proof };
    }
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    const formData = new FormData();
    formData.append('image', file);
    formData.append('description', description);
    if (slot) {
      formData.append('slot', slot);
    }
    if (opticalPowerDbm !== null && opticalPowerDbm !== undefined && !Number.isNaN(Number(opticalPowerDbm))) {
      formData.append('optical_power_dbm', String(opticalPowerDbm));
    }
    try {
      return await firstValueFrom(
        this.http.post(`${this.baseUrl}/tasks/${taskId}/proof`, formData, this.idempotentHeaders(key))
      );
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        const dataUrl = await this.offlineQueue.compressImageToDataUrl(file);
        await this.offlineQueue.enqueueMutation(
          taskId,
          'proof',
          {
            dataUrl,
            fileName: file.name || 'offline_proof.jpg',
            description,
            slot: slot || 'additional',
            optical_power_dbm: opticalPowerDbm ?? null,
          },
          key
        );
        return { _queued: true, message: 'Proof photo compressed & saved offline — will upload once signal returns.' };
      }
      throw err;
    }
  }

  async getProof(taskId: number) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      return { data: task?.proof_of_work || [] };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/proof?_ts=${Date.now()}`, this.noCacheHeaders()));
  }

  // Inventory & Materials (BOM — with Offline Read Cache)
  async getInventory(): Promise<any> {
    if (await this.isDemoMode()) {
      return { data: this.demoInventory };
    }
    try {
      const res: any = await firstValueFrom(
        this.http.get(`${this.baseUrl}/inventory?_ts=${Date.now()}`, this.noCacheHeaders())
      );
      if (Array.isArray(res?.data)) {
        this.offlineQueue.cacheInventory(res.data);
      }
      return res;
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        const cached = await this.offlineQueue.getCachedInventory();
        if (cached.length > 0) {
          return { data: cached, _offline_cached: true };
        }
      }
      throw err;
    }
  }

  // Fiber GIS Assets (OLT / ODC / ODP Nodes for Field Routing)
  async getNearbyFiberNodes(lat?: number | null, lng?: number | null): Promise<any[]> {
    if (await this.isDemoMode()) {
      return [];
    }
    try {
      let params = '';
      if (lat !== null && lat !== undefined && lng !== null && lng !== undefined) {
        params = `?lat=${lat}&lng=${lng}`;
      }
      const res: any = await firstValueFrom(
        this.http.get(`${this.baseUrl}/fiber-nodes/nearby${params}`, this.noCacheHeaders())
      );
      return Array.isArray(res?.data) ? res.data : (Array.isArray(res) ? res : []);
    } catch (err) {
      console.warn('[ApiService] Could not load nearby fiber nodes:', err);
      return [];
    }
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
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(
        this.http.post(`${this.baseUrl}/tasks/${taskId}/materials`, data, this.idempotentHeaders(key))
      );
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(taskId, 'material_add', data, key);
        return { _queued: true, message: 'Material consumption saved offline — will deduct stock once online.' };
      }
      throw err;
    }
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

  // Dynamic QRIS On-Site Billing Collection
  async generateTaskQris(taskId: number, amount?: number, expiryMinutes = 15): Promise<any> {
    if (await this.isDemoMode()) {
      const task: any = this.demoTasks.find(t => t.id === Number(taskId));
      const billAmt = amount && amount >= 1000 ? amount : (task?.billing_amount || 175000);
      const ref = `QRIS-DEMO-${taskId}-${Date.now().toString().slice(-5)}`;
      const qrPayload = `00020101021226680014ID.CO.QRIS.WWW52045812530336054${String(Math.round(billAmt)).padStart(6, '0')}5802ID5913BENTENG_SIBER6007JAKARTA6304DEMO`;
      const tx = {
        reference_no: ref,
        amount: billAmt,
        status: 'pending',
        qr_payload: qrPayload,
        qr_image_url: `https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=${encodeURIComponent(qrPayload)}`,
        expires_at: new Date(Date.now() + expiryMinutes * 60000).toISOString(),
        paid_at: null as string | null,
      };
      if (task) {
        task.billing_amount = billAmt;
        task.payment_status = 'pending';
        task._demo_qris = tx;
        task._demo_qris_created = Date.now();
      }
      return { message: 'Dynamic QRIS generated (Demo Sandbox).', transaction: tx };
    }

    const body: any = { expiry_minutes: expiryMinutes };
    if (amount && amount >= 1000) body.amount = amount;
    return firstValueFrom(
      this.http.post(`${this.baseUrl}/tasks/${taskId}/qris`, body, this.idempotentHeaders())
    );
  }

  async getTaskPaymentStatus(taskId: number): Promise<any> {
    if (await this.isDemoMode()) {
      const task: any = this.demoTasks.find(t => t.id === Number(taskId));
      // In Demo Mode, simulate instant QRIS bank settlement 6 seconds after generation
      if (task?._demo_qris && task.payment_status === 'pending' && Date.now() - (task._demo_qris_created || 0) >= 6000) {
        task.payment_status = 'paid';
        task._demo_qris.status = 'paid';
        task._demo_qris.paid_at = new Date().toISOString();
      }
      return {
        task_id: taskId,
        billing_amount: task?.billing_amount || 0,
        payment_status: task?.payment_status || 'unpaid',
        is_paid: task?.payment_status === 'paid',
        transaction: task?._demo_qris || null,
      };
    }

    return firstValueFrom(
      this.http.get(`${this.baseUrl}/tasks/${taskId}/payment-status?_ts=${Date.now()}`, this.noCacheHeaders())
    );
  }

  // Customer Digital Signature (with Offline Queue Fallback)
  async saveTaskSignature(taskId: number, signature: string, signerName: string, idempotencyKey?: string): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      if (task) {
        task.customer_signature = signature;
        task.signer_name = signerName;
      }
      return { message: 'Signature saved in demo mode', data: task };
    }
    const key = idempotencyKey || this.offlineQueue.generateUuid();
    try {
      return await firstValueFrom(
        this.http.post(
          `${this.baseUrl}/tasks/${taskId}/signature`,
          { signature, signer_name: signerName },
          this.idempotentHeaders(key)
        )
      );
    } catch (err) {
      if (this.isOfflineNetworkError(err)) {
        await this.offlineQueue.enqueueMutation(taskId, 'signature', { signature, signer_name: signerName }, key);
        return { _queued: true, message: 'Customer signature saved offline — will sync automatically.' };
      }
      throw err;
    }
  }

  /**
   * Replay all queued offline work order mutations in strict FIFO causal order
   * using their original UUIDv4 Idempotency-Key so retries never duplicate data.
   */
  async flushOfflineMutations(): Promise<{ synced: number; remaining: number }> {
    if (await this.isDemoMode()) {
      return { synced: 0, remaining: 0 };
    }
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
      const pending = await this.offlineQueue.getPendingMutations();
      return { synced: 0, remaining: pending.length };
    }

    const queue = await this.offlineQueue.getPendingMutations();
    let synced = 0;

    for (const item of queue) {
      try {
        const headers = this.idempotentHeaders(item.idempotency_key);
        if (item.type === 'accept') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/accept`, {}, headers));
        } else if (item.type === 'reject') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/reject`, item.payload || {}, headers));
        } else if (item.type === 'start') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/start`, {}, headers));
        } else if (item.type === 'progress') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/progress`, item.payload || {}, headers));
        } else if (item.type === 'material_add') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/materials`, item.payload || {}, headers));
        } else if (item.type === 'signature') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/signature`, item.payload || {}, headers));
        } else if (item.type === 'complete') {
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/complete`, {}, headers));
        } else if (item.type === 'proof' && item.payload?.dataUrl) {
          const file = this.offlineQueue.dataUrlToFile(item.payload.dataUrl, item.payload.fileName || 'offline_proof.jpg');
          const fd = new FormData();
          fd.append('image', file);
          fd.append('description', item.payload.description || '');
          if (item.payload.slot) fd.append('slot', item.payload.slot);
          if (item.payload.optical_power_dbm !== null && item.payload.optical_power_dbm !== undefined) {
            fd.append('optical_power_dbm', String(item.payload.optical_power_dbm));
          }
          await firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${item.task_id}/proof`, fd, headers));
        }

        await this.offlineQueue.removeMutation(item.id);
        synced += 1;
      } catch (err: any) {
        if (this.isOfflineNetworkError(err)) {
          break; // Network dropped again; stop FIFO replay to preserve causal order
        }
        // If server rejected permanently (e.g. 422 / 409 / 404 after 3 attempts), drop stale item so queue never deadlocks
        if (err?.status >= 400 && err?.status < 500) {
          await this.offlineQueue.removeMutation(item.id);
        } else {
          await this.offlineQueue.markMutationAttempt(item.id, err?.message || 'Retry failed');
          break;
        }
      }
    }

    const remaining = (await this.offlineQueue.getPendingMutations()).length;
    return { synced, remaining };
  }
}
