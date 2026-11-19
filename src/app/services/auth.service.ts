import { Injectable, inject } from '@angular/core';
import { StorageService } from './storage.service';
import { ApiService } from './api.service';
import { Router } from '@angular/router';
import { LocationService } from './location.service';
import { environment } from '../../environments/environment';
import { DEMO_USER } from './demo-mock.data';

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
        // Revoke the issued token so it does not remain orphaned in personal_access_tokens
        if (res.token) {
          fetch(`${environment.apiUrl}/logout`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${res.token}`,
            },
          }).catch(() => {});
        }
        throw { error: { message: 'This terminal is restricted to field technicians. Administrators please sign in via Admin Portal.' } };
      }

      await this.storage.remove('demo_mode');
      await this.storage.set('token', res.token);
      await this.storage.set('user', res.user);

      // Start GPS telemetry right on login if technician is online (independent of route)
      if (res.user?.is_online !== false) {
        this.locationService.startTracking();
      }
      return res;
    } catch (error: any) {
      if (isDebug) console.error('[AuthService] Login error:', error);
      
      // Re-throw with user-friendly message
      if (error?.error?.message) {
        throw error;
      }
      if (error?.status === 401 || error?.statusText === 'Unauthorized') {
        throw { error: { message: 'Invalid email or password.' } };
      }
      if (error?.status === 0 || error?.statusText === 'Unknown Error') {
        throw { error: { message: 'Connection failed. Please check network connectivity and API server status.' } };
      }
      throw { error: { message: error?.message || 'An unexpected error occurred during sign in.' } };
    }
  }

  async loginDemo(email?: string): Promise<any> {
    const demoUser = {
      ...DEMO_USER,
      email: email || DEMO_USER.email,
      name: email?.startsWith('budi')
        ? 'Budi Santoso'
        : email?.startsWith('citra')
          ? 'Citra Lestari'
          : DEMO_USER.name,
    };
    await this.storage.set('demo_mode', true);
    await this.storage.set('token', 'demo-mock-token');
    await this.storage.set('user', demoUser);
    return { token: 'demo-mock-token', user: demoUser };
  }

  async logout() {
    try {
      this.locationService.stopTracking();
      await this.api.logout();
    } catch (e) {
      console.warn('[AuthService] Logout API failed, continuing local logout:', e);
    }
    await this.storage.remove('demo_mode');
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
