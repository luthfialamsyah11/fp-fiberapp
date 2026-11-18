import { Component, OnDestroy, inject } from '@angular/core';
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
export class MapPage implements OnDestroy {
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

  async ionViewWillEnter() {
    await Promise.all([this.loadActiveTask(), this.getCurrentLocation()]);
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
    this.locationInterval = setInterval(() => this.syncFromLocationService(), 10000);
  }

  private stopLocationPolling() {
    if (this.locationInterval) {
      clearInterval(this.locationInterval);
      this.locationInterval = null;
    }
  }

  private syncFromLocationService() {
    const cached = this.locationService.getLastKnownPosition();
    if (cached?.coords) {
      this.latitude = cached.coords.latitude;
      this.longitude = cached.coords.longitude;
      this.accuracy = Math.round(cached.coords.accuracy);
      this.lastUpdated = new Date(cached.timestamp || Date.now());
      this.locationError = false;
    }
  }

  async getCurrentLocation() {
    try {
      this.isLocating = true;
      this.locationError = false;
      const position = await this.locationService.getCurrentPosition();
      if (position?.coords) {
        this.latitude = position.coords.latitude;
        this.longitude = position.coords.longitude;
        this.accuracy = Math.round(position.coords.accuracy);
        this.lastUpdated = new Date(position.timestamp || Date.now());
      }
    } catch (e) {
      console.warn('GPS signal unavailable:', e);
      this.locationError = true;
      this.syncFromLocationService();
    } finally {
      this.isLocating = false;
    }
  }

  async loadActiveTask() {
    try {
      const res: any = await this.api.getTasks();
      const tasks = Array.isArray(res) ? res : (res?.data ?? []);
      this.activeTask = tasks.find((t: any) => t.status === 'on-going')
        || tasks.find((t: any) => t.status === 'accepted')
        || null;
      this.locationService.setActiveTaskId(this.activeTask?.id || null);
    } catch (e) {
      console.error(e);
    }
  }

  openInMaps() {
    if (this.activeTask?.latitude && this.activeTask?.longitude) {
      this.navigationService.openNavigationOptions(
        this.activeTask.latitude,
        this.activeTask.longitude,
        this.activeTask.title || 'Active Task Location'
      );
    } else if (this.latitude && this.longitude) {
      this.navigationService.openNavigationOptions(
        this.latitude,
        this.longitude,
        'My Current Location'
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
