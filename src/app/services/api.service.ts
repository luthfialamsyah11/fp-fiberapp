import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { StorageService } from './storage.service';
import { environment } from '../../environments/environment';
import { DEMO_INVENTORY, DEMO_USER, createInitialDemoTasks } from './demo-mock.data';

const isDebug = !environment.production;

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private storage = inject(StorageService);

  private baseUrl = environment.apiUrl;
  private demoUser = { ...DEMO_USER };
  private demoTasks = createInitialDemoTasks();
  private demoInventory = DEMO_INVENTORY.map(item => ({ ...item }));

  getBaseUrl(): string {
    return this.baseUrl.replace(/\/api$/, '');
  }

  private async isDemoMode(): Promise<boolean> {
    return (await this.storage.get('demo_mode')) === true;
  }

  // Auth
  async login(email: string, password: string) {
    const url = `${this.baseUrl}/login`;
    
    try {
      const response = await firstValueFrom(this.http.post(url, { email, password }));
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
      return { data: this.demoUser };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/me`));
  }

  async updateProfile(data: any) {
    if (await this.isDemoMode()) {
      this.demoUser = { ...this.demoUser, ...data };
      return { data: this.demoUser };
    }
    return firstValueFrom(this.http.put(`${this.baseUrl}/profile`, data));
  }

  // Tasks
  async getTasks(status?: string) {
    if (await this.isDemoMode()) {
      const list = status
        ? this.demoTasks.filter(t => t.status === status)
        : this.demoTasks.filter(t => t.status !== 'completed');
      return { data: list };
    }
    const params = status ? `?status=${status}` : '';
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks${params}`));
  }

  async getTaskById(id: number) {
    if (await this.isDemoMode()) {
      const found = this.demoTasks.find(t => t.id === Number(id)) || this.demoTasks[0];
      return { data: found };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${id}`));
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
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/accept`, {}));
  }

  async rejectTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'rejected', 0);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/reject`, {}));
  }

  async startTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'on-going', 25);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/start`, {}));
  }

  async completeTask(id: number) {
    if (await this.isDemoMode()) return this.updateDemoTaskStatus(id, 'completed', 100);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/complete`, {}));
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
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/progress`, { note, progress_percent: percent }));
  }

  async getHistory() {
    if (await this.isDemoMode()) {
      return { data: this.demoTasks.filter(t => t.status === 'completed') };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/history`));
  }

  // GPS
  async updateLocation(latitude: number, longitude: number) {
    if (await this.isDemoMode()) {
      return { status: 'ok', latitude, longitude };
    }
    return firstValueFrom(this.http.post(`${this.baseUrl}/location`, { latitude, longitude }));
  }

  // Proof of Work
  async uploadProof(taskId: number, file: File, description: string) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const proof = {
        id: Date.now(),
        description,
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
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${taskId}/proof`, formData));
  }

  async getProof(taskId: number) {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      return { data: task?.proof_of_work || [] };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/proof`));
  }

  // Inventory & Materials (BOM)
  async getInventory(): Promise<any> {
    if (await this.isDemoMode()) {
      return { data: this.demoInventory };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/inventory`));
  }

  async getTaskMaterials(taskId: number): Promise<any> {
    if (await this.isDemoMode()) {
      const task = this.demoTasks.find(t => t.id === Number(taskId));
      const materials = task?.materials || [];
      const totalCost = materials.reduce((sum: number, m: any) => sum + ((m.inventory_item?.unit_price || 0) * m.quantity), 0);
      return { data: materials, total_cost: totalCost };
    }
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/materials`));
  }

  async addTaskMaterial(taskId: number, data: { inventory_item_id: number; quantity: number; serial_number?: string; notes?: string }): Promise<any> {
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
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${taskId}/materials`, data));
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
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${taskId}/signature`, {
      signature,
      signer_name: signerName
    }));
  }
}
