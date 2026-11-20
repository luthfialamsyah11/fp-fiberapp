import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Capacitor } from '@capacitor/core';
import { PushNotifications, PushNotificationSchema, ActionPerformed, Token } from '@capacitor/push-notifications';
import { ToastController } from '@ionic/angular';
import { ApiService } from './api.service';
import { StorageService } from './storage.service';

@Injectable({ providedIn: 'root' })
export class PushNotificationService {
  private api = inject(ApiService);
  private storage = inject(StorageService);
  private router = inject(Router);
  private toastCtrl = inject(ToastController);

  private initialized = false;
  private readonly TOKEN_STORAGE_KEY = 'fsm_fcm_device_token_v1';

  async initForAuthenticatedUser(): Promise<void> {
    if (this.initialized || !Capacitor.isNativePlatform()) {
      return;
    }

    try {
      const perm = await PushNotifications.checkPermissions();
      let receive = perm.receive;
      if (receive === 'prompt' || receive === 'prompt-with-rationale') {
        const req = await PushNotifications.requestPermissions();
        receive = req.receive;
      }

      if (receive !== 'granted') {
        return;
      }

      this.initialized = true;

      await PushNotifications.addListener('registration', async (token: Token) => {
        if (!token?.value) return;
        await this.storage.set(this.TOKEN_STORAGE_KEY, token.value);
        try {
          await this.api.registerDeviceToken(token.value, Capacitor.getPlatform());
        } catch {
          // Will retry on next login / foreground wake
        }
      });

      await PushNotifications.addListener('pushNotificationReceived', async (notification: PushNotificationSchema) => {
        const title = notification.title || 'FiberOps Dispatch Alert';
        const body = notification.body || 'You have a new work order update.';
        const toast = await this.toastCtrl.create({
          header: title,
          message: body,
          duration: 4500,
          position: 'top',
          color: 'primary',
        });
        await toast.present();
      });

      await PushNotifications.addListener('pushNotificationActionPerformed', (action: ActionPerformed) => {
        const taskId = action?.notification?.data?.task_id;
        if (taskId) {
          this.router.navigate(['/task-detail', Number(taskId)]);
        } else {
          this.router.navigate(['/task-list']);
        }
      });

      await PushNotifications.register();
    } catch {
      // Gracefully skip if Firebase google-services.json is not bundled yet
    }
  }

  async unregisterOnLogout(): Promise<void> {
    try {
      const token = await this.storage.get(this.TOKEN_STORAGE_KEY);
      if (token) {
        await this.api.unregisterDeviceToken(String(token)).catch(() => {});
        await this.storage.remove(this.TOKEN_STORAGE_KEY);
      }
    } catch {}
  }
}
