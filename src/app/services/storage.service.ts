import { Injectable, inject } from '@angular/core';
import { Storage } from '@ionic/storage-angular';

@Injectable({ providedIn: 'root' })
export class StorageService {
  private storage = inject(Storage);
  private _storage: Storage | null = null;
  private storageReady: Promise<Storage>;

  constructor() {
    this.storageReady = this.init();
  }

  private async init(): Promise<Storage> {
    this._storage = await this.storage.create();
    return this._storage;
  }

  async get(key: string): Promise<any> {
    const storage = await this.storageReady;
    return storage.get(key);
  }

  async set(key: string, value: any): Promise<any> {
    const storage = await this.storageReady;
    return storage.set(key, value);
  }

  async remove(key: string): Promise<any> {
    const storage = await this.storageReady;
    return storage.remove(key);
  }

  async clear(): Promise<void> {
    const storage = await this.storageReady;
    return storage.clear();
  }
}
