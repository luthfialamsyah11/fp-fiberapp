import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { firstValueFrom } from 'rxjs';
import { StorageService } from './storage.service';
import { environment } from '../../environments/environment';

const isDebug = !environment.production;

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);
  private storage = inject(StorageService);

  private baseUrl = environment.apiUrl;

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
    return firstValueFrom(this.http.post(`${this.baseUrl}/logout`, {}));
  }

  async getMe() {
    return firstValueFrom(this.http.get(`${this.baseUrl}/me`));
  }

  async updateProfile(data: any) {
    return firstValueFrom(this.http.put(`${this.baseUrl}/profile`, data));
  }

  // Tasks
  async getTasks(status?: string) {
    const params = status ? `?status=${status}` : '';
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks${params}`));
  }

  async getTaskById(id: number) {
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${id}`));
  }

  async acceptTask(id: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/accept`, {}));
  }

  async rejectTask(id: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/reject`, {}));
  }

  async startTask(id: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/start`, {}));
  }

  async completeTask(id: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/complete`, {}));
  }

  async updateProgress(id: number, note: string, percent: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${id}/progress`, { note, progress_percent: percent }));
  }

  async getHistory() {
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/history`));
  }

  // GPS
  async updateLocation(latitude: number, longitude: number) {
    return firstValueFrom(this.http.post(`${this.baseUrl}/location`, { latitude, longitude }));
  }

  // Proof of Work
  async uploadProof(taskId: number, file: File, description: string) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('description', description);
    return firstValueFrom(this.http.post(`${this.baseUrl}/tasks/${taskId}/proof`, formData));
  }

  async getProof(taskId: number) {
    return firstValueFrom(this.http.get(`${this.baseUrl}/tasks/${taskId}/proof`));
  }
}
