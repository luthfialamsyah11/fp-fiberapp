import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController, LoadingController, ToastController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { StorageService } from '../../services/storage.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: false,
})
export class ProfilePage {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private storage = inject(StorageService);
  private router = inject(Router);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);
  private alertCtrl = inject(AlertController);

  user: any = null;
  completedTasks: any[] = [];
  name = '';
  phone = '';
  email = '';
  role = '';
  isLoading = true;
  isSaving = false;
  isEditing = false;
  nameFocused = false;
  phoneFocused = false;

  async ionViewWillEnter() {
    await this.loadProfile();
  }

  async loadProfile() {
    this.isLoading = true;
    try {
      const [res, hRes]: [any, any] = await Promise.all([
        this.api.getMe(),
        this.api.getHistory().catch((hErr) => {
          console.warn('Failed to load history in profile', hErr);
          return [];
        }),
      ]);

      this.user = res?.data || res;
      if (this.user) {
        this.name = this.user.name || '';
        this.phone = this.user.phone || '';
        this.email = this.user.email || '';
        this.role = this.user.role || '';
      }

      this.completedTasks = Array.isArray(hRes) ? hRes : (hRes?.data ?? []);
    } catch (e: any) {
      const errMsg = e?.error?.message || 'Gagal memuat profil';
      const status = e?.status !== undefined ? e.status : 'N/A';
      this.showToast(`${errMsg} (Status: ${status})`, 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  goToDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  async saveProfile() {
    if (!this.name.trim()) {
      this.showToast('Full name cannot be empty', 'warning');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Saving profile...' });
    await loading.present();
    this.isSaving = true;

    try {
      const res: any = await this.api.updateProfile({
        name: this.name,
        phone: this.phone,
      });

      const updatedUser = res?.data || res;
      if (updatedUser) {
        await this.storage.set('user', updatedUser);
      }

      await loading.dismiss();
      this.isEditing = false;
      this.showToast('Profile updated successfully', 'success');
    } catch (e: any) {
      await loading.dismiss();
      const errMsg = e?.message || e?.error?.message || JSON.stringify(e);
      const status = e?.status !== undefined ? e.status : 'N/A';
      this.showToast(`Failed to update profile: ${errMsg} (Status: ${status})`, 'danger');
    } finally {
      this.isSaving = false;
    }
  }

  async doLogout() {
    const alert = await this.alertCtrl.create({
      header: 'Log Out',
      message: 'Are you sure you want to log out of your technician account?',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Log Out',
          role: 'destructive',
          handler: () => this.auth.logout()
        }
      ]
    });
    await alert.present();
  }

  async changePassword() {
    const alert = await this.alertCtrl.create({
      header: 'Change Password',
      message: 'For enterprise security compliance, please contact your FiberOps IT Administrator to reset or update your credentials.',
      buttons: ['Understood']
    });
    await alert.present();
  }

  async helpCenter() {
    const alert = await this.alertCtrl.create({
      header: 'Help Center',
      message: 'FiberOps Field Support Services:\n\nEmail: field-support@fiberops.com\nPhone: +62 21 8062-8888\nService Hours: 24/7 Enterprise Support',
      buttons: ['Close']
    });
    await alert.present();
  }

  async appInfo() {
    const alert = await this.alertCtrl.create({
      header: 'About FiberOps',
      message: 'FiberOps Technician PWA\nVersion: 1.0.4-premium\nBuild: 2026.11.16\n\nEnterprise ISP Field Workforce & Infrastructure Management Platform.',
      buttons: ['Close']
    });
    await alert.present();
  }

  async showToast(msg: string, color: string) {
    const t = await this.toastCtrl.create({
      message: msg,
      duration: 2500,
      color,
      position: 'top',
    });
    await t.present();
  }
}
