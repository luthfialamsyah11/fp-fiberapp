import { Component, OnInit, inject } from '@angular/core';
import { Router } from '@angular/router';
import { AlertController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';
import { AuthService } from '../../services/auth.service';
import { LocationService } from '../../services/location.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.page.html',
  styleUrls: ['./dashboard.page.scss'],
  standalone: false,
})
export class DashboardPage implements OnInit {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private router = inject(Router);
  private alertCtrl = inject(AlertController);
  private locationService = inject(LocationService);

  user: any = null;
  tasks: any[] = [];
  isLoading = true;
  isOnline = true;

  async toggleOnlineStatus() {
    const newStatus = !this.isOnline;
    this.isOnline = newStatus;

    if (newStatus) {
      this.locationService.startTracking();
    } else {
      this.locationService.stopTracking();
    }

    try {
      await this.api.updateProfile({ is_online: newStatus });
    } catch (e) {
      console.error('Failed to update online status', e);
      // Revert status and tracking state
      this.isOnline = !newStatus;
      if (this.isOnline) {
        this.locationService.startTracking();
      } else {
        this.locationService.stopTracking();
      }
    }
  }

  get pendingTasks() {
    return this.tasks.filter(t => t.status === 'pending' || t.status === 'assigned' || t.status === 'accepted').length;
  }

  get inProgressTasks() {
    return this.tasks.filter(t => t.status === 'on-going').length;
  }

  get completedTasks() {
    return this.tasks.filter(t => t.status === 'completed').length;
  }

  get recentTasks() {
    return this.tasks.slice(0, 5);
  }

  get activeTask() {
    return this.tasks.find(t => t.status === 'on-going') || null;
  }

  get nextTask() {
    return this.tasks.find(t => t.status === 'assigned' || t.status === 'accepted' || t.status === 'pending') || null;
  }

  async ngOnInit() {}

  async ionViewWillEnter() {
    await this.loadData();
  }

  async loadData() {
    this.isLoading = true;
    try {
      // Load user profile
      try {
        const user: any = await this.api.getMe();
        this.user = user?.data ?? user;
        if (this.user) {
          this.isOnline = this.user.is_online !== undefined ? !!this.user.is_online : true;
          if (this.isOnline) {
            this.locationService.startTracking();
          } else {
            this.locationService.stopTracking();
          }
        }
      } catch (userError) {
        console.error('Failed to load user profile in dashboard', userError);
      }

      // Load tasks
      try {
        const tasks: any = await this.api.getTasks();
        this.tasks = Array.isArray(tasks) ? tasks : (tasks?.data ?? []);
      } catch (tasksError) {
        console.error('Failed to load tasks in dashboard', tasksError);
      }
    } catch (e) {
      console.error('Unexpected error in loadData', e);
    } finally {
      this.isLoading = false;
    }
  }

  getInitial(): string {
    const name = this.user?.name || 'T';
    return name.charAt(0).toUpperCase();
  }

  getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat Pagi 👋';
    if (hour < 15) return 'Selamat Siang 👋';
    if (hour < 18) return 'Selamat Sore 👋';
    return 'Selamat Malam 👋';
  }

  goToTaskList() {
    this.router.navigate(['/task-list']);
  }

  goToHistory() {
    this.router.navigate(['/history']);
  }

  goToProfile() {
    this.router.navigate(['/profile']);
  }

  goToMap() {
    this.router.navigate(['/map']);
  }

  goToTaskDetail(id: number) {
    this.router.navigate(['/task-detail', id]);
  }

  getStatusColor(status: string): string {
    switch (status) {
      case 'pending':
      case 'assigned': return 'warning';
      case 'on-going': return 'primary';
      case 'completed': return 'success';
      case 'rejected': return 'danger';
      default: return 'medium';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'pending': return 'Menunggu';
      case 'assigned': return 'Ditugaskan';
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
      default: return '#64748B';
    }
  }

  getPriorityLabel(priority: string): string {
    switch (priority) {
      case 'high': return 'Tinggi';
      case 'medium': return 'Sedang';
      case 'low': return 'Rendah';
      default: return priority;
    }
  }

  async logout() {
    const alert = await this.alertCtrl.create({
      header: 'Keluar',
      message: 'Apakah Anda yakin ingin keluar?',
      buttons: [
        { text: 'Batal', role: 'cancel' },
        {
          text: 'Keluar',
          role: 'destructive',
          handler: () => this.auth.logout()
        }
      ]
    });
    await alert.present();
  }

  doRefresh(event: any) {
    this.loadData().then(() => event.target.complete());
  }
}
