import { Injectable, inject } from '@angular/core';
import { ActionSheetController, ToastController } from '@ionic/angular';
import { Capacitor } from '@capacitor/core';

@Injectable({
  providedIn: 'root'
})
export class NavigationService {
  private actionSheetCtrl = inject(ActionSheetController);
  private toastCtrl = inject(ToastController);

  /**
   * Open an interactive Action Sheet with multiple GPS navigation options (Google Maps, Waze, Apple Maps).
   */
  async openNavigationOptions(latitude: number, longitude: number, destinationLabel?: string) {
    if (!latitude || !longitude) {
      await this.showToast('Koordinat GPS lokasi tidak tersedia', 'warning');
      return;
    }

    const platform = Capacitor.getPlatform(); // 'ios', 'android', or 'web'
    const isIOS = platform === 'ios';
    const cleanLat = Number(latitude).toFixed(6);
    const cleanLng = Number(longitude).toFixed(6);
    const label = destinationLabel ? destinationLabel : `Lokasi (${cleanLat}, ${cleanLng})`;

    // Action sheet buttons configuration
    const buttons: any[] = [];

    // 1. Google Maps (Primary for Android & universally supported)
    buttons.push({
      text: 'Google Maps (Navigasi Suara & Rute)',
      icon: 'navigate-outline',
      handler: () => {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${cleanLat},${cleanLng}`;
        window.open(url, '_system');
      }
    });

    // 2. Waze (Ideal for avoiding traffic & real-time road alerts in Indonesia)
    buttons.push({
      text: 'Waze (Anti-Macet & Laporan Jalan)',
      icon: 'car-sport-outline',
      handler: () => {
        const url = `https://waze.com/ul?ll=${cleanLat},${cleanLng}&navigate=yes`;
        window.open(url, '_system');
      }
    });

    // 3. Apple Maps (Specialized for iOS native navigation)
    if (isIOS) {
      buttons.unshift({
        text: 'Apple Maps (Navigasi Native iOS)',
        icon: 'map-outline',
        handler: () => {
          const url = `maps://?daddr=${cleanLat},${cleanLng}&dirflg=d`;
          window.open(url, '_system');
        }
      });
    } else {
      // Available on Android/Web as web option if preferred
      buttons.push({
        text: 'Apple Maps (Web)',
        icon: 'map-outline',
        handler: () => {
          const url = `https://maps.apple.com/?daddr=${cleanLat},${cleanLng}&dirflg=d`;
          window.open(url, '_system');
        }
      });
    }

    // 4. Salin Titik Koordinat GPS ke Clipboard
    buttons.push({
      text: `Salin Koordinat (${cleanLat}, ${cleanLng})`,
      icon: 'copy-outline',
      handler: async () => {
        const coordText = `${cleanLat}, ${cleanLng}`;
        try {
          if (navigator?.clipboard?.writeText) {
            await navigator.clipboard.writeText(coordText);
          } else {
            const input = document.createElement('input');
            input.value = coordText;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
          }
          await this.showToast('Koordinat GPS berhasil disalin ke clipboard! 📋', 'success');
        } catch (e) {
          await this.showToast(`Koordinat: ${coordText}`, 'success');
        }
      }
    });

    // 5. Cancel Button
    buttons.push({
      text: 'Batal',
      icon: 'close-outline',
      role: 'cancel'
    });

    const actionSheet = await this.actionSheetCtrl.create({
      header: 'PILIH APLIKASI NAVIGASI',
      subHeader: `Tujuan: ${label}`,
      buttons: buttons,
      mode: isIOS ? 'ios' : 'md',
    });

    await actionSheet.present();
  }

  private async showToast(msg: string, color: 'success' | 'warning' | 'danger' = 'success') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2500,
      color: color,
      position: 'top',
    });
    await toast.present();
  }
}
