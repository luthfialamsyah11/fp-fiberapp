import { Injectable, inject } from '@angular/core';
import { StorageService } from './storage.service';
import { ApiService } from './api.service';
import { Router } from '@angular/router';
import { LocationService } from './location.service';
import { environment } from '../../environments/environment';

const isDebug = !environment.production;

@Injectable({ providedIn: 'root' })
export class AuthService {
  private storage = inject(StorageService);
  private api = inject(ApiService);
  private router = inject(Router);
  private locationService = inject(LocationService);

  async login(email: string, password: string): Promise<any> {
    try {
      const res: any = await this.api.login(email, password);
      
      if (res.user && res.user.role !== 'technician') {
        throw { error: { message: 'Aplikasi ini khusus untuk teknisi. Admin harap login melalui Web.' } };
      }

      await this.storage.set('token', res.token);
      await this.storage.set('user', res.user);
      return res;
    } catch (error: any) {
      if (isDebug) console.error('[AuthService] Login error:', error);
      
      // Re-throw dengan message yang user-friendly
      if (error?.error?.message) {
        throw error;
      }
      if (error?.status === 401 || error?.statusText === 'Unauthorized') {
        throw { error: { message: 'Email atau password salah' } };
      }
      if (error?.status === 0 || error?.statusText === 'Unknown Error') {
        throw { error: { message: 'Gagal terhubung ke server. Periksa koneksi internet dan URL API.' } };
      }
      throw { error: { message: error?.message || 'Terjadi kesalahan saat login' } };
    }
  }

  async logout() {
    try {
      this.locationService.stopTracking();
      await this.api.logout();
    } catch (e) {
      console.warn('[AuthService] Logout API failed, continuing local logout:', e);
    }
    await this.storage.remove('token');
    await this.storage.remove('user');
    this.router.navigate(['/login']);
  }

  async getUser() {
    return await this.storage.get('user');
  }

  async getToken() {
    return await this.storage.get('token');
  }

  async isLoggedIn(): Promise<boolean> {
    const token = await this.storage.get('token');
    return !!token;
  }
}
