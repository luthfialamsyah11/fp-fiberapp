import { Injectable, inject } from '@angular/core';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';
import { App } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';
import { Haptics, NotificationType } from '@capacitor/haptics';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from './api.service';
import { LocationService } from './location.service';
import { StorageService } from './storage.service';

@Injectable({ providedIn: 'root' })
export class TaskSyncService {
  private api = inject(ApiService);
  private storage = inject(StorageService);
  private locationService = inject(LocationService);
  private toastCtrl = inject(ToastController);
  private router = inject(Router);

  readonly tasks$ = new BehaviorSubject<any[]>([]);
  readonly lastSyncTime$ = new BehaviorSubject<string>('');
  readonly newTaskAlert$ = new BehaviorSubject<any | null>(null);
  readonly taskVersion$ = new BehaviorSubject<string>('');
  readonly inventoryVersion$ = new BehaviorSubject<string>('');

  private isRunning = false;
  private isChecking = false;
  private pollTimer: any = null;
  private lastVersion: string | null = null;
  private lastInventoryVersion: string | null = null;
  private knownTaskIds = new Set<number>();
  private hasHydrated = false;
  private appStateHandle: any = null;

  private readonly onVisibilityChange = () => {
    if (typeof document !== 'undefined' && !document.hidden) {
      this.checkSignal(true);
    }
  };

  private readonly onWindowFocus = () => {
    this.checkSignal(true);
  };

  private readonly onNetworkOnline = () => {
    this.checkSignal(true);
  };

  start() {
    if (this.isRunning) {
      this.checkSignal(true);
      return;
    }
    this.isRunning = true;

    // 1. Immediate initial sync
    this.refreshTasks(false);

    // 2. Ultra-lightweight 3-second version check (~120 bytes) while foregrounded
    this.pollTimer = setInterval(() => {
      if (typeof document !== 'undefined' && document.hidden) return;
      this.checkSignal(false);
    }, 3000);

    // 3. Zero-delay wake on tab/window/network resume
    if (typeof document !== 'undefined') {
      document.addEventListener('visibilitychange', this.onVisibilityChange);
    }
    if (typeof window !== 'undefined') {
      window.addEventListener('focus', this.onWindowFocus);
      window.addEventListener('online', this.onNetworkOnline);
    }

    // 4. Native Android/iOS foreground resume listener
    if (Capacitor.isNativePlatform()) {
      App.addListener('appStateChange', ({ isActive }) => {
        if (isActive) {
          this.checkSignal(true);
        }
      })
        .then(handle => {
          this.appStateHandle = handle;
        })
        .catch(() => {});
    }
  }

  stop() {
    this.isRunning = false;
    if (this.pollTimer) {
      clearInterval(this.pollTimer);
      this.pollTimer = null;
    }
    if (typeof document !== 'undefined') {
      document.removeEventListener('visibilitychange', this.onVisibilityChange);
    }
    if (typeof window !== 'undefined') {
      window.removeEventListener('focus', this.onWindowFocus);
      window.removeEventListener('online', this.onNetworkOnline);
    }
    if (this.appStateHandle?.remove) {
      this.appStateHandle.remove();
      this.appStateHandle = null;
    }
    this.lastVersion = null;
    this.lastInventoryVersion = null;
    this.knownTaskIds.clear();
    this.hasHydrated = false;
    this.tasks$.next([]);
    this.newTaskAlert$.next(null);
    this.taskVersion$.next('');
    this.inventoryVersion$.next('');
    this.api.setAssignedCount(0);
  }

  dismissNewTaskAlert() {
    this.newTaskAlert$.next(null);
  }

  async checkSignal(forceFullOnSameVersion = false): Promise<void> {
    if (!this.isRunning || this.isChecking) return;

    const token = await this.storage.get('token');
    if (!token) return;

    this.isChecking = true;
    try {
      const signal: any = await this.api.getTaskSyncSignal();
      const versionChanged = this.lastVersion === null || signal.version !== this.lastVersion;
      this.lastVersion = signal.version;
      this.api.setAssignedCount(signal.assigned_count);

      if (signal.inventory_version && signal.inventory_version !== this.lastInventoryVersion) {
        this.lastInventoryVersion = signal.inventory_version;
        this.inventoryVersion$.next(signal.inventory_version);
      }

      if (versionChanged || forceFullOnSameVersion) {
        this.taskVersion$.next(signal.version || String(Date.now()));
        await this.refreshTasks(this.hasHydrated);
      }
    } catch {
      // Fallback: if sync-signal fails transiently (e.g. during deploy), try full refresh on forced checks
      if (forceFullOnSameVersion) {
        await this.refreshTasks(this.hasHydrated);
      }
    } finally {
      this.isChecking = false;
    }
  }

  async refreshTasks(notifyNewDispatches = true): Promise<any[]> {
    const token = await this.storage.get('token');
    if (!token) return [];

    try {
      const res: any = await this.api.getTasks();
      const nextTasks: any[] = Array.isArray(res) ? res : (res?.data ?? []);

      const assignedNow = nextTasks.filter(t => t.status === 'assigned' || t.status === 'pending');

      if (notifyNewDispatches && this.hasHydrated) {
        const newlyDispatched = nextTasks.filter(t => !this.knownTaskIds.has(Number(t.id)));
        if (newlyDispatched.length > 0) {
          const newest = newlyDispatched[0];
          this.newTaskAlert$.next(newest);
          this.triggerNewTaskFeedback(newest, newlyDispatched.length);
        }
      }

      this.knownTaskIds = new Set(nextTasks.map(t => Number(t.id)));
      this.hasHydrated = true;
      this.tasks$.next(nextTasks);
      this.api.setAssignedCount(assignedNow.length);

      const now = new Date();
      this.lastSyncTime$.next(now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' }));

      const activeMission = nextTasks.find(t => t.status === 'on-going')
        || nextTasks.find(t => t.status === 'accepted')
        || null;
      this.locationService.setActiveTaskId(activeMission?.id || null);

      return nextTasks;
    } catch (e) {
      console.warn('[TaskSyncService] Failed to refresh tasks:', e);
      return this.tasks$.value;
    }
  }

  private async triggerNewTaskFeedback(newestTask: any, totalNew: number) {
    // 1. Native haptic feedback on Android/iOS
    if (Capacitor.isNativePlatform()) {
      Haptics.notification({ type: NotificationType.Success }).catch(() => {});
    }

    // 2. Subtle non-blocking notification chime (Web Audio API, fails silently if autoplay blocked)
    this.playSubtleChime();

    // 3. Actionable global toast visible on ANY tab (/task-list, /map, /profile, /task-detail)
    const code = `#TSK-${String(newestTask.id).padStart(4, '0')}`;
    const msg = totalNew === 1
      ? `New work order: ${code} — ${newestTask.title}`
      : `${totalNew} new work orders assigned to you!`;

    const toast = await this.toastCtrl.create({
      message: msg,
      duration: 5500,
      position: 'top',
      color: 'primary',
      icon: 'notifications-outline',
      buttons: [
        {
          text: 'Open',
          handler: () => {
            this.newTaskAlert$.next(null);
            this.router.navigate(['/task-detail', newestTask.id]);
          },
        },
      ],
    });
    await toast.present();
  }

  private playSubtleChime() {
    try {
      const AudioCtx = (window as any).AudioContext || (window as any).webkitAudioContext;
      if (!AudioCtx) return;
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(1174.66, ctx.currentTime + 0.14);
      gain.gain.setValueAtTime(0.06, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
      osc.connect(gain);
      gain.connect(ctx.destination);

      let closed = false;
      const cleanup = () => {
        if (closed) return;
        closed = true;
        try {
          osc.disconnect();
          gain.disconnect();
        } catch {}
        if (typeof ctx.close === 'function' && ctx.state !== 'closed') {
          ctx.close().catch(() => {});
        }
      };

      osc.onended = cleanup;
      osc.start();
      osc.stop(ctx.currentTime + 0.26);
      setTimeout(cleanup, 450);
    } catch {
      // Autoplay policy may block audio until first user gesture; safe to ignore
    }
  }
}
