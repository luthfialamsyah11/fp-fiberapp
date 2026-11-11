import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { LoadingController, ToastController } from '@ionic/angular';
import { AuthService } from '../../services/auth.service';
import { StorageService } from '../../services/storage.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false,
})
export class LoginPage {
  private auth = inject(AuthService);
  private storage = inject(StorageService);
  private router = inject(Router);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);

  email = '';
  password = '';
  showPassword = false;
  emailFocused = false;
  passwordFocused = false;
  rememberMe = true;
  isLoading = false;

  demoTechnicians = [
    { name: 'Alex', email: 'alex@fsm.com', role: 'Teknisi Fiber' },
    { name: 'Budi', email: 'budi@fsm.com', role: 'Teknisi ONT' },
    { name: 'Citra', email: 'citra@fsm.com', role: 'Teknisi Backbone' },
  ];

  async ionViewWillEnter() {
    if (await this.auth.isLoggedIn()) {
      this.router.navigate(['/task-list'], { replaceUrl: true });
      return;
    }

    // Auto-populate remembered email
    const savedEmail = await this.storage.get('remembered_email');
    if (savedEmail) {
      this.email = savedEmail;
    }
  }

  fillDemo(email: string) {
    this.email = email;
    this.password = 'password123';
    this.showToast(`Kredensial demo ${email} diterapkan`, 'success');
  }

  async login() {
    if (!this.email || !this.password) {
      this.showToast('Email dan password wajib diisi', 'warning');
      return;
    }

    this.isLoading = true;
    const loading = await this.loadingCtrl.create({ 
      message: 'Memverifikasi akun...',
      spinner: 'crescent'
    });
    await loading.present();

    try {
      await this.auth.login(this.email, this.password);
      
      // Save or remove remembered email
      if (this.rememberMe) {
        await this.storage.set('remembered_email', this.email);
      } else {
        await this.storage.remove('remembered_email');
      }

      await loading.dismiss();
      this.isLoading = false;
      this.router.navigate(['/task-list'], { replaceUrl: true });
    } catch (err: any) {
      await loading.dismiss();
      this.isLoading = false;
      
      let msg = 'Login gagal, cek email dan password';
      if (err?.error?.message) {
        msg = err.error.message;
      } else if (err?.message) {
        msg = err.message;
      } else if (typeof err === 'string') {
        msg = err;
      }
      
      console.error('Login error:', err);
      this.showToast(msg, 'danger');
    }
  }

  async showToast(msg: string, color: 'danger' | 'warning' | 'success' = 'danger') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 3000,
      position: 'top',
      color: color
    });
    await toast.present();
  }

  togglePassword() {
    this.showPassword = !this.showPassword;
  }
}
