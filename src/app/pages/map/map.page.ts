import { Component, ElementRef, OnDestroy, ViewChild, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import * as L from 'leaflet';
import { Subscription } from 'rxjs';
import { GpsHealthStatus, LocationService } from '../../services/location.service';
import { ApiService } from '../../services/api.service';
import { NavigationService } from '../../services/navigation.service';

@Component({
  selector: 'app-map',
  templateUrl: './map.page.html',
  styleUrls: ['./map.page.scss'],
  standalone: false,
})
export class MapPage implements OnDestroy {
  @ViewChild('mapContainer') mapContainer?: ElementRef<HTMLDivElement>;

  private locationService = inject(LocationService);
  private api = inject(ApiService);
  private router = inject(Router);
  private navigationService = inject(NavigationService);
  private toastCtrl = inject(ToastController);

  latitude: number | null = null;
  longitude: number | null = null;
  accuracy: number | null = null;
  lastUpdated: Date | null = null;
  isLocating = true;

  gpsStatus: GpsHealthStatus = 'offline';
  gpsStatusMessage = 'Offline';
  pendingOfflineCount = 0;

  tasks: any[] = [];
  activeTask: any = null;
  assignedTasks: any[] = [];
  selectedTask: any = null;
  isSatellite = false;
  private userToggledStyle = false;

  private map: L.Map | null = null;
  private streetLayer: L.TileLayer | null = null;
  private satelliteLayer: L.TileLayer | null = null;
  private techMarker: L.Marker | null = null;
  private accuracyCircle: L.Circle | null = null;
  private taskMarkersLayer: L.LayerGroup | null = null;
  private routeLine: L.Polyline | null = null;

  private locationInterval: any = null;
  private subs = new Subscription();

  constructor() {
    this.subs.add(
      this.locationService.status$.subscribe(s => {
        this.gpsStatus = s;
      })
    );
    this.subs.add(
      this.locationService.statusMessage$.subscribe(m => {
        this.gpsStatusMessage = m;
      })
    );
  }

  get hasGpsError(): boolean {
    return (
      this.gpsStatus === 'no_permission' ||
      this.gpsStatus === 'gps_off' ||
      this.gpsStatus === 'no_network' ||
      this.gpsStatus === 'stale'
    );
  }

  get focusedTask(): any {
    return this.selectedTask || this.activeTask || this.assignedTasks.find(t => t.latitude && t.longitude) || null;
  }

  get distanceToFocusedLabel(): string | null {
    const target = this.focusedTask;
    if (!target?.latitude || !target?.longitude || this.latitude === null || this.longitude === null) {
      return null;
    }
    const meters = this.haversineMeters(this.latitude, this.longitude, Number(target.latitude), Number(target.longitude));
    if (meters < 1000) {
      return `${Math.round(meters)} m`;
    }
    return `${(meters / 1000).toFixed(1)} km`;
  }

  get etaMinutesToFocused(): number | null {
    const target = this.focusedTask;
    if (!target?.latitude || !target?.longitude || this.latitude === null || this.longitude === null) {
      return null;
    }
    const meters = this.haversineMeters(this.latitude, this.longitude, Number(target.latitude), Number(target.longitude));
    // Average urban technician travel speed ~30 km/h (8.33 m/s)
    return Math.max(1, Math.ceil(meters / 8.33 / 60));
  }

  async ionViewWillEnter() {
    this.syncFromLocationService();
    await Promise.all([
      this.loadTasks(),
      this.refreshLocation(false),
      this.refreshOfflineCount(),
    ]);
    this.startLocationPolling();
  }

  ionViewDidEnter() {
    this.initLeafletMap();
    setTimeout(() => {
      this.map?.invalidateSize();
      this.renderMapLayers(true);
    }, 150);
  }

  ionViewDidLeave() {
    this.stopLocationPolling();
  }

  ngOnDestroy() {
    this.stopLocationPolling();
    this.subs.unsubscribe();
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }

  private initLeafletMap() {
    if (this.map || !this.mapContainer?.nativeElement) return;

    const initialLat = this.latitude ?? (this.focusedTask?.latitude ? Number(this.focusedTask.latitude) : -6.2088);
    const initialLng = this.longitude ?? (this.focusedTask?.longitude ? Number(this.focusedTask.longitude) : 106.8456);

    this.map = L.map(this.mapContainer.nativeElement, {
      center: [initialLat, initialLng],
      zoom: 14,
      zoomControl: false,
      attributionControl: false,
    });

    const configuredStyle = (this.api.getClientConfig().map_style || 'openstreetmap').toLowerCase();
    if (!this.userToggledStyle) {
      this.isSatellite = configuredStyle === 'mapbox';
    }

    const baseTileUrl =
      configuredStyle === 'carto'
        ? 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png'
        : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

    this.streetLayer = L.tileLayer(baseTileUrl, {
      maxZoom: 19,
    });
    this.satelliteLayer = L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      { maxZoom: 19 }
    );

    (this.isSatellite ? this.satelliteLayer : this.streetLayer).addTo(this.map);
    this.taskMarkersLayer = L.layerGroup().addTo(this.map);
  }

  toggleMapStyle() {
    this.userToggledStyle = true;
    this.isSatellite = !this.isSatellite;
    if (!this.map || !this.streetLayer || !this.satelliteLayer) return;
    if (this.isSatellite) {
      this.map.removeLayer(this.streetLayer);
      this.satelliteLayer.addTo(this.map);
    } else {
      this.map.removeLayer(this.satelliteLayer);
      this.streetLayer.addTo(this.map);
    }
  }

  private startLocationPolling() {
    this.stopLocationPolling();
    this.locationInterval = setInterval(async () => {
      this.syncFromLocationService();
      await this.refreshOfflineCount();
      this.renderMapLayers(false);
    }, 8000);
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
    }
  }

  async refreshOfflineCount() {
    this.pendingOfflineCount = await this.locationService.getPendingOfflineCount();
  }

  /**
   * Captures a fresh GPS fix AND immediately dispatches it to POST /api/location (fixes MP-2).
   */
  async refreshLocation(showFeedback = true) {
    this.isLocating = true;
    try {
      const position = await this.locationService.forceSyncNow();
      if (position?.coords) {
        this.latitude = position.coords.latitude;
        this.longitude = position.coords.longitude;
        this.accuracy = Math.round(position.coords.accuracy);
        this.lastUpdated = new Date(position.timestamp || Date.now());
      }
      await this.refreshOfflineCount();
      this.renderMapLayers(true);
      if (showFeedback) {
        await this.showToast('GPS position synced with NOC Dispatch.', 'success');
      }
    } catch (e: any) {
      console.warn('[MapPage] GPS sync warning:', e);
      this.syncFromLocationService();
      this.renderMapLayers(false);
      if (showFeedback) {
        const msg = e?.code === 1
          ? 'Location permission denied. Please enable GPS access.'
          : 'Could not acquire fresh GPS fix. Check device location toggle.';
        await this.showToast(msg, 'warning');
      }
    } finally {
      this.isLocating = false;
    }
  }

  async fixGpsIssue() {
    if (this.gpsStatus === 'no_permission') {
      const perm = await this.locationService.ensurePermissions();
      if (perm === 'denied') {
        await this.locationService.openLocationSettings();
        return;
      }
    }
    await this.refreshLocation(true);
  }

  async flushOfflineBuffer() {
    await this.locationService.flushOfflineQueue();
    await this.refreshOfflineCount();
    await this.showToast(
      this.pendingOfflineCount === 0
        ? 'All buffered offline GPS points synchronized.'
        : `${this.pendingOfflineCount} point(s) still waiting for network.`,
      this.pendingOfflineCount === 0 ? 'success' : 'warning'
    );
  }

  async loadTasks() {
    try {
      const res: any = await this.api.getTasks();
      const list = Array.isArray(res) ? res : (res?.data ?? []);
      this.tasks = list;

      this.activeTask = list.find((t: any) => t.status === 'on-going')
        || list.find((t: any) => t.status === 'accepted')
        || null;

      this.assignedTasks = list.filter((t: any) => t.status === 'assigned');
      this.api.setAssignedCount(this.assignedTasks.length);
      this.locationService.setActiveTaskId(this.activeTask?.id || null);

      if (this.selectedTask) {
        this.selectedTask = list.find((t: any) => t.id === this.selectedTask.id) || null;
      }
      this.renderMapLayers(false);
    } catch (e) {
      console.error(e);
    }
  }

  private renderMapLayers(fitBounds: boolean) {
    if (!this.map || !this.taskMarkersLayer) return;

    const boundsPoints: L.LatLngExpression[] = [];

    // 1. Live Technician Marker & Accuracy Circle
    if (this.latitude !== null && this.longitude !== null) {
      const techLatLng: L.LatLngExpression = [this.latitude, this.longitude];
      boundsPoints.push(techLatLng);

      const techIcon = L.divIcon({
        className: 'leaflet-tech-pin',
        html: `<div style="position:relative;width:22px;height:22px;display:flex;align-items:center;justify-content:center;">
                 <div style="position:absolute;width:36px;height:36px;border-radius:50%;background:rgba(37,99,235,0.22);"></div>
                 <div style="width:16px;height:16px;border-radius:50%;background:#2563EB;border:3px solid #FFFFFF;box-shadow:0 2px 6px rgba(37,99,235,0.5);position:relative;z-index:2;"></div>
               </div>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
      });

      if (this.techMarker) {
        this.techMarker.setLatLng(techLatLng);
      } else {
        this.techMarker = L.marker(techLatLng, { icon: techIcon, zIndexOffset: 1000 })
          .addTo(this.map)
          .bindPopup('<b>Your Live GPS</b>');
      }

      if (this.accuracy && this.accuracy > 5) {
        const isLow = this.accuracy > 250;
        const cColor = isLow ? '#D97706' : '#2563EB';
        if (this.accuracyCircle) {
          this.accuracyCircle.setLatLng(techLatLng);
          this.accuracyCircle.setRadius(Math.min(this.accuracy, 1000));
          this.accuracyCircle.setStyle({ color: cColor, fillColor: cColor, dashArray: isLow ? '4,4' : undefined });
        } else {
          this.accuracyCircle = L.circle(techLatLng, {
            radius: Math.min(this.accuracy, 1000),
            color: cColor,
            fillColor: cColor,
            fillOpacity: 0.12,
            weight: 1.5,
            dashArray: isLow ? '4,4' : undefined,
          }).addTo(this.map);
        }
      }
    }

    // 2. Active & Assigned Work Order Destination Pins
    this.taskMarkersLayer.clearLayers();
    const focused = this.focusedTask;

    for (const task of this.tasks) {
      if (!task.latitude || !task.longitude) continue;
      const lat = Number(task.latitude);
      const lng = Number(task.longitude);
      if (Math.abs(lat) < 0.0001 && Math.abs(lng) < 0.0001) continue;

      const isFocused = focused && focused.id === task.id;
      const isActive = this.activeTask && this.activeTask.id === task.id;
      const pinColor = isActive ? '#16A34A' : (task.priority === 'high' ? '#DC2626' : '#D97706');
      const size = isFocused ? 30 : 24;

      const pinIcon = L.divIcon({
        className: 'leaflet-task-pin',
        html: `<div style="width:${size}px;height:${size}px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:${pinColor};border:2.5px solid #FFFFFF;box-shadow:0 3px 8px rgba(15,23,42,0.35);display:flex;align-items:center;justify-content:center;">
                 <div style="width:7px;height:7px;border-radius:50%;background:#FFFFFF;transform:rotate(45deg);"></div>
               </div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
      });

      const marker = L.marker([lat, lng], { icon: pinIcon }).addTo(this.taskMarkersLayer);
      marker.on('click', () => {
        this.selectTaskOnMap(task);
      });

      if (isFocused || isActive) {
        boundsPoints.push([lat, lng]);
      }
    }

    // 3. Route Line from Technician to Focused Destination
    if (this.routeLine) {
      this.map.removeLayer(this.routeLine);
      this.routeLine = null;
    }
    if (
      this.latitude !== null &&
      this.longitude !== null &&
      focused?.latitude &&
      focused?.longitude
    ) {
      this.routeLine = L.polyline(
        [
          [this.latitude, this.longitude],
          [Number(focused.latitude), Number(focused.longitude)],
        ],
        {
          color: '#2563EB',
          weight: 3.5,
          opacity: 0.85,
          dashArray: '7, 7',
        }
      ).addTo(this.map);
    }

    if (fitBounds && boundsPoints.length > 0) {
      if (boundsPoints.length === 1) {
        this.map.setView(boundsPoints[0], 15, { animate: true });
      } else {
        this.map.fitBounds(L.latLngBounds(boundsPoints), { padding: [44, 44], maxZoom: 16 });
      }
    }
  }

  selectTaskOnMap(task: any) {
    this.selectedTask = task;
    this.renderMapLayers(true);
  }

  centerOnMe() {
    if (this.map && this.latitude !== null && this.longitude !== null) {
      this.map.setView([this.latitude, this.longitude], 16, { animate: true });
    } else {
      this.refreshLocation(true);
    }
  }

  fitAllMarkers() {
    this.renderMapLayers(true);
  }

  async openInMaps(taskOverride?: any) {
    const target = taskOverride || this.focusedTask;
    if (target?.latitude && target?.longitude) {
      await this.navigationService.openNavigationOptions(
        Number(target.latitude),
        Number(target.longitude),
        target.title || `Work Order #${target.id}`
      );
      return;
    }
    if (this.latitude !== null && this.longitude !== null) {
      await this.navigationService.openNavigationOptions(
        this.latitude,
        this.longitude,
        'My Current Location'
      );
      return;
    }
    await this.showToast('Waiting for GPS coordinates. Tap refresh to acquire location.', 'warning');
  }

  navigateToTask(taskOverride?: any) {
    const target = taskOverride || this.focusedTask;
    if (target?.id) {
      this.router.navigate(['/task-detail', target.id]);
    }
  }

  getTaskDistanceLabel(task: any): string | null {
    if (!task?.latitude || !task?.longitude || this.latitude === null || this.longitude === null) {
      return null;
    }
    const m = this.haversineMeters(this.latitude, this.longitude, Number(task.latitude), Number(task.longitude));
    return m < 1000 ? `${Math.round(m)} m` : `${(m / 1000).toFixed(1)} km`;
  }

  formatTaskCode(id: number): string {
    return `#TSK-${String(id).padStart(4, '0')}`;
  }

  private haversineMeters(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const R = 6371000;
    const toRad = (d: number) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    const a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a)));
  }

  async doRefresh(event: any) {
    await Promise.all([this.loadTasks(), this.refreshLocation(false), this.refreshOfflineCount()]);
    event.target.complete();
  }

  private async showToast(message: string, color: 'success' | 'warning' | 'danger' = 'success') {
    const toast = await this.toastCtrl.create({
      message,
      duration: 2400,
      color,
      position: 'top',
    });
    await toast.present();
  }
}
