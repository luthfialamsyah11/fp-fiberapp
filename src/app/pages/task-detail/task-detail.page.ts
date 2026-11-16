import { Component, OnInit, inject, ViewChild, ElementRef, AfterViewInit } from '@angular/core';
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
export class TaskDetailPage implements OnInit, AfterViewInit {
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

  getBastUrl(): string {
    if (!this.task) return '#';
    const base = this.api.getBaseUrl();
    return `${base}/tasks/${this.task.id}/bast?token=${this.task.tracking_token || ''}`;
  }

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
    before: 'Photo BEFORE service: Initial issue and optical loss.',
    after: 'Photo AFTER service: Task completed, equipment verified normal.',
    additional: '',
  };

  isUploadingSlot: { [key: string]: boolean } = {
    before: false,
    after: false,
    additional: false,
  };

  // ─── Material & Inventory (BOM) Tracker ─────────────────────────────────────
  inventoryItems: any[] = [];
  selectedInventoryId: number | null = null;
  materialQuantity: number = 1;
  materialSerialNumber: string = '';
  materialNotes: string = '';
  isAddingMaterial = false;

  // ─── Customer Digital Signature (Canvas) ───────────────────────────────────
  @ViewChild('signatureCanvas') signatureCanvasRef?: ElementRef<HTMLCanvasElement>;
  private canvasCtx: CanvasRenderingContext2D | null = null;
  private isDrawing = false;
  isCanvasDrawn = false;
  customerSignerName = '';
  isSavingSignature = false;

  async ngOnInit() {
    this.taskId = Number(this.route.snapshot.paramMap.get('id'));
  }

  async ionViewWillEnter() {
    await this.loadTask();
    await this.loadInventory();
  }

  ngAfterViewInit() {
    setTimeout(() => this.initSignatureCanvas(), 500);
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

      if (this.task?.customer_name && !this.customerSignerName) {
        this.customerSignerName = this.task.customer_name;
      }

      setTimeout(() => this.initSignatureCanvas(), 300);
    } catch (e) {
      this.showToast('Failed to load task details', 'danger');
    } finally {
      this.isLoading = false;
    }
  }

  async loadInventory() {
    try {
      const res: any = await this.api.getInventory();
      this.inventoryItems = res?.data ?? [];
      if (this.inventoryItems.length > 0 && !this.selectedInventoryId) {
        this.selectedInventoryId = this.inventoryItems[0].id;
      }
    } catch (e) {
      console.warn('Failed to load inventory catalog', e);
    }
  }

  // ─── STAGE 1: ACCEPT / DECLINE ─────────────────────────────────────────────
  async acceptTask() {
    if (this.isActioning) return;
    this.isActioning = true;
    const loading = await this.loadingCtrl.create({ message: 'Accepting task...' });
    await loading.present();
    try {
      await this.api.acceptTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Task accepted! Ready for en-route dispatch.', 'success');
      await this.loadTask();
    } catch (e: any) {
      await loading.dismiss();
      this.showToast(e?.error?.message || 'Failed to accept task', 'danger');
    } finally {
      this.isActioning = false;
    }
  }

  async rejectTask() {
    const alert = await this.alertCtrl.create({
      header: 'Decline Assignment?',
      message: 'Are you sure you want to decline this task? The ticket will be returned to the dispatcher pool.',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Decline Task',
          role: 'destructive',
          handler: async () => {
            const loading = await this.loadingCtrl.create({ message: 'Declining task...' });
            await loading.present();
            try {
              await this.api.rejectTask(this.taskId);
              await loading.dismiss();
              await this.showToast('Task has been declined.', 'medium');
              this.router.navigate(['/task-list']);
            } catch (e: any) {
              await loading.dismiss();
              this.showToast(e?.error?.message || 'Failed to decline task', 'danger');
            }
          }
        }
      ]
    });
    await alert.present();
  }

  // ─── STAGE 2: START ON-SITE ────────────────────────────────────────────────
  async startTask() {
    if (this.isStarting) return;
    this.isStarting = true;
    const loading = await this.loadingCtrl.create({ message: 'Starting work on-site...' });
    await loading.present();
    try {
      await this.api.startTask(this.taskId);
      await loading.dismiss();
      await this.showToast('Task started! Status changed to In Progress.', 'success');
      await this.loadTask();
    } catch (e: any) {
      await loading.dismiss();
      this.showToast(e?.error?.message || 'Failed to start task', 'danger');
    } finally {
      this.isStarting = false;
    }
  }

  // ─── STAGE 3: UPDATE PROGRESS & PROOF PHOTOS ───────────────────────────────
  async updateProgress() {
    if (this.isUpdatingProgress) return;
    this.isUpdatingProgress = true;
    try {
      await this.api.updateProgress(this.taskId, this.progressNote || 'Field progress update', this.progressPercent);
      await this.showToast(`Progress updated to ${this.progressPercent}%`, 'success');
      this.progressNote = '';
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Failed to update progress', 'danger');
    } finally {
      this.isUpdatingProgress = false;
    }
  }

  async selectPhotoForSlot(slot: 'before' | 'after' | 'additional') {
    this.activeSlot = slot;
    try {
      const image = await Camera.getPhoto({
        quality: 85,
        allowEditing: false,
        resultType: CameraResultType.Base64,
        source: CameraSource.Camera,
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
      this.showToast('Please capture or select a photo first', 'warning');
      return;
    }

    const desc = this.slotDescriptions[slot] || (slot === 'before' ? 'Before Photo' : 'After Photo');
    this.isUploadingSlot[slot] = true;
    try {
      await this.api.uploadProof(this.taskId, file, desc);
      await this.showToast(`Proof photo (${slot === 'before' ? 'Before' : (slot === 'after' ? 'After' : 'Additional')}) uploaded successfully!`, 'success');
      this.slotFiles[slot] = null;
      this.slotPreviews[slot] = null;
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Failed to upload proof photo', 'danger');
    } finally {
      this.isUploadingSlot[slot] = false;
    }
  }

  // ─── STAGE 3.5: MATERIAL & INVENTORY (BOM) ──────────────────────────────────
  async addMaterial() {
    if (!this.selectedInventoryId) {
      this.showToast('Please select a material / device first', 'warning');
      return;
    }
    if (this.materialQuantity <= 0) {
      this.showToast('Quantity must be greater than 0', 'warning');
      return;
    }

    this.isAddingMaterial = true;
    try {
      await this.api.addTaskMaterial(this.taskId, {
        inventory_item_id: Number(this.selectedInventoryId),
        quantity: Number(this.materialQuantity),
        serial_number: this.materialSerialNumber || undefined,
        notes: this.materialNotes || undefined,
      });

      await this.showToast('Material recorded & warehouse stock deducted!', 'success');
      this.materialSerialNumber = '';
      this.materialNotes = '';
      this.materialQuantity = 1;
      await this.loadTask();
      await this.loadInventory();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Failed to record material', 'danger');
    } finally {
      this.isAddingMaterial = false;
    }
  }

  async deleteMaterial(materialId: number) {
    const alert = await this.alertCtrl.create({
      header: 'Remove Material?',
      message: 'This item will be removed and stock will be returned to warehouse.',
      buttons: [
        { text: 'Cancel', role: 'cancel' },
        {
          text: 'Remove',
          role: 'destructive',
          handler: async () => {
            try {
              await this.api.deleteTaskMaterial(this.taskId, materialId);
              await this.showToast('Material removed & stock restored.', 'success');
              await this.loadTask();
              await this.loadInventory();
            } catch (e: any) {
              this.showToast(e?.error?.message || 'Failed to remove material', 'danger');
            }
          }
        }
      ]
    });
    await alert.present();
  }

  removeMaterial(material: any) {
    if (material?.id) {
      this.deleteMaterial(material.id);
    }
  }

  triggerCapture(slot: 'before' | 'after' | 'additional') {
    this.selectPhotoForSlot(slot);
  }

  clearCanvas() {
    this.clearSignature();
  }

  // ─── TAHAP 3.8: CUSTOMER DIGITAL E-SIGNATURE (CANVAS) ───────────────────────
  initSignatureCanvas() {
    if (!this.signatureCanvasRef?.nativeElement) return;
    const canvas = this.signatureCanvasRef.nativeElement;
    this.canvasCtx = canvas.getContext('2d');
    if (!this.canvasCtx) return;

    // Resize canvas based on display container width
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width > 0 ? rect.width : 340;
    canvas.height = 150;

    this.canvasCtx.lineWidth = 2.5;
    this.canvasCtx.lineCap = 'round';
    this.canvasCtx.lineJoin = 'round';
    this.canvasCtx.strokeStyle = '#0284c7';

    // Touch events for Mobile
    canvas.addEventListener('touchstart', (e) => this.handleCanvasTouch(e, 'start'), { passive: false });
    canvas.addEventListener('touchmove', (e) => this.handleCanvasTouch(e, 'move'), { passive: false });
    canvas.addEventListener('touchend', () => this.handleCanvasTouch(null, 'end'), { passive: false });

    // Mouse events for Desktop/Emulators
    canvas.addEventListener('mousedown', (e) => this.handleCanvasMouse(e, 'start'));
    canvas.addEventListener('mousemove', (e) => this.handleCanvasMouse(e, 'move'));
    canvas.addEventListener('mouseup', () => this.handleCanvasMouse(null, 'end'));
    canvas.addEventListener('mouseleave', () => this.handleCanvasMouse(null, 'end'));
  }

  private handleCanvasTouch(e: TouchEvent | null, type: 'start' | 'move' | 'end') {
    if (!this.canvasCtx || !this.signatureCanvasRef) return;
    if (e) e.preventDefault();

    const canvas = this.signatureCanvasRef.nativeElement;
    const rect = canvas.getBoundingClientRect();

    if (type === 'start' && e && e.touches[0]) {
      this.isDrawing = true;
      this.isCanvasDrawn = true;
      this.canvasCtx.beginPath();
      this.canvasCtx.moveTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top);
    } else if (type === 'move' && this.isDrawing && e && e.touches[0]) {
      this.canvasCtx.lineTo(e.touches[0].clientX - rect.left, e.touches[0].clientY - rect.top);
      this.canvasCtx.stroke();
    } else if (type === 'end') {
      this.isDrawing = false;
    }
  }

  private handleCanvasMouse(e: MouseEvent | null, type: 'start' | 'move' | 'end') {
    if (!this.canvasCtx || !this.signatureCanvasRef) return;
    const canvas = this.signatureCanvasRef.nativeElement;
    const rect = canvas.getBoundingClientRect();

    if (type === 'start' && e) {
      this.isDrawing = true;
      this.isCanvasDrawn = true;
      this.canvasCtx.beginPath();
      this.canvasCtx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    } else if (type === 'move' && this.isDrawing && e) {
      this.canvasCtx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
      this.canvasCtx.stroke();
    } else if (type === 'end') {
      this.isDrawing = false;
    }
  }

  clearSignature() {
    if (!this.canvasCtx || !this.signatureCanvasRef) return;
    const canvas = this.signatureCanvasRef.nativeElement;
    this.canvasCtx.clearRect(0, 0, canvas.width, canvas.height);
    this.isCanvasDrawn = false;
  }

  async saveSignature() {
    if (!this.isCanvasDrawn || !this.signatureCanvasRef) {
      this.showToast('Please collect customer signature on screen first', 'warning');
      return;
    }
    if (!this.customerSignerName.trim()) {
      this.showToast('Please enter the customer signer full name', 'warning');
      return;
    }

    const canvas = this.signatureCanvasRef.nativeElement;
    const dataUrl = canvas.toDataURL('image/png');

    this.isSavingSignature = true;
    try {
      await this.api.saveTaskSignature(this.taskId, dataUrl, this.customerSignerName);
      await this.showToast('Customer signature saved & verified!', 'success');
      await this.loadTask();
    } catch (e: any) {
      this.showToast(e?.error?.message || 'Failed to save customer signature', 'danger');
    } finally {
      this.isSavingSignature = false;
    }
  }

  // ─── STAGE 4: COMPLETE TASK ────────────────────────────────────────────────
  async completeTask() {
    if (this.isCompleting) return;

    const proofs = this.task?.proof_of_work || this.task?.proofs || [];
    if (proofs.length === 0) {
      this.showToast('At least 1 proof photo is required before completing task', 'warning');
      return;
    }

    const alert = await this.alertCtrl.create({
      header: 'Confirm Completion',
      message: 'Have you verified the installation, recorded all materials, and captured the customer signature?',
      buttons: [
        { text: 'Review Again', role: 'cancel' },
        {
          text: 'Mark Complete',
          handler: async () => {
            this.isCompleting = true;
            const loading = await this.loadingCtrl.create({ message: 'Processing task completion...' });
            await loading.present();
            try {
              await this.api.completeTask(this.taskId);
              await loading.dismiss();
              await this.showToast('Congratulations! Work order completed. BAST is ready to export. 🎉', 'success');
              await this.loadTask();
            } catch (e: any) {
              await loading.dismiss();
              this.showToast(e?.error?.message || 'Failed to complete task', 'danger');
            } finally {
              this.isCompleting = false;
            }
          }
        }
      ]
    });
    await alert.present();
  }

  // ─── 1-TAP COMMUNICATION, UBER-TRACKING & NAVIGATION SHORTCUTS ──────────────
  shareCustomerTracking() {
    if (!this.task?.customer_phone) {
      this.showToast('Customer phone number is not available', 'warning');
      return;
    }

    let cleanPhone = this.task.customer_phone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '62' + cleanPhone.substring(1);
    }
    const customer = this.task.customer_name || 'Customer';
    const techName = this.user?.name || 'FiberOps Specialist';
    const trackingUrl = this.task?.tracking_url || `http://127.0.0.1:8000/track/${this.task?.tracking_token}`;
    
    const text = encodeURIComponent(
      `Hello ${customer}, this is ${techName} from the FiberOps technical team en route to your location for ticket: ${this.task.title}.\n\nYou can track our service fleet live here:\n👉 ${trackingUrl}\n\nPlease ensure someone is on-site. Thank you!`
    );
    window.open(`https://wa.me/${cleanPhone}?text=${text}`, '_blank');
  }

  openWhatsApp() {
    if (!this.task?.customer_phone) return;
    let cleanPhone = this.task.customer_phone.replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '62' + cleanPhone.substring(1);
    }
    const customer = this.task.customer_name || 'Customer';
    const techName = this.user?.name || 'FiberOps Specialist';
    const text = encodeURIComponent(
      `Hello ${customer}, this is ${techName} from FiberOps on the way to your location for work order: ${this.task.title}. Please ensure someone is available on-site. Thank you.`
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
      this.showToast('Address copied to clipboard', 'success');
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
      this.showToast('GPS coordinates not available for this task', 'warning');
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
      case 'pending': return 'Pending';
      case 'assigned': return 'Assigned';
      case 'accepted': return 'Accepted';
      case 'on-going': return 'In Progress';
      case 'completed': return 'Completed';
      case 'rejected': return 'Declined';
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
      case 'high': return 'High Priority';
      case 'medium': return 'Medium Priority';
      case 'low': return 'Low Priority';
      default: return priority;
    }
  }
}
