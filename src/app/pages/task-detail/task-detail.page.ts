import { Component, OnInit, inject } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AlertController, ToastController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-task-detail',
  templateUrl: './task-detail.page.html',
  styleUrls: ['./task-detail.page.scss'],
  standalone: false,
})
export class TaskDetailPage implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private api = inject(ApiService);
  private alertCtrl = inject(AlertController);
  private toastCtrl = inject(ToastController);

  task: any = null;
  isLoading = true;
  isActioning = false;
  taskId!: number;

  async ngOnInit() {
    this.taskId = Number(this.route.snapshot.paramMap.get('id'));
  }

  async ionViewWillEnter() {
    await this.loadTask();
  }

  async loadTask() {
    this.isLoading = true;
    try {
      const res: any = await this.api.getTaskById(this.taskId);
      this.task = res?.data ?? res;
    } catch (e) {
      this.showToast('Gagal memuat detail tugas', 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  async acceptTask() {
    const alert = await this.alertCtrl.create({
      header: 'Terima Tugas',
      message: 'Apakah Anda yakin ingin menerima tugas ini?',
      buttons: [
        { text: 'Batal', role: 'cancel' },
        {
          text: 'Terima',
          handler: async () => {
            this.isActioning = true;
            try {
              await this.api.acceptTask(this.taskId);
              await this.showToast('Tugas berhasil diterima', 'success');
              await this.loadTask();
            } catch (e) {
              this.showToast('Gagal menerima tugas', 'danger');
            } finally {
              this.isActioning = false;
            }
          }
        }
      ]
    });
    await alert.present();
  }

  async rejectTask() {
    const alert = await this.alertCtrl.create({
      header: 'Tolak Tugas',
      message: 'Apakah Anda yakin ingin menolak tugas ini?',
      buttons: [
        { text: 'Batal', role: 'cancel' },
        {
          text: 'Tolak',
          role: 'destructive',
          handler: async () => {
            this.isActioning = true;
            try {
              await this.api.rejectTask(this.taskId);
              await this.showToast('Tugas ditolak', 'warning');
              this.router.navigate(['/task-list']);
            } catch (e) {
              this.showToast('Gagal menolak tugas', 'danger');
            } finally {
              this.isActioning = false;
            }
          }
        }
      ]
    });
    await alert.present();
  }

  openMaps() {
    if (this.task?.latitude && this.task?.longitude) {
      const url = `https://www.google.com/maps/dir/?api=1&destination=${this.task.latitude},${this.task.longitude}`;
      window.open(url, '_system');
    }
  }

  goToExecution() {
    this.router.navigate(['/job-execution', this.taskId]);
  }

  goBack() {
    this.router.navigate(['/task-list']);
  }

  async showToast(msg: string, color: string) {
    const t = await this.toastCtrl.create({ message: msg, duration: 2500, color, position: 'top' });
    await t.present();
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
      case 'on-going': return 'Sedang Dikerjakan';
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

  getPriorityLabel(priority: string): string {
    switch (priority) {
      case 'high': return 'Prioritas Tinggi';
      case 'medium': return 'Prioritas Sedang';
      case 'low': return 'Prioritas Rendah';
      default: return priority;
    }
  }
}
