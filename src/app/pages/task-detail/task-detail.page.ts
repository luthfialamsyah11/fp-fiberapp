import { Component, OnInit, inject, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { AlertController, LoadingController, NavController, ToastController } from '@ionic/angular';
import { ApiService } from '../../services/api.service';
import { NavigationService } from '../../services/navigation.service';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';

@Component({
  selector: 'app-task-detail',
  templateUrl: './task-detail.page.html',
  styleUrls: ['./task-detail.page.scss'],
  standalone: false,
})
export class TaskDetailPage implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private navCtrl = inject(NavController);
  private api = inject(ApiService);
  private alertCtrl = inject(AlertController);
  private loadingCtrl = inject(LoadingController);
  private toastCtrl = inject(ToastController);
  private navigationService = inject(NavigationService);

  task: any = null;
  user: any = null;
  isLoading = true;
  isActioning = false;
  taskId!: number;

  // Progressive Execution State
  isStarting = false;
  isCompleting = false;
  isUpdatingProgress = false;
  progressPercent = 0;
  progressNote = '';

  // Guided Slot Proofs (Before, After, Additional)
  activeSlot: 'before' | 'after' | 'additional' = 'before';
  @ViewChild('fileInputBefore') fileInputBefore!: ElementRef<HTMLInputElement>;
  @ViewChild('fileInputAfter') fileInputAfter!: ElementRef<HTMLInputElement>;
  @ViewChild('fileInputAdditional') fileInputAdditional!: ElementRef<HTMLInputElement>;

  slotFiles: { [key: string]: File | null } = {
    before: null,
    after: null,
    additional: null,
  };

  slotPreviews: { [key: string]: string | null } = {
    before: null,
    after: null,
    additional: null,
  };

  slotDescriptions: { [key: string]: string } = {
    before: 'Foto kondisi SEBELUM perbaikan: Kendala dan redaman awal.',
    after: 'Foto kondisi SESUDAH perbaikan: Pekerjaan tuntas, perangkat normal.',
    additional: '',
  };

  isUploadingSlot: { [key: string]: boolean } = {
    before: false,
    after: false,
    additional: false,
  };

  async ngOnInit() {
    this.taskId = Number(this.route.snapshot.paramMap.get('id'));
  }

  async ionViewWillEnter() {
    await this.loadTask();
  }

  async loadTask() {
    this.isLoading = true;
    try {
      // 1. Get Me profile
      try {
        const uRes: any = await this.api.getMe();
        this.user = uRes?.data ?? uRes;
      } catch (err) {
        console.warn('Profile fetch warning in task detail', err);
      }

      // 2. Get Task
      const res: any = await this.api.getTaskById(this.taskId);
      this.task = res?.data ?? res;

      if (this.task?.progress_percent) {
        this.progressPercent = this.task.progress_percent;
      }
    } catch (e) {
      this.showToast('Gagal memuat detail tugas', 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  // ─── TAHAP 1: TERIMA / TOLAK ───────────────────────────────────────────────────
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
              await this.showToast('Tugas berhasil diterima! Siap berangkat.', 'success');
              await this.loadTask();
            } catch (e: any) {
              this.showToast(e?.error?.message || 'Gagal menerima tugas', 'danger');
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
      message: 'Apakah Anda yakin ingin menolak tugas ini? Tiket akan dikembalikan ke dispatcher.',
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
            } catch (e: any) {
              this.showToast(e?.error?.message || 'Gagal menolak tugas', 'danger');
            } finally {
              this.isActioning = false;
            }
          }
        }
      ]
    });
    await alert.present();
  }

  // ─── TAHAP 2: MULAI PENGERJAAN ─────────────────────────────────────────────────
  async startTask() {
    if (this.isStarting) return;
    this.isStarting = true;
    const loading = await this.loadingCtrl.create({ message: 'Memulai tugas...' });
    await loading.present();
    try {
      await this.api.startTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Tugas dimulai! Silakan ambil Foto Sebelum.', 'success');
      await this.loadTask();
    } catch (e: any) {
      await loading.dismiss();
      this.showToast(e?.error?.message || 'Gagal memulai tugas', 'danger');
    } finally {
      this.isStarting = false;
    }
  }

  // ─── TAHAP 3: UPDATE PROGRES & BUKTI FOTO ──────────────────────────────────────
  async updateProgress() {
    if (!this.progressNote.trim()) {
      this.showToast('Isi catatan perkembangan pekerjaan terlebih dahulu', 'warning');
      return;
    }
    this.isUpdatingProgress = true;
    try {
      await this.api.updateProgress(this.taskId, this.progressNote, this.progressPercent);
      await this.showToast('Catatan progres berhasil disimpan', 'success');
      this.progressNote = '';
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Gagal menyimpan progres', 'danger');
    } finally {
      this.isUpdatingProgress = false;
    }
  }

  async triggerCapture(slot: 'before' | 'after' | 'additional') {
    this.activeSlot = slot;
    try {
      const image = await Camera.getPhoto({
        quality: 75,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Prompt,
      });

      if (image && image.base64String) {
        const byteCharacters = atob(image.base64String);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
          byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: 'image/jpeg' });
        this.slotFiles[slot] = new File([blob], `${slot}_${new Date().getTime()}.jpg`, { type: 'image/jpeg' });
        this.slotPreviews[slot] = `data:image/jpeg;base64,${image.base64String}`;
      }
    } catch (e) {
      // Fallback to web file picker
      if (slot === 'before' && this.fileInputBefore?.nativeElement) {
        this.fileInputBefore.nativeElement.click();
      } else if (slot === 'after' && this.fileInputAfter?.nativeElement) {
        this.fileInputAfter.nativeElement.click();
      } else if (slot === 'additional' && this.fileInputAdditional?.nativeElement) {
        this.fileInputAdditional.nativeElement.click();
      }
    }
  }

  onFileSelected(event: any, slot: 'before' | 'after' | 'additional') {
    const file = event?.target?.files?.[0];
    if (file) {
      this.slotFiles[slot] = file;
      const reader = new FileReader();
      reader.onload = () => {
        this.slotPreviews[slot] = reader.result as string;
      };
      reader.readAsDataURL(file);
    }
  }

  async uploadSlotProof(slot: 'before' | 'after' | 'additional') {
    const file = this.slotFiles[slot];
    if (!file) {
      this.showToast('Pilih atau foto gambar terlebih dahulu', 'warning');
      return;
    }

    const desc = this.slotDescriptions[slot] || (slot === 'before' ? 'Foto Sebelum' : 'Foto Sesudah');
    this.isUploadingSlot[slot] = true;
    try {
      await this.api.uploadProof(this.taskId, file, desc);
      await this.showToast(`Bukti ${slot === 'before' ? 'Foto Sebelum' : (slot === 'after' ? 'Foto Sesudah' : 'Foto Tambahan')} berhasil diunggah!`, 'success');
      this.slotFiles[slot] = null;
      this.slotPreviews[slot] = null;
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Gagal mengunggah foto bukti', 'danger');
    } finally {
      this.isUploadingSlot[slot] = false;
    }
  }

  // ─── TAHAP 4: SELESAIKAN TUGAS ─────────────────────────────────────────────────
  async completeTask() {
    if (this.isCompleting) return;

    const proofs = this.task?.proof_of_work || this.task?.proofs || [];
    if (proofs.length === 0) {
      this.showToast('Wajib mengunggah minimal 1 foto bukti sebelum menyelesaikan tugas', 'warning');
      return;
    }

    const alert = await this.alertCtrl.create({
      header: 'Konfirmasi Penyelesaian',
      message: 'Apakah pekerjaan sudah dipastikan tuntas dan perangkat pelanggan berfungsi normal?',
      buttons: [
        { text: 'Periksa Lagi', role: 'cancel' },
        {
          text: 'Tandai Selesai',
          handler: async () => {
            this.isCompleting = true;
            const loading = await this.loadingCtrl.create({ message: 'Memproses penyelesaian...' });
            await loading.present();
            try {
              await this.api.completeTask(this.taskId);
              await loading.dismiss();
              await this.showToast('Selamat! Tugas berhasil diselesaikan. 🎉', 'success');
              await this.loadTask();
            } catch (e: any) {
              await loading.dismiss();
              this.showToast(e?.error?.message || 'Gagal menyelesaikan tugas', 'danger');
            } finally {
              this.isCompleting = false;
            }
          }
        }
      ]
    });
    await alert.present();
  }

  // ─── PINTASAN KOMUNIKASI & NAVIGASI 1-TAP ──────────────────────────────────────
  openWhatsApp() {
    if (!this.task?.customer_phone) return;
    let cleanPhone = this.task.customer_phone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '62' + cleanPhone.substring(1);
    }
    const customer = this.task.customer_name || 'Pelanggan';
    const techName = this.user?.name || 'Teknisi FiberOps';
    const text = encodeURIComponent(
      `Halo Bapak/Ibu ${customer}, saya ${techName} dari tim teknisi FiberOps sedang menuju ke lokasi Anda untuk pengerjaan tiket: ${this.task.title}. Mohon pastikan ada orang di lokasi. Terima kasih.`
    );
    window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
  }

  callCustomer() {
    if (!this.task?.customer_phone) return;
    window.open(`tel:${this.task.customer_phone}`, '_system');
  }

  async copyAddress() {
    const addr = this.task?.address || this.task?.location || this.task?.resolved_location;
    if (addr && navigator.clipboard) {
      await navigator.clipboard.writeText(addr);
      this.showToast('Alamat berhasil disalin ke clipboard', 'success');
    }
  }

  openMaps() {
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
    if (window.history.length > 1) {
      this.navCtrl.back();
    } else {
      this.router.navigate(['/task-list']);
    }
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
