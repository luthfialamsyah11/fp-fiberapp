import { Component, inject } from '@angular/core';
import { AlertController, Platform, ToastController } from '@ionic/angular';
import { Router } from '@angular/router';
import { Location } from '@angular/common';
import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';
import { AuthService } from './services/auth.service';
import { ApiService } from './services/api.service';
import { LocationService } from './services/location.service';
import { TaskSyncService } from './services/task-sync.service';
import { PushNotificationService } from './services/push-notification.service';
import { OfflineQueueService } from './services/offline-queue.service';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent {
  private platform = inject(Platform);
  private router = inject(Router);
  private location = inject(Location);
  private toastCtrl = inject(ToastController);
  private alertCtrl = inject(AlertController);
  private auth = inject(AuthService);
  private api = inject(ApiService);
  private locationService = inject(LocationService);
  private taskSync = inject(TaskSyncService);
  private pushService = inject(PushNotificationService);
  private offlineQueue = inject(OfflineQueueService);

  private lastTimeBackPress = 0;
  private timePeriodToExit = 2000;
  private hasShownPermissionPrompt = false;

  constructor() {
    this.initializeApp();
  }

  initializeApp() {
    this.platform.ready().then(async () => {
      // 1. Register Android hardware back button / swipe gesture
      this.platform.backButton.subscribeWithPriority(10, async () => {
        const currentUrl = (this.router.url || '').split('?')[0];

        // Root tabs where back button confirms app exit
        const rootUrls = ['/task-list', '/map', '/profile', '/login', '/'];

        if (rootUrls.includes(currentUrl)) {
          if (new Date().getTime() - this.lastTimeBackPress < this.timePeriodToExit) {
            App.exitApp();
          } else {
            const toast = await this.toastCtrl.create({
              message: 'Press back again to exit',
              duration: 2000,
              position: 'bottom',
            });
            await toast.present();
            this.lastTimeBackPress = new Date().getTime();
          }
        } else {
          this.location.back();
        }
      });

      // 2. App-level GPS & badge bootstrap so tracking works regardless of which route the APK opens on (AP-3)
      await this.bootstrapSessionTelemetry();

      // 3. Prompt native Android background location guidance once if permission is denied (AP-5)
      this.locationService.status$.subscribe(status => {
        if (status === 'no_permission' && Capacitor.isNativePlatform() && !this.hasShownPermissionPrompt) {
          this.hasShownPermissionPrompt = true;
          this.presentBackgroundPermissionGuide();
        }
      });
    });
  }

  private async bootstrapSessionTelemetry() {
    try {
      if (!(await this.auth.isLoggedIn())) return;

      // Start global 3-second instant task sync across all routes
      this.taskSync.start();

      // Initialize native FCM push notifications (graceful no-op on web)
      this.pushService.initForAuthenticatedUser();

      // Initialize & auto-flush offline work order mutations when network returns or app resumes
      await this.offlineQueue.init();
      this.api.flushOfflineMutations().catch(() => {});
      window.addEventListener('online', () => {
        this.api.flushOfflineMutations().then(res => {
          if (res.synced > 0) {
            this.taskSync.refreshTasks(false);
          }
        }).catch(() => {});
      });
      App.addListener('appStateChange', ({ isActive }) => {
        if (isActive) {
          this.api.flushOfflineMutations().then(res => {
            if (res.synced > 0) this.taskSync.refreshTasks(false);
          }).catch(() => {});
        }
      });

      const meRes: any = await this.api.getMe().catch(() => null);
      const user = meRes?.data ?? meRes;
      if (user && user.is_online !== false) {
        this.locationService.startTracking();
      }
    } catch {
      // Non-fatal startup fallback
    }
  }

  private async presentBackgroundPermissionGuide() {
    const alert = await this.alertCtrl.create({
      header: 'Background GPS Required',
      message:
        'To share accurate live position with NOC Dispatch even when the screen is locked, please enable Location -> "Allow all the time" and set Battery to "Unrestricted" in App Settings.',
      buttons: [
        { text: 'Later', role: 'cancel' },
        {
          text: 'Open Settings',
          handler: () => {
            this.locationService.openLocationSettings();
          },
        },
      ],
    });
    await alert.present();
  }
}
