import { Component, OnDestroy, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { GpsHealthStatus, LocationService } from '../../services/location.service';
import { NavigationService } from '../../services/navigation.service';
import { formatPhoneForWhatsApp } from '../../utils/phone';

@Component({
  selector: 'app-task-list',
  templateUrl: './task-list.page.html',
  styleUrls: ['./task-list.page.scss'],
  standalone: false,
})
export class TaskListPage implements OnDestroy {
  private api = inject(ApiService);
  private router = inject(Router);
  private toastCtrl = inject(ToastController);
  private locationService = inject(LocationService);
  private navigationService = inject(NavigationService);

  user: any = null;
  isOnline = true;
  gpsStatus: GpsHealthStatus = 'offline';
  gpsStatusMessage = 'Offline';
  gpsAccuracy: number | null = null;
  lastSyncTime = '';
  isSyncing = false;

  tasks: any[] = [];
  filteredTasks: any[] = [];
  activeMission: any = null;
  completedCount = 0;
  isLoading = true;
  selectedFilter = 'all';
  searchQuery = '';

  private syncTimer: any = null;
  private knownAssignedIds = new Set<number>();
  private hasLoadedOnce = false;
  private subs = new Subscription();

  filters = [
    { value: 'all', label: 'All' },
    { value: 'assigned', label: 'Assigned' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'on-going', label: 'In Progress' },
    { value: 'urgent', label: 'Urgent' },
  ];

  constructor() {
    this.subs.add(
      this.locationService.status$.subscribe(s => {
        this.gpsStatus = s;
        this.refreshGpsAccuracy();
      })
    );
    this.subs.add(
      this.locationService.statusMessage$.subscribe(m => {
        this.gpsStatusMessage = m;
        this.refreshGpsAccuracy();
      })
    );
  }

  async ionViewWillEnter() {
    await this.loadInitialData();
    this.startForegroundSync();
  }

  ionViewDidLeave() {
    this.stopForegroundSync();
  }

  ngOnDestroy() {
    this.stopForegroundSync();
    this.subs.unsubscribe();
  }

  get greetingText(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  }

  get userInitials(): string {
    const name = String(this.user?.name || 'FT').trim();
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
      return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase() || 'FT';
  }

  get assignedCount(): number {
    return this.tasks.filter(t => t.status === 'assigned').length;
  }

  get inProgressCount(): number {
    return this.tasks.filter(t => t.status === 'on-going' || t.status === 'accepted').length;
  }

  get urgentCount(): number {
    return this.tasks.filter(t => t.priority === 'high').length;
  }

  getFilterCount(value: string): number {
    switch (value) {
      case 'all': return this.tasks.length;
      case 'urgent': return this.urgentCount;
      default: return this.tasks.filter(t => t.status === value).length;
    }
  }

  get hasGpsWarning(): boolean {
    return (
      this.isOnline &&
      (this.gpsStatus === 'no_permission' ||
        this.gpsStatus === 'gps_off' ||
        this.gpsStatus === 'no_network' ||
        this.gpsStatus === 'stale')
    );
  }

  private refreshGpsAccuracy() {
    const pos = this.locationService.getLastKnownPosition();
    this.gpsAccuracy = pos?.coords?.accuracy ? Math.round(pos.coords.accuracy) : null;
  }

  async fixGpsIssue() {
    if (this.gpsStatus === 'no_permission') {
      const perm = await this.locationService.ensurePermissions();
      if (perm === 'denied') {
        await this.locationService.openLocationSettings();
        return;
      }
    }
    await this.locationService.stopTracking();
    await this.locationService.startTracking();
  }

  private startForegroundSync() {
    this.stopForegroundSync();
    this.syncTimer = setInterval(() => {
      if (typeof document !== 'undefined' && document.hidden) return;
      this.loadTasks(true);
    }, 15000);
  }

  private stopForegroundSync() {
    if (this.syncTimer) {
      clearInterval(this.syncTimer);
      this.syncTimer = null;
    }
  }

  async loadInitialData() {
    this.isLoading = true;
    try {
      const [meRes, historyRes] = await Promise.all([
        this.api.getMe().catch((err) => {
          console.warn('Could not load user profile in task list', err);
          return null;
        }),
        this.api.getHistory().catch(() => null),
        this.loadTasks(),
      ]);

      const uRes: any = meRes;
      this.user = uRes?.data ?? uRes;
      if (this.user) {
        this.isOnline = this.user.is_online !== undefined ? !!this.user.is_online : true;
        if (this.isOnline) {
          this.locationService.startTracking();
        } else {
          this.locationService.stopTracking();
        }
      }

      const hList = Array.isArray(historyRes) ? historyRes : ((historyRes as any)?.data ?? []);
      this.completedCount = Array.isArray(hList) ? hList.length : 0;
    } finally {
      this.isLoading = false;
    }
  }

  async manualSync() {
    if (this.isSyncing) return;
    this.isSyncing = true;
    try {
      await this.loadTasks(false);
      this.refreshGpsAccuracy();
      const toast = await this.toastCtrl.create({
        message: 'Work orders & telemetry synced',
        duration: 1800,
        position: 'top',
        color: 'dark',
      });
      await toast.present();
    } finally {
      this.isSyncing = false;
    }
  }

  async toggleOnlineStatus() {
    const nextStatus = !this.isOnline;
    this.isOnline = nextStatus;

    if (nextStatus) {
      this.locationService.startTracking();
    } else {
      this.locationService.stopTracking();
    }

    try {
      await this.api.updateProfile({ is_online: nextStatus });
    } catch (e) {
      console.error('Failed to toggle status:', e);
      this.isOnline = !nextStatus;
      if (this.isOnline) {
        this.locationService.startTracking();
      } else {
        this.locationService.stopTracking();
      }
    }
  }

  async loadTasks(isBackgroundPoll = false) {
    try {
      const res: any = await this.api.getTasks();
      const nextTasks = Array.isArray(res) ? res : (res?.data ?? []);
      const assignedNow = nextTasks.filter((t: any) => t.status === 'assigned');

      if (isBackgroundPoll && this.hasLoadedOnce) {
        const newlyDispatched = assignedNow.filter((t: any) => !this.knownAssignedIds.has(Number(t.id)));
        if (newlyDispatched.length > 0) {
          const first = newlyDispatched[0];
          const code = `#TSK-${String(first.id).padStart(4, '0')}`;
          const msg = newlyDispatched.length === 1
            ? `New work order assigned: ${code} — ${first.title}`
            : `${newlyDispatched.length} new work orders dispatched to you!`;
          this.showDispatchToast(msg);
        }
      }

      this.knownAssignedIds = new Set(assignedNow.map((t: any) => Number(t.id)));
      this.api.setAssignedCount(assignedNow.length);
      this.hasLoadedOnce = true;
      this.tasks = nextTasks;

      const now = new Date();
      this.lastSyncTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      this.refreshGpsAccuracy();

      this.activeMission = this.tasks.find(t => t.status === 'on-going')
        || this.tasks.find(t => t.status === 'accepted')
        || null;

      this.locationService.setActiveTaskId(this.activeMission?.id || null);

      this.applyFilter();
    } catch (e) {
      console.error(e);
    }
  }

  private async showDispatchToast(message: string) {
    const toast = await this.toastCtrl.create({
      message,
      duration: 4500,
      position: 'top',
      color: 'primary',
      icon: 'notifications-outline',
    });
    await toast.present();
  }

  applyFilter() {
    let list = this.tasks;
    if (this.selectedFilter === 'urgent') {
      list = list.filter(t => t.priority === 'high');
    } else if (this.selectedFilter !== 'all') {
      list = list.filter(t => t.status === this.selectedFilter);
    }
    if (this.searchQuery.trim()) {
      const q = this.searchQuery.trim().toLowerCase();
      list = list.filter(t => {
        const code = `#tsk-${String(t.id).padStart(4, '0')}`;
        return (
          code.includes(q) ||
          String(t.id).includes(q) ||
          t.title?.toLowerCase().includes(q) ||
          (t.location || t.address || t.resolved_location)?.toLowerCase().includes(q) ||
          t.customer_name?.toLowerCase().includes(q) ||
          t.customer_phone?.toLowerCase().includes(q)
        );
      });
    }
    this.filteredTasks = list;
  }

  setFilter(val: string) {
    this.selectedFilter = val;
    this.applyFilter();
  }

  clearSearch() {
    this.searchQuery = '';
    this.applyFilter();
  }

  resetFilters() {
    this.selectedFilter = 'all';
    this.searchQuery = '';
    this.applyFilter();
  }

  onSearch(event: any) {
    this.searchQuery = event.target.value || '';
    this.applyFilter();
  }

  goToDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  goToMap() {
    this.router.navigate(['/map'], { replaceUrl: true });
  }

  goToProfile() {
    this.router.navigate(['/profile'], { replaceUrl: true });
  }

  formatTaskCode(id: number): string {
    return `#TSK-${String(id).padStart(4, '0')}`;
  }

  openWhatsApp(task: any, event?: Event) {
    if (event) event.stopPropagation();
    if (!task?.customer_phone) return;
    const countryCode = this.api.getClientConfig().default_country_code || '62';
    const cleanPhone = formatPhoneForWhatsApp(task.customer_phone, countryCode);
    if (!cleanPhone) return;

    const customer = task.customer_name || 'Customer';
    const technician = this.user?.name || 'FiberOps Field Tech';
    const trackUrl = task.tracking_url
      || (task.tracking_token ? `${this.api.getBaseUrl()}/track/${task.tracking_token}` : '');
    const trackingMsg = trackUrl ? ` Live tracking: ${trackUrl}` : '';
    const text = encodeURIComponent(
      `Hello ${customer}, I am ${technician} from FiberOps field team. I am assigned to handle your work order #${task.id}.${trackingMsg}`
    );
    window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
  }

  callCustomer(task: any, event?: Event) {
    if (event) event.stopPropagation();
    if (!task?.customer_phone) return;
    window.open(`tel:${task.customer_phone}`, '_system');
  }

  openNavigation(task: any, event?: Event) {
    if (event) event.stopPropagation();
    if (task?.latitude && task?.longitude) {
      this.navigationService.openNavigationOptions(task.latitude, task.longitude, task.title);
    }
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'pending': return 'warning';
      case 'assigned': return 'primary';
      case 'accepted': return 'success';
      case 'on-going': return 'tertiary';
      case 'completed': return 'success';
      case 'rejected': return 'danger';
      default: return 'medium';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'pending': return 'Pending';
      case 'assigned': return 'Assigned';
      case 'accepted': return 'En Route';
      case 'on-going': return 'On-Site';
      case 'completed': return 'Completed';
      case 'rejected': return 'Declined';
      default: return status;
    }
  }

  getPriorityColor(priority: string): string {
    switch (priority) {
      case 'high': return '#DC2626';
      case 'medium': return '#D97706';
      case 'low': return '#16A34A';
      default: return '#94A3B8';
    }
  }

  trackByTaskId(index: number, task: any): number {
    return task.id;
  }

  doRefresh(event: any) {
    this.loadInitialData().then(() => event.target.complete());
  }
}
