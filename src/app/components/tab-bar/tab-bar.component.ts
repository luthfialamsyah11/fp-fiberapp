import { Component, Input, OnDestroy, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { GpsHealthStatus, LocationService } from '../../services/location.service';

@Component({
  selector: 'app-tab-bar',
  templateUrl: './tab-bar.component.html',
  styleUrls: ['./tab-bar.component.scss'],
  standalone: false,
})
export class TabBarComponent implements OnInit, OnDestroy {
  private router = inject(Router);
  private api = inject(ApiService);
  private locationService = inject(LocationService);

  @Input() activeTab: string = 'tasks';

  assignedCount = 0;
  gpsWarning = false;
  private subs = new Subscription();

  tabs = [
    { key: 'tasks', label: 'Work Orders', icon: 'checkbox-outline', activeIcon: 'checkbox', route: '/task-list' },
    { key: 'map', label: 'Route Map', icon: 'map-outline', activeIcon: 'map', route: '/map' },
    { key: 'profile', label: 'Profile', icon: 'person-outline', activeIcon: 'person', route: '/profile' },
  ];

  ngOnInit() {
    this.subs.add(
      this.api.assignedCount$.subscribe(count => {
        this.assignedCount = count;
      })
    );
    this.subs.add(
      this.locationService.status$.subscribe((status: GpsHealthStatus) => {
        this.gpsWarning = status === 'no_permission' || status === 'gps_off' || status === 'no_network' || status === 'stale';
      })
    );
  }

  ngOnDestroy() {
    this.subs.unsubscribe();
  }

  navigate(tab: any) {
    this.router.navigate([tab.route], { replaceUrl: true });
  }

  getIcon(tab: any): string {
    return this.activeTab === tab.key ? tab.activeIcon : tab.icon;
  }
}
