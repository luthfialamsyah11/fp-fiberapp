import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { Router } from '@angular/router';
import { LocationService } from '../../services/location.service';
import { ApiService } from '../../services/api.service';
import { NavigationService } from '../../services/navigation.service';

@Component({
  selector: 'app-map',
  templateUrl: './map.page.html',
  styleUrls: ['./map.page.scss'],
  standalone: false,
})
export class MapPage implements OnInit, OnDestroy {
  private locationService = inject(LocationService);
  private api = inject(ApiService);
  private router = inject(Router);
  private navigationService = inject(NavigationService);

  latitude: number | null = null;
  longitude: number | null = null;
  accuracy: number | null = null;
  lastUpdated: Date | null = null;
  isLocating = true;
  activeTask: any = null;
  locationError = false;
  private locationInterval: any = null;

  async ngOnInit() {
    // Basic init
  }

  async ionViewWillEnter() {
    await this.loadActiveTask();
    await this.getCurrentLocation();
    this.startLocationPolling();
  }

  ionViewDidLeave() {
    this.stopLocationPolling();
  }

  ngOnDestroy() {
    this.stopLocationPolling();
  }

  private startLocationPolling() {
    this.stopLocationPolling();
    this.locationInterval = setInterval(() => this.getCurrentLocation(), 15000);
  }

  private stopLocationPolling() {
    if (this.locationInterval) {
      clearInterval(this.locationInterval);
      this.locationInterval = null;
    }
  }

  async getCurrentLocation() {
    try {
      this.isLocating = true;
      this.locationError = false;
      const position = await this.locationService.getCurrentPosition();
      if (position) {
        this.latitude = position.coords.latitude;
        this.longitude = position.coords.longitude;
        this.accuracy = Math.round(position.coords.accuracy);
        this.lastUpdated = new Date();

        // Dispatch coordinates to backend dispatcher tracking
        this.api.updateLocation(this.latitude, this.longitude).catch((err) => {
          console.warn('[MapPage] Could not sync GPS to backend:', err);
        });
      }
    } catch (e) {
      console.error('Location error:', e);
      this.locationError = true;
      // Set fallback coordinates (Jakarta)
      if (!this.latitude) {
        this.latitude = -6.2088;
        this.longitude = 106.8456;
      }
    } finally {
      this.isLocating = false;
    }
  }

  async loadActiveTask() {
    try {
      const res: any = await this.api.getTasks();
      const tasks = Array.isArray(res) ? res : (res?.data ?? []);
      this.activeTask = tasks.find((t: any) => t.status === 'on-going') || null;
    } catch (e) {
      console.error(e);
    }
  }

  openInMaps() {
    if (this.activeTask?.latitude && this.activeTask?.longitude) {
      this.navigationService.openNavigationOptions(
        this.activeTask.latitude,
        this.activeTask.longitude,
        this.activeTask.title || 'Lokasi Tugas Aktif'
      );
    } else if (this.latitude && this.longitude) {
      this.navigationService.openNavigationOptions(
        this.latitude,
        this.longitude,
        'Posisi Saya Saat Ini'
      );
    }
  }

  navigateToTask() {
    if (this.activeTask) {
      this.router.navigate(['/task-detail', this.activeTask.id]);
    }
  }

  refreshLocation() {
    this.getCurrentLocation();
  }
}
