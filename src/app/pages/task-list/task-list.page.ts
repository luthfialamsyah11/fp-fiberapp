import { Component, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';
import { LocationService } from '../../services/location.service';
import { NavigationService } from '../../services/navigation.service';

@Component({
  selector: 'app-task-list',
  templateUrl: './task-list.page.html',
  styleUrls: ['./task-list.page.scss'],
  standalone: false,
})
export class TaskListPage implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);
  private locationService = inject(LocationService);
  private navigationService = inject(NavigationService);

  user: any = null;
  isOnline = true;
  tasks: any[] = [];
  filteredTasks: any[] = [];
  activeMission: any = null;
  isLoading = true;
  selectedFilter = 'all';
  searchQuery = '';

  filters = [
    { value: 'all', label: 'Semua Tugas' },
    { value: 'assigned', label: 'Tugas Baru' },
    { value: 'accepted', label: 'Diterima' },
    { value: 'on-going', label: 'Dikerjakan' },
  ];

  ngOnInit() {}

  async ionViewWillEnter() {
    await this.loadInitialData();
  }

  async loadInitialData() {
    this.isLoading = true;
    try {
      // 1. Load User Profile & Online status
      try {
        const uRes: any = await this.api.getMe();
        this.user = uRes?.data ?? uRes;
        if (this.user) {
          this.isOnline = this.user.is_online !== undefined ? !!this.user.is_online : true;
          if (this.isOnline) {
            this.locationService.startTracking();
          } else {
            this.locationService.stopTracking();
          }
        }
      } catch (err) {
        console.warn('Could not load user profile in task list', err);
      }

      // 2. Load Tasks
      await this.loadTasks();
    } finally {
      this.isLoading = false;
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

  async loadTasks() {
    try {
      const res: any = await this.api.getTasks();
      this.tasks = Array.isArray(res) ? res : (res?.data ?? []);
      
      // Determine Sticky Active Mission (Prioritize 'on-going', then 'accepted')
      this.activeMission = this.tasks.find(t => t.status === 'on-going') 
        || this.tasks.find(t => t.status === 'accepted') 
        || null;

      this.applyFilter();
    } catch (e) {
      console.error(e);
    }
  }

  applyFilter() {
    let list = this.tasks;
    if (this.selectedFilter !== 'all') {
      list = list.filter(t => t.status === this.selectedFilter);
    }
    if (this.searchQuery.trim()) {
      const q = this.searchQuery.toLowerCase();
      list = list.filter(t =>
        t.title?.toLowerCase().includes(q) ||
        (t.location || t.address || t.resolved_location)?.toLowerCase().includes(q) ||
        t.customer_name?.toLowerCase().includes(q) ||
        t.customer_phone?.toLowerCase().includes(q)
      );
    }
    this.filteredTasks = list;
  }

  setFilter(val: string) {
    this.selectedFilter = val;
    this.applyFilter();
  }

  onSearch(event: any) {
    this.searchQuery = event.target.value;
    this.applyFilter();
  }

  goToDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  openWhatsApp(task: any, event?: Event) {
    if (event) event.stopPropagation();
    if (!task?.customer_phone) return;
    let cleanPhone = task.customer_phone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '62' + cleanPhone.substring(1);
    }
    const customer = task.customer_name || 'Pelanggan';
    const technician = this.user?.name || 'Teknisi FiberOps';
    const text = encodeURIComponent(`Halo Bapak/Ibu ${customer}, saya ${technician} dari tim teknisi FiberOps sedang menuju ke lokasi Anda untuk pengerjaan tiket: ${task.title}.`);
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
      case 'pending': return 'Menunggu';
      case 'assigned': return 'Ditugaskan';
      case 'accepted': return 'Diterima';
      case 'on-going': return 'Dikerjakan';
      case 'completed': return 'Selesai';
      case 'rejected': return 'Ditolak';
      default: return status;
    }
  }

  getPriorityColor(priority: string): string {
    switch (priority) {
      case 'high': return '#DC2626';
      case 'medium': return '#F59E0B';
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
