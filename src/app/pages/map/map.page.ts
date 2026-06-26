import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { Router } from '@angular/router';
import { LocationService } from '../../services/location.service';
import { ApiService } from '../../services/api.service';

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

  latitude: number | null = null;
  longitude: number | null = null;
  accuracy: number | null = null;
  lastUpdated: Date | null = null;
  isLocating = true;
  activeTask: any = null;
  locationError = false;
  private locationInterval: any;

  async ngOnInit() {
    await this.getCurrentLocation();
    this.locationInterval = setInterval(() => this.getCurrentLocation(), 15000);
  }

  async ionViewWillEnter() {
    await this.loadActiveTask();
  }

  ngOnDestroy() {
    if (this.locationInterval) {
      clearInterval(this.locationInterval);
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
      const url = `https://www.google.com/maps/dir/?api=1&destination=${this.activeTask.latitude},${this.activeTask.longitude}`;
      window.open(url, '_system');
    } else if (this.latitude && this.longitude) {
      const url = `https://www.google.com/maps/@${this.latitude},${this.longitude},17z`;
      window.open(url, '_system');
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
