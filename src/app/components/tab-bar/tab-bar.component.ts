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
    { key: 'tasks', label: 'Tugas', icon: 'checkbox-outline', activeIcon: 'checkbox', route: '/task-list' },
    { key: 'map', label: 'Peta Rute', icon: 'map-outline', activeIcon: 'map', route: '/map' },
    { key: 'profile', label: 'Akun & Riwayat', icon: 'person-outline', activeIcon: 'person', route: '/profile' },
  ];

  constructor(private router: Router) {}

  navigate(tab: any) {
    this.router.navigate([tab.route], { replaceUrl: true });
  }

  getIcon(tab: any): string {
    return this.activeTab === tab.key ? tab.activeIcon : tab.icon;
  }
}
