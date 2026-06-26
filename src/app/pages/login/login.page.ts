import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { LoadingController, ToastController } from '@ionic/angular';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  standalone: false,
})
export class LoginPage {
  private auth = inject(AuthService);
  private router = inject(Router);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);

  email = '';
  password = '';
  showPassword = false;
  emailFocused = false;
  passwordFocused = false;

  async login() {
    if (!this.email || !this.password) {
      this.showToast('Email dan password wajib diisi');
      return;
    }

    const loading = await this.loadingCtrl.create({ message: 'Masuk...' });
    await loading.present();

    try {
      const result = await this.auth.login(this.email, this.password);
      await loading.dismiss();
      this.router.navigate(['/dashboard'], { replaceUrl: true });
    } catch (err: any) {
      await loading.dismiss();
      
      // Extract error message dengan prioritas
      let msg = 'Login gagal, cek email dan password';
      
      if (err?.error?.message) {
        msg = err.error.message;
      } else if (err?.message) {
        msg = err.message;
      } else if (typeof err === 'string') {
        msg = err;
      }
      
      console.error('Login error:', err);
      this.showToast(msg);
    }
  }

  async showToast(msg: string) {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 3000,
      position: 'bottom',
      color: 'danger'
    });
    await toast.present();
  }

  togglePassword() {
    this.showPassword = !this.showPassword;
  }
}