import { Component, OnInit, inject, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { LoadingController, ToastController, ActionSheetController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';
import { NavigationService } from '../../services/navigation.service';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';

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
  private navigationService = inject(NavigationService);

  task: any = null;
  isLoading = true;
  taskId!: number;
  isStarting = false;
  isCompleting = false;

  // Progress update
  progressNote = '';
  progressPercent = 0;
  isUpdating = false;

  // Proof of work
  @ViewChild('fileInput') fileInputRef!: ElementRef<HTMLInputElement>;
  selectedFile: File | null = null;
  previewUrl: string | null = null;
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
    if (this.isStarting) return;
    this.isStarting = true;
    const loading = await this.loadingCtrl.create({ message: 'Memulai tugas...' });
    await loading.present();
    try {
      await this.api.startTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Tugas dimulai', 'success');
      await this.loadTask();
    } catch (e: any) {
      await loading.dismiss();
      this.showToast(e?.error?.message || e?.message || 'Gagal memulai tugas', 'danger');
    } finally {
      this.isStarting = false;
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
    } catch (e: any) {
      this.showToast(e?.error?.message || e?.message || 'Gagal memperbarui progres', 'danger');
    } finally {
      this.isUpdating = false;
    }
  }

  async completeTask() {
    if (this.isCompleting || this.isUploading) return;
    this.isCompleting = true;
    const loading = await this.loadingCtrl.create({ message: 'Menyelesaikan tugas...' });
    await loading.present();
    try {
      await this.api.completeTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Tugas berhasil diselesaikan! 🎉', 'success');
      this.router.navigate(['/task-list']);
    } catch (e: any) {
      await loading.dismiss();
      const errMsg = e?.error?.message || e?.message || 'Gagal menyelesaikan tugas';
      this.showToast(errMsg, 'danger');
    } finally {
      this.isCompleting = false;
    }
  }

  async takePicture() {
    try {
      const image = await Camera.getPhoto({
        quality: 70,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Prompt // Menggunakan Prompt agar bisa pilih dari Galeri/File atau Kamera
      });

      if (image && image.base64String) {
        // Convert Base64 to Blob
        const byteCharacters = atob(image.base64String);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
          byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'image/jpeg' });
        
        // Buat objek File
        this.selectedFile = new File([blob], `proof_${new Date().getTime()}.jpg`, { type: 'image/jpeg' });
        
        // Simpan preview
        this.previewUrl = `data:image/jpeg;base64,${image.base64String}`;
      }
    } catch (e) {
      console.log('Native camera not available or user cancelled, falling back to file picker:', e);
      // Fallback to web/PWA file picker
      if (this.fileInputRef?.nativeElement) {
        this.fileInputRef.nativeElement.click();
      }
    }
  }

  onFileSelected(event: any) {
    const file = event?.target?.files?.[0];
    if (file) {
      this.selectedFile = file;
      const reader = new FileReader();
      reader.onload = () => {
        this.previewUrl = reader.result as string;
      };
      reader.readAsDataURL(file);
    }
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
      this.previewUrl = null;
      this.proofDescription = '';
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || e?.message || 'Gagal mengunggah bukti', 'danger');
    } finally {
      this.isUploading = false;
    }
  }

  openNavigation() {
    if (this.task?.latitude && this.task?.longitude) {
      this.navigationService.openNavigationOptions(
        this.task.latitude,
        this.task.longitude,
        this.task.customer_name ? `${this.task.customer_name} (${this.task.title})` : this.task.title
      );
    } else {
      this.showToast('Koordinat GPS lokasi tugas tidak tersedia', 'warning');
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
