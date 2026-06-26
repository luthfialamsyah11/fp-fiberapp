import { Component, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.page.html',
  styleUrls: ['./history.page.scss'],
  standalone: false,
})
export class HistoryPage implements OnInit {
  private api = inject(ApiService);
  private router = inject(Router);

  histories: any[] = [];
  isLoading = true;

  ngOnInit() {}

  async ionViewWillEnter() {
    await this.loadHistory();
  }

  async loadHistory() {
    this.isLoading = true;
    try {
      const res: any = await this.api.getHistory();
      this.histories = Array.isArray(res) ? res : (res?.data ?? []);
    } catch (e) {
      console.error(e);
    } finally {
      this.isLoading = false;
    }
  }

  goToDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  doRefresh(event: any) {
    this.loadHistory().then(() => event.target.complete());
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'completed': return 'success';
      case 'rejected': return 'danger';
      case 'on-going': return 'tertiary';
      default: return 'medium';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'completed': return 'Selesai';
      case 'rejected': return 'Ditolak';
      case 'on-going': return 'Dikerjakan';
      default: return status;
    }
  }

  getStatusIcon(status: string): string {
    switch (status) {
      case 'completed': return 'checkmark-circle';
      case 'rejected': return 'close-circle';
      default: return 'time';
    }
  }

  countByStatus(status: string): number {
    return this.histories.filter(h => h.status === status).length;
  }
}
