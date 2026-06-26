import { Component, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-task-list',
  templateUrl: './task-list.page.html',
  styleUrls: ['./task-list.page.scss'],
  standalone: false,
})
export class TaskListPage implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);

  tasks: any[] = [];
  filteredTasks: any[] = [];
  isLoading = true;
  selectedFilter = 'all';
  searchQuery = '';

  filters = [
    { value: 'all', label: 'Semua' },
    { value: 'assigned', label: 'Ditugaskan' },
    { value: 'accepted', label: 'Diterima' },
    { value: 'on-going', label: 'Dikerjakan' },
  ];

  ngOnInit() {}

  async ionViewWillEnter() {
    await this.loadTasks();
  }

  async loadTasks() {
    this.isLoading = true;
    try {
      const res: any = await this.api.getTasks();
      this.tasks = Array.isArray(res) ? res : (res?.data ?? []);
      this.applyFilter();
    } catch (e) {
      console.error(e);
    } finally {
      this.isLoading = false;
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
        t.location?.toLowerCase().includes(q) ||
        t.customer_name?.toLowerCase().includes(q)
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

  doRefresh(event: any) {
    this.loadTasks().then(() => event.target.complete());
  }
}
