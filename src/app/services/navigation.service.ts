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
   * Open an interactive Action Sheet with multiple GPS navigation options (Google Maps, Waze, Apple Maps, Copy).
   */
  async openNavigationOptions(latitude: number, longitude: number, destinationLabel?: string) {
    if (latitude === null || latitude === undefined || longitude === null || longitude === undefined || (Math.abs(latitude) < 0.0001 && Math.abs(longitude) < 0.0001)) {
      await this.showToast('GPS coordinates are not available for this destination.', 'warning');
      return;
    }

    const platform = Capacitor.getPlatform();
    const isIOS = platform === 'ios';
    const cleanLat = Number(latitude).toFixed(6);
    const cleanLng = Number(longitude).toFixed(6);
    const label = destinationLabel ? destinationLabel : `Location (${cleanLat}, ${cleanLng})`;

    const buttons: any[] = [];

    buttons.push({
      text: 'Google Maps (Turn-by-Turn)',
      icon: 'navigate-outline',
      handler: () => {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${cleanLat},${cleanLng}`;
        window.open(url, '_system');
      }
    });

    buttons.push({
      text: 'Waze (Live Traffic)',
      icon: 'car-sport-outline',
      handler: () => {
        const url = `https://waze.com/ul?ll=${cleanLat},${cleanLng}&navigate=yes`;
        window.open(url, '_system');
      }
    });

    if (isIOS) {
      buttons.unshift({
        text: 'Apple Maps (Native iOS)',
        icon: 'map-outline',
        handler: () => {
          const url = `maps://?daddr=${cleanLat},${cleanLng}&dirflg=d`;
          window.open(url, '_system');
        }
      });
    } else {
      buttons.push({
        text: 'Apple Maps (Web)',
        icon: 'map-outline',
        handler: () => {
          const url = `https://maps.apple.com/?daddr=${cleanLat},${cleanLng}&dirflg=d`;
          window.open(url, '_system');
        }
      });
    }

    buttons.push({
      text: `Copy Coordinates (${cleanLat}, ${cleanLng})`,
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
          await this.showToast('GPS coordinates copied to clipboard.', 'success');
        } catch {
          await this.showToast(`Coordinates: ${coordText}`, 'success');
        }
      }
    });

    buttons.push({
      text: 'Cancel',
      icon: 'close-outline',
      role: 'cancel'
    });

    const actionSheet = await this.actionSheetCtrl.create({
      header: 'CHOOSE NAVIGATION APP',
      subHeader: `Destination: ${label}`,
      buttons,
      mode: isIOS ? 'ios' : 'md',
    });

    await actionSheet.present();
  }

  private async showToast(msg: string, color: 'success' | 'warning' | 'danger' = 'success') {
    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 2500,
      color,
      position: 'top',
    });
    await toast.present();
  }
}
