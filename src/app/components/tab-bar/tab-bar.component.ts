import { Component, Input } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-tab-bar',
  templateUrl: './tab-bar.component.html',
  styleUrls: ['./tab-bar.component.scss'],
  standalone: false,
})
export class TabBarComponent {
  @Input() activeTab: string = 'home';

  tabs = [
    { key: 'home', label: 'Home', icon: 'home-outline', activeIcon: 'home', route: '/dashboard' },
    { key: 'tasks', label: 'Tasks', icon: 'clipboard-outline', activeIcon: 'clipboard', route: '/task-list' },
    { key: 'map', label: 'Map', icon: 'map-outline', activeIcon: 'map', route: '/map' },
    { key: 'history', label: 'History', icon: 'time-outline', activeIcon: 'time', route: '/history' },
    { key: 'profile', label: 'Profile', icon: 'person-outline', activeIcon: 'person', route: '/profile' },
  ];

  constructor(private router: Router) {}

  navigate(tab: any) {
    this.router.navigate([tab.route], { replaceUrl: true });
  }

  getIcon(tab: any): string {
    return this.activeTab === tab.key ? tab.activeIcon : tab.icon;
  }
}
