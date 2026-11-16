import { Component, Input, inject } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-tab-bar',
  templateUrl: './tab-bar.component.html',
  styleUrls: ['./tab-bar.component.scss'],
  standalone: false,
})
export class TabBarComponent {
  private router = inject(Router);
  @Input() activeTab: string = 'home';

  tabs = [
    { key: 'tasks', label: 'Work Orders', icon: 'checkbox-outline', activeIcon: 'checkbox', route: '/task-list' },
    { key: 'map', label: 'Route Map', icon: 'map-outline', activeIcon: 'map', route: '/map' },
    { key: 'profile', label: 'Profile', icon: 'person-outline', activeIcon: 'person', route: '/profile' },
  ];

  navigate(tab: any) {
    this.router.navigate([tab.route], { replaceUrl: true });
  }

  getIcon(tab: any): string {
    return this.activeTab === tab.key ? tab.activeIcon : tab.icon;
  }
}
