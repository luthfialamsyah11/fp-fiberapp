import { Component, OnInit, inject } from '@angular/core';
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
export class ProfilePage implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private storage = inject(StorageService);
  private router = inject(Router);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);
  private alertCtrl = inject(AlertController);

  user: any = null;
  name = '';
  phone = '';
  email = '';
  role = '';
  isLoading = true;
  isSaving = false;
  isEditing = false;
  nameFocused = false;
  phoneFocused = false;

  ngOnInit() {}

  async ionViewWillEnter() {
    await this.loadProfile();
  }

  async loadProfile() {
    this.isLoading = true;
    try {
      const res: any = await this.api.getMe();
      this.user = res?.data || res;
      if (this.user) {
        this.name = this.user.name || '';
        this.phone = this.user.phone || '';
        this.email = this.user.email || '';
        this.role = this.user.role || '';
      }
    } catch (e: any) {
      const errMsg = e?.error?.message || 'Gagal memuat profil';
      const status = e?.status !== undefined ? e.status : 'N/A';
      this.showToast(`${errMsg} (Status: ${status})`, 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  async saveProfile() {
    if (!this.name.trim()) {
      this.showToast('Nama lengkap tidak boleh kosong', 'warning');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Menyimpan profil...' });
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
      this.showToast('Profil berhasil diperbarui', 'success');
    } catch (e: any) {
      await loading.dismiss();
      const errMsg = e?.message || e?.error?.message || JSON.stringify(e);
      const status = e?.status !== undefined ? e.status : 'N/A';
      this.showToast(`Gagal memperbarui profil: ${errMsg} (Status: ${status})`, 'danger');
    } finally {
      this.isSaving = false;
    }
  }

  async doLogout() {
    const alert = await this.alertCtrl.create({
      header: 'Keluar',
      message: 'Apakah Anda yakin ingin keluar dari akun?',
      buttons: [
        { text: 'Batal', role: 'cancel' },
        {
          text: 'Keluar',
          role: 'destructive',
          handler: () => this.auth.logout()
        }
      ]
    });
    await alert.present();
  }

  async changePassword() {
    const alert = await this.alertCtrl.create({
      header: 'Ubah Kata Sandi',
      message: 'Demi keamanan data, silakan hubungi Tim IT Administrator FiberOps untuk mereset atau mengganti kata sandi Anda.',
      buttons: ['Mengerti']
    });
    await alert.present();
  }

  async helpCenter() {
    const alert = await this.alertCtrl.create({
      header: 'Pusat Bantuan',
      message: 'Layanan Bantuan Lapangan FiberOps:\n\nEmail: field-support@fiberops.com\nTelepon: (021) 8062-8888\nJam Kerja: 24/7',
      buttons: ['Tutup']
    });
    await alert.present();
  }

  async appInfo() {
    const alert = await this.alertCtrl.create({
      header: 'Tentang FiberOps',
      message: 'FiberOps Technician App\nVersi: 1.0.4-premium\nBuild: 2026.06.10\n\nSistem manajemen lapangan terpadu ISP & WiFi.',
      buttons: ['Selesai']
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
