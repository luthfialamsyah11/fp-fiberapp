import { Component, OnDestroy, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { Subscription } from 'rxjs';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { GpsHealthStatus, LocationService } from '../../services/location.service';
import { NavigationService } from '../../services/navigation.service';
import { OfflineQueueService } from '../../services/offline-queue.service';
import { TaskSyncService } from '../../services/task-sync.service';
import { formatPhoneForWhatsApp } from '../../utils/phone';

@Component({
  selector: 'app-task-list',
  templateUrl: './task-list.page.html',
  styleUrls: ['./task-list.page.scss'],
  standalone: false,
})
export class TaskListPage implements OnDestroy {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private router = inject(Router);
  private toastCtrl = inject(ToastController);
  private locationService = inject(LocationService);
  private navigationService = inject(NavigationService);
  private offlineQueue = inject(OfflineQueueService);
  private taskSync = inject(TaskSyncService);

  user: any = null;
  isOnline = true;
  isDemoMode = false;
  pendingOfflineMutations = 0;
  isFlushingOffline = false;
  gpsStatus: GpsHealthStatus = 'offline';
  gpsStatusMessage = 'Offline';
  gpsAccuracy: number | null = null;
  lastSyncTime = '';
  isSyncing = false;

  tasks: any[] = [];
  filteredTasks: any[] = [];
  activeMission: any = null;
  newTaskAlert: any = null;
  completedCount = 0;
  isLoading = true;
  selectedFilter = 'all';
  searchQuery = '';

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
      this.offlineQueue.pendingMutationsCount$.subscribe(count => {
        this.pendingOfflineMutations = count;
      })
    );
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
    this.subs.add(
      this.taskSync.tasks$.subscribe(list => {
        this.applySyncedTasks(list);
      })
    );
    this.subs.add(
      this.taskSync.lastSyncTime$.subscribe(t => {
        if (t) this.lastSyncTime = t;
      })
    );
    this.subs.add(
      this.taskSync.newTaskAlert$.subscribe(task => {
        this.newTaskAlert = task;
      })
    );
  }

  async flushOfflineWorkOrders() {
    if (this.isFlushingOffline) return;
    this.isFlushingOffline = true;
    try {
      const res = await this.api.flushOfflineMutations();
      if (res.synced > 0) {
        const toast = await this.toastCtrl.create({
          message: `Synced ${res.synced} offline field action(s) to NOC.`,
          duration: 3000,
          color: 'success',
          position: 'top',
        });
        await toast.present();
        this.taskSync.refreshTasks(false);
      }
    } finally {
      this.isFlushingOffline = false;
    }
  }

  async ionViewWillEnter() {
    this.isDemoMode = await this.api.isDemoMode();
    this.taskSync.start();
    await this.loadInitialData();
  }

  ngOnDestroy() {
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
        const toast = await this.toastCtrl.create({
          message: 'Location permission is blocked. Enable Location in browser/device settings, then tap retry.',
          duration: 4000,
          position: 'top',
          color: 'warning',
        });
        await toast.present();
        return;
      }
    }

    try {
      const pos = await this.locationService.forceSyncNow();
      await this.locationService.stopTracking();
      await this.locationService.startTracking();
      this.refreshGpsAccuracy();
      const accLabel = pos?.coords?.accuracy ? ` (±${Math.round(pos.coords.accuracy)}m)` : '';
      const toast = await this.toastCtrl.create({
        message: `GPS locked & synced with NOC${accLabel}`,
        duration: 2400,
        position: 'top',
        color: 'success',
      });
      await toast.present();
    } catch (err: any) {
      const code = err?.code;
      const msg = code === 1
        ? 'Browser/OS blocked Location access. Click the lock icon near the URL bar and set Location to Allow.'
        : 'Device could not obtain a GPS/Wi-Fi location fix. Enable Windows/Android Location Services and retry.';
      const toast = await this.toastCtrl.create({
        message: msg,
        duration: 4500,
        position: 'top',
        color: 'warning',
      });
      await toast.present();
    }
  }

  async exitDemoMode() {
    await this.auth.logout();
  }

  openNewTaskAlert() {
    if (!this.newTaskAlert) return;
    const id = this.newTaskAlert.id;
    this.taskSync.dismissNewTaskAlert();
    this.goToDetail(id);
  }

  dismissNewTaskAlert(event?: Event) {
    if (event) event.stopPropagation();
    this.taskSync.dismissNewTaskAlert();
  }

  private applySyncedTasks(nextTasks: any[]) {
    this.tasks = Array.isArray(nextTasks) ? nextTasks : [];
    this.refreshGpsAccuracy();

    this.activeMission = this.tasks.find(t => t.status === 'on-going')
      || this.tasks.find(t => t.status === 'accepted')
      || null;

    this.applyFilter();
  }

  async loadInitialData() {
    this.isLoading = this.tasks.length === 0;
    try {
      const [meRes, historyRes] = await Promise.all([
        this.api.getMe().catch((err) => {
          console.warn('Could not load user profile in task list', err);
          return null;
        }),
        this.api.getHistory().catch(() => null),
        this.taskSync.refreshTasks(false),
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
      // True bidirectional sync: push offline mutations & buffered GPS first, then pull latest tasks from NOC
      const [flushRes] = await Promise.all([
        this.api.flushOfflineMutations().catch(() => ({ synced: 0, remaining: 0 })),
        this.locationService.flushOfflineQueue().catch(() => {}),
      ]);
      await this.taskSync.refreshTasks(true);
      this.refreshGpsAccuracy();
      const summary = flushRes.synced > 0
        ? `Synced ${flushRes.synced} offline action(s) & refreshed work orders`
        : (flushRes.remaining > 0
          ? `Work orders refreshed (${flushRes.remaining} offline item(s) still awaiting connection/auth)`
          : 'Work orders & telemetry synced');
      const toast = await this.toastCtrl.create({
        message: summary,
        duration: 2400,
        position: 'top',
        color: flushRes.synced > 0 ? 'success' : 'dark',
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
    await this.taskSync.refreshTasks(isBackgroundPoll);
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
