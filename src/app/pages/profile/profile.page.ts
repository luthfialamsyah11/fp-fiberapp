import { Component, OnDestroy, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { AlertController, LoadingController, ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { GpsHealthStatus, LocationService } from '../../services/location.service';
import { StorageService } from '../../services/storage.service';

@Component({
  selector: 'app-profile',
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  standalone: false,
})
export class ProfilePage implements OnDestroy {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private storage = inject(StorageService);
  private locationService = inject(LocationService);
  private router = inject(Router);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);
  private alertCtrl = inject(AlertController);

  user: any = null;
  completedTasks: any[] = [];
  showAllHistory = false;

  stats: {
    completed_count: number;
    active_count: number;
    declined_count: number;
    acceptance_rate: number;
    proof_compliance_rate: number;
    avg_rating: number | null;
    rated_count: number;
  } = {
    completed_count: 0,
    active_count: 0,
    declined_count: 0,
    acceptance_rate: 100,
    proof_compliance_rate: 100,
    avg_rating: null,
    rated_count: 0,
  };

  isOnline = true;
  gpsStatus: GpsHealthStatus = 'offline';
  gpsStatusMessage = 'Offline';
  pendingOfflineCount = 0;
  isNative = Capacitor.isNativePlatform();

  name = '';
  phone = '';
  email = '';
  role = '';
  isLoading = true;
  isSaving = false;
  isEditing = false;
  nameFocused = false;
  phoneFocused = false;

  // Self-service Change Password state (PR-6)
  isChangingPassword = false;
  currentPassword = '';
  newPassword = '';
  confirmPassword = '';
  isSavingPassword = false;

  private subs = new Subscription();

  constructor() {
    this.subs.add(
      this.locationService.status$.subscribe(s => {
        this.gpsStatus = s;
      })
    );
    this.subs.add(
      this.locationService.statusMessage$.subscribe(m => {
        this.gpsStatusMessage = m;
      })
    );
  }

  async ionViewWillEnter() {
    await this.loadProfile();
  }

  ngOnDestroy() {
    this.subs.unsubscribe();
  }

  get userInitials(): string {
    const raw = String(this.user?.name || this.name || 'FT').trim();
    const parts = raw.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return raw.substring(0, 2).toUpperCase() || 'FT';
  }

  get visibleCompletedTasks(): any[] {
    return this.showAllHistory ? this.completedTasks : this.completedTasks.slice(0, 5);
  }

  get hasGpsWarning(): boolean {
    return (
      this.isOnline &&
      (this.gpsStatus === 'no_permission' ||
        this.gpsStatus === 'gps_off' ||
        this.gpsStatus === 'no_network' ||
        this.gpsStatus === 'stale')
    );
  }

  async loadProfile() {
    this.isLoading = true;
    try {
      const [res, hRes, pendingCount]: [any, any, number] = await Promise.all([
        this.api.getMe(),
        this.api.getHistory().catch((hErr) => {
          console.warn('Failed to load history in profile', hErr);
          return [];
        }),
        this.locationService.getPendingOfflineCount(),
      ]);

      this.pendingOfflineCount = pendingCount;
      this.user = res?.data || res;
      if (this.user) {
        this.name = this.user.name || '';
        this.phone = this.user.phone || '';
        this.email = this.user.email || '';
        this.role = this.user.role || '';
        this.isOnline = this.user.is_online !== undefined ? !!this.user.is_online : true;
      }

      this.completedTasks = Array.isArray(hRes) ? hRes : (hRes?.data ?? []);

      if (res?.stats) {
        this.stats = {
          completed_count: Number(res.stats.completed_count ?? this.completedTasks.length),
          active_count: Number(res.stats.active_count ?? 0),
          declined_count: Number(res.stats.declined_count ?? 0),
          acceptance_rate: Number(res.stats.acceptance_rate ?? 100),
          proof_compliance_rate: Number(res.stats.proof_compliance_rate ?? 100),
          avg_rating: res.stats.avg_rating !== null && res.stats.avg_rating !== undefined
            ? Number(res.stats.avg_rating)
            : null,
          rated_count: Number(res.stats.rated_count ?? 0),
        };
      } else {
        this.stats.completed_count = this.completedTasks.length;
      }
    } catch (e: any) {
      const errMsg = e?.error?.message || 'Failed to load technician profile';
      this.showToast(errMsg, 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  async doRefresh(event: any) {
    await this.loadProfile();
    event.target.complete();
  }

  async toggleOnlineStatus() {
    const nextStatus = !this.isOnline;
    this.isOnline = nextStatus;

    if (nextStatus) {
      this.locationService.startTracking();
    } else {
      this.locationService.stopTracking();
    }

    try {
      const res: any = await this.api.updateProfile({ is_online: nextStatus });
      if (res?.data) {
        this.user = res.data;
        await this.storage.set('user', res.data);
      }
      this.showToast(
        nextStatus ? 'You are now Online and sharing live GPS.' : 'You are now Offline. GPS tracking paused.',
        nextStatus ? 'success' : 'medium'
      );
    } catch {
      this.isOnline = !nextStatus;
      if (this.isOnline) {
        this.locationService.startTracking();
      } else {
        this.locationService.stopTracking();
      }
      this.showToast('Failed to update shift status.', 'danger');
    }
  }

  async syncGpsNow() {
    try {
      await this.locationService.forceSyncNow();
      this.pendingOfflineCount = await this.locationService.getPendingOfflineCount();
      this.showToast('GPS position synced with NOC.', 'success');
    } catch {
      this.showToast('Unable to acquire GPS fix. Check location permission.', 'warning');
    }
  }

  async flushOfflineBuffer() {
    await this.locationService.flushOfflineQueue();
    this.pendingOfflineCount = await this.locationService.getPendingOfflineCount();
    this.showToast(
      this.pendingOfflineCount === 0
        ? 'Offline GPS buffer flushed successfully.'
        : `${this.pendingOfflineCount} GPS point(s) still pending.`,
      this.pendingOfflineCount === 0 ? 'success' : 'warning'
    );
  }

  async openNativeLocationSettings() {
    await this.locationService.openLocationSettings();
  }

  openEditMode() {
    this.name = this.user?.name || '';
    this.phone = this.user?.phone || '';
    this.isChangingPassword = false;
    this.isEditing = true;
  }

  cancelEdit() {
    // Revert dirty inputs back to persisted user values (fixes PR-2)
    this.name = this.user?.name || '';
    this.phone = this.user?.phone || '';
    this.isEditing = false;
  }

  openChangePassword() {
    this.currentPassword = '';
    this.newPassword = '';
    this.confirmPassword = '';
    this.isEditing = false;
    this.isChangingPassword = true;
  }

  cancelChangePassword() {
    this.currentPassword = '';
    this.newPassword = '';
    this.confirmPassword = '';
    this.isChangingPassword = false;
  }

  async submitPasswordChange() {
    if (!this.currentPassword) {
      this.showToast('Please enter your current password.', 'warning');
      return;
    }
    if (this.newPassword.length < 8) {
      this.showToast('New password must be at least 8 characters.', 'warning');
      return;
    }
    if (this.newPassword !== this.confirmPassword) {
      this.showToast('New password confirmation does not match.', 'warning');
      return;
    }

    this.isSavingPassword = true;
    const loading = await this.loadingCtrl.create({ message: 'Updating password...' });
    await loading.present();

    try {
      await this.api.updatePassword(this.currentPassword, this.newPassword, this.confirmPassword);
      await loading.dismiss();
      this.cancelChangePassword();
      this.showToast('Password updated successfully.', 'success');
    } catch (e: any) {
      await loading.dismiss();
      const firstErr = e?.error?.errors
        ? (([] as any[]).concat(...Object.values(e.error.errors))[0] as string)
        : null;
      const errMsg = firstErr || e?.error?.message || 'Failed to update password.';
      this.showToast(errMsg, 'danger');
    } finally {
      this.isSavingPassword = false;
    }
  }

  goToDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  openBastPdf(task: any, event?: Event) {
    if (event) event.stopPropagation();
    if (!task?.id) return;
    const url = `${this.api.getBaseUrl()}/tasks/${task.id}/bast?token=${task.tracking_token || ''}`;
    window.open(url, '_blank', 'noopener,noreferrer');
  }

  formatTaskCode(id: number): string {
    return `#TSK-${String(id).padStart(4, '0')}`;
  }

  async saveProfile() {
    const trimmedName = this.name.trim();
    const trimmedPhone = this.phone.trim();
    if (!trimmedName) {
      this.showToast('Full name cannot be empty.', 'warning');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Saving profile...' });
    await loading.present();
    this.isSaving = true;

    try {
      const res: any = await this.api.updateProfile({
        name: trimmedName,
        phone: trimmedPhone,
      });

      const updatedUser = res?.data || { ...this.user, name: trimmedName, phone: trimmedPhone };
      this.user = updatedUser;
      this.name = updatedUser.name || trimmedName;
      this.phone = updatedUser.phone || trimmedPhone;
      await this.storage.set('user', updatedUser);

      await loading.dismiss();
      this.isEditing = false;
      this.showToast('Profile updated successfully.', 'success');
    } catch (e: any) {
      await loading.dismiss();
      const errMsg = e?.error?.message || 'Failed to update profile. Please try again.';
      this.showToast(errMsg, 'danger');
    } finally {
      this.isSaving = false;
    }
  }

  async doLogout() {
    const alert = await this.alertCtrl.create({
      header: 'Log Out',
      message: 'Are you sure you want to log out? Live GPS tracking will stop until you sign in again.',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Log Out',
          role: 'destructive',
          handler: () => this.auth.logout(),
        },
      ],
    });
    await alert.present();
  }

  async helpCenter() {
    const cfg = this.api.getClientConfig();
    const company = cfg.company_name || 'FiberOps Field Support';
    const supportEmail = cfg.support_email || 'noc@bentengsiber.com';
    const supportPhone = cfg.support_phone || '+1-800-555-0199';

    const alert = await this.alertCtrl.create({
      header: 'NOC Help Center',
      subHeader: company,
      message: `Email: ${supportEmail}\nPhone: ${supportPhone}\nService Hours: 24/7 Dispatch Support`,
      buttons: [
        {
          text: 'Call NOC',
          handler: () => {
            window.open(`tel:${supportPhone}`, '_system');
          },
        },
        {
          text: 'Email NOC',
          handler: () => {
            window.open(`mailto:${supportEmail}`, '_system');
          },
        },
        { text: 'Close', role: 'cancel' },
      ],
    });
    await alert.present();
  }

  async appInfo() {
    const cfg = this.api.getClientConfig();
    const company = cfg.company_name || 'FiberOps';
    const alert = await this.alertCtrl.create({
      header: 'About FiberOps',
      message: `${company} Field Technician App\nVersion: 2.0.0\nPlatform: ${Capacitor.getPlatform().toUpperCase()}\n\nReal-time ISP Work Orders, Background GPS Telemetry & Digital BAST Verification.`,
      buttons: ['Close'],
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
