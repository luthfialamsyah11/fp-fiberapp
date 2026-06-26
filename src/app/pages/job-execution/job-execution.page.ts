import { Component, OnInit, inject } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { LoadingController, ToastController, ActionSheetController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';

@Component({
  selector: 'app-job-execution',
  templateUrl: './job-execution.page.html',
  styleUrls: ['./job-execution.page.scss'],
  standalone: false,
})
export class JobExecutionPage implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private api = inject(ApiService);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);
  private actionSheetCtrl = inject(ActionSheetController);

  task: any = null;
  isLoading = true;
  taskId!: number;

  // Progress update
  progressNote = '';
  progressPercent = 0;
  isUpdating = false;

  // Proof of work
  selectedFile: File | null = null;
  proofDescription = '';
  isUploading = false;

  async ngOnInit() {
    this.taskId = Number(this.route.snapshot.paramMap.get('id'));
    await this.loadTask();
  }

  async loadTask() {
    this.isLoading = true;
    try {
      const res: any = await this.api.getTaskById(this.taskId);
      this.task = res?.data ?? res;
      if (this.task?.progress_percent) {
        this.progressPercent = this.task.progress_percent;
      }
    } catch (e) {
      this.showToast('Gagal memuat tugas', 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  async startTask() {
    const loading = await this.loadingCtrl.create({ message: 'Memulai tugas...' });
    await loading.present();
    try {
      await this.api.startTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Tugas dimulai', 'success');
      await this.loadTask();
    } catch (e) {
      await loading.dismiss();
      this.showToast('Gagal memulai tugas', 'danger');
    }
  }

  async updateProgress() {
    if (!this.progressNote.trim()) {
      this.showToast('Isi catatan progres terlebih dahulu', 'warning');
      return;
    }
    this.isUpdating = true;
    try {
      await this.api.updateProgress(this.taskId, this.progressNote, this.progressPercent);
      await this.showToast('Progres diperbarui', 'success');
      this.progressNote = '';
      await this.loadTask();
    } catch (e) {
      this.showToast('Gagal memperbarui progres', 'danger');
    } finally {
      this.isUpdating = false;
    }
  }

  async completeTask() {
    const loading = await this.loadingCtrl.create({ message: 'Menyelesaikan tugas...' });
    await loading.present();
    try {
      await this.api.completeTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Tugas berhasil diselesaikan! 🎉', 'success');
      this.router.navigate(['/task-list']);
    } catch (e) {
      await loading.dismiss();
      this.showToast('Gagal menyelesaikan tugas', 'danger');
    }
  }

  onFileChange(event: any) {
    const file = event.target.files[0];
    if (file) this.selectedFile = file;
  }

  async uploadProof() {
    if (!this.selectedFile) {
      this.showToast('Pilih foto bukti terlebih dahulu', 'warning');
      return;
    }
    this.isUploading = true;
    try {
      await this.api.uploadProof(this.taskId, this.selectedFile, this.proofDescription);
      await this.showToast('Bukti pekerjaan berhasil diunggah', 'success');
      this.selectedFile = null;
      this.proofDescription = '';
    } catch (e) {
      this.showToast('Gagal mengunggah bukti', 'danger');
    } finally {
      this.isUploading = false;
    }
  }

  goBack() {
    this.router.navigate(['/task-detail', this.taskId]);
  }

  async showToast(msg: string, color: string) {
    const t = await this.toastCtrl.create({ message: msg, duration: 2500, color, position: 'top' });
    await t.present();
  }
}
