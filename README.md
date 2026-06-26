# FiberOps Field Service Management (FSM) Portal

Aplikasi manajemen pekerjaan lapangan komprehensif yang dirancang untuk perusahaan ISP (Internet Service Provider) dan teknisi jaringan. Proyek ini terdiri dari aplikasi mobile-responsive berbasis **Ionic 8 & Angular 20** (Progressive Web App / PWA) untuk teknisi di lapangan, serta backend REST API dan Panel Admin berbasis **Laravel 10 & MySQL**.

Tautan Repositori: [https://github.com/luthfialamsyah11/API.git](https://github.com/luthfialamsyah11/API.git)

---

## 📂 Struktur Repositori (Hybrid Layout)

Repositori ini menggunakan struktur hibrida yang menyatukan source code aplikasi client (frontend) dan server (backend) dalam satu root directory:

*   **`/` (Root Folder)**: Source code aplikasi client frontend built with **Ionic 8** & **Angular 20** (dikonfigurasi sebagai Progressive Web App / PWA).
*   **`/fsm-backend`**: Source code API Utama dan Panel Administrasi built with **Laravel 10** & **MySQL**.
*   **`/android`**: Folder proyek native Capacitor Android untuk kompilasi berkas APK/AAB bagi perangkat fisik teknisi.
*   **`/admin-public`**: Berkas bootstrap public backend Laravel yang telah disesuaikan agar bisa dideploy ke subdomain cPanel dengan struktur direktori terpisah.
*   **`/deployment-packages`**: Folder wadah berkas arsip deploy (`.zip`) untuk memudahkan proses hosting.
*   **`/docs`**: Folder berkas dokumentasi proyek (termasuk berkas `openapi.json`).
*   **`/www`**: Folder hasil build/kompilasi aplikasi frontend PWA.

---

## ⚡ Fitur Utama Sistem

1.  **Pemberian & Pemantauan Tugas (Task Assignment)**: Distribusi tiket gangguan lapangan secara real-time kepada teknisi (seperti indikasi redaman tinggi, kabel LOS, instalasi baru, migrasi bandwidth).
2.  **Pemantauan Koordinat GPS**: Melacak rute perjalanan teknisi secara real-time berdasarkan koordinat geografis (terintegrasi mock seeder rute Monas, SCBD, Manggarai, Palmerah).
3.  **Dokumentasi Bukti Kerja (Proof of Work)**: Teknisi dapat mengunggah dokumentasi foto (kondisi sebelum & sesudah perbaikan) dan menambahkan catatan detail perkembangan tugas.
4.  **Dashboard Admin**: Panel berbasis web bagi dispatcher untuk mengelola data teknisi, memantau sebaran tiket aktif, dan melihat log posisi teknisi di peta.
5.  **Offline Capability**: Dukungan Progressive Web App (PWA) dengan local storage caching yang memungkinkan akses tugas dasar di area bersinyal minim.

---

## 💻 Panduan Instalasi Lokal (Development)

### 1. Konfigurasi Backend API (Laravel)

Pindah ke direktori backend:
```bash
cd fsm-backend
```

1.  **Instalasi Dependensi PHP**:
    ```bash
    composer install
    ```
2.  **Konfigurasi Environment**:
    Salin file `.env.example` menjadi `.env` dan sesuaikan kredensial database lokal Anda:
    ```bash
    cp .env.example .env
    ```
3.  **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```
4.  **Migrasi & Seed Database**:
    Jalankan perintah ini untuk membangun struktur tabel MySQL dan mengimpor data uji coba (termasuk mock log lokasi koordinat rute teknisi):
    ```bash
    php artisan migrate:fresh --seed
    ```
5.  **Membuat Symlink Storage**:
    Hubungkan folder penyimpanan file media dengan folder publik:
    ```bash
    php artisan storage:link
    ```
6.  **Jalankan Local Development Server**:
    ```bash
    php artisan serve
    ```
    API backend Anda akan berjalan secara default di `http://127.0.0.1:8000`.

---

### 2. Konfigurasi Frontend Client (Ionic/Angular)

Pindah kembali ke root direktori proyek:
```bash
cd ..
```

1.  **Instalasi Dependensi Node.js**:
    ```bash
    npm install
    ```
2.  **Konfigurasi API Endpoint**:
    Sesuaikan variabel `apiUrl` pada berkas `src/environments/environment.ts` dengan alamat server Laravel Anda:
    ```typescript
    export const environment = {
      production: false,
      apiUrl: 'http://localhost:8000/api'
    };
    ```
3.  **Jalankan Web Server Lokal**:
    ```bash
    ionic serve
    ```
4.  **Menjalankan di Perangkat Android (Capacitor)**:
    Singkronkan aset web ke folder android dan jalankan menggunakan Android Studio:
    ```bash
    npx cap sync
    npx cap open android
    ```
    Build APK debug atau signed bundle langsung dari menu **Build -> Generate Signed Bundle / APK** pada Android Studio.

---

## 🌐 Panduan Deployment di cPanel Shared Hosting (LiteSpeed)

Sistem ini dioptimalkan untuk arsitektur dual-subdomain pada cPanel (contoh konfigurasi live):
*   **Subdomain Admin**: `admin.bentengsiber.com` -> mengarah ke folder `/public_html/admin/`
*   **Subdomain Client**: `app.bentengsiber.com` -> mengarah ke folder `/public_html/app/`
*   **Laravel Core**: Diletakkan secara aman di luar webroot, yaitu di `/home/username/laravel-core/`

### 📦 Pembuatan Paket File Deploy (.zip)
Untuk mempercepat upload ke File Manager cPanel, lakukan kompresi berkas lokal berikut dan simpan/pindahkan ke dalam folder **`/deployment-packages/`**:
1.  **`frontend.zip`**: Berisi hasil kompilasi aset web PWA (isi folder `www/` setelah menjalankan `npm run build`), lengkap dengan berkas `.htaccess` untuk SPA Routing.
2.  **`admin-public.zip`**: Berisi aset folder `/admin-public` (atau folder `public/` Laravel yang telah dimodifikasi tautan path bootstrap-nya).
3.  **`vendor.zip`**: Berisi folder `/vendor` Laravel hasil `composer install` lokal untuk menghindari pemblokiran eksekusi kompilasi di shared hosting.
4.  **`laravel.zip`** & **`resources.zip`**: Paket arsip core Laravel dan resource pendukung backend.

---

### 🚀 Langkah-Langkah Deploy ke Server

#### Langkah 1: Impor Database Bersih
*   Buat database MySQL baru di cPanel (misal: `username_dbfsm`).
*   Impor berkas database yang telah dibersihkan dari log percakapan duplikat di: `/fsm-backend/database/laravel.sql`.

#### Langkah 2: Upload Laravel Core
*   Buat folder baru bernama **`laravel-core`** di direktori root user Anda (`/home/username/`).
*   Upload berkas backend proyek Anda (kecuali `node_modules` dan `vendor`).
*   Upload berkas **`vendor.zip`** (yang berada di `/deployment-packages/vendor.zip`) langsung ke `/home/username/laravel-core/` lalu lakukan ekstrak di sana.

#### Langkah 3: Setup Subdomain dan Ekstrak Aset Publik
*   Daftarkan subdomain di cPanel:
    *   `admin.bentengsiber.com` -> direktori `/public_html/admin/`
    *   `app.bentengsiber.com` -> direktori `/public_html/app/`
*   Upload dan ekstrak berkas **`frontend.zip`** (dari `/deployment-packages/frontend.zip`) di dalam folder `/public_html/app/`.
*   Upload dan ekstrak berkas **`admin-public.zip`** (dari `/deployment-packages/admin-public.zip`) di dalam folder `/public_html/admin/`.

#### Langkah 4: Penyesuaian File `.env` Produksi
Edit file `.env` di direktori `/home/username/laravel-core/` dengan konfigurasi produksi:
```env
APP_NAME=Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.bentengsiber.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_dbfsm
DB_USERNAME=username_adminfsm
DB_PASSWORD=PasswordDatabaseAnda

FILESYSTEM_DISK=public
```

#### Langkah 5: Solusi Masalah Extension PHP `Class "PDO" not found`
Pada shared hosting dengan fitur *Site Isolation / CageFS* yang dinonaktifkan oleh administrator server, konfigurasi PHP Selector cPanel sering diabaikan oleh web server LiteSpeed.

Atasi kendala ini dengan memuat ekstensi secara manual menggunakan `php.ini` lokal:
1.  Buat berkas bernama **`php.ini`** di folder `/public_html/admin/` berisi:
    ```ini
    extension=/opt/alt/php81/usr/lib64/php/modules/pdo.so
    extension=/opt/alt/php81/usr/lib64/php/modules/pdo_mysql.so
    extension=/opt/alt/php81/usr/lib64/php/modules/mbstring.so
    extension=/opt/alt/php81/usr/lib64/php/modules/zip.so
    extension=/opt/alt/php81/usr/lib64/php/modules/fileinfo.so
    extension=/opt/alt/php81/usr/lib64/php/modules/gd.so
    ```
2.  Tambahkan instruksi pembacaan `php.ini` di bagian paling atas berkas `/public_html/admin/.htaccess`:
    ```apache
    SetEnv PHPRC /home/username/public_html/admin
    LSPHP_ConfigPath /home/username/public_html/admin
    ```

#### Langkah 6: Pembuatan Symlink Gambar
*   Akses asisten pembuatan symlink via browser di: `https://admin.bentengsiber.com/symlink.php`
*   Klik **Create Symlink** untuk menghubungkan folder media.
*   Setelah berhasil, klik **Delete This Assistant Script** demi keamanan server.

---

## 🛠️ Catatan Penting Perbaikan Kritis (Mobile Client)

Beberapa kendala pada build mobile Android telah diperbaiki di dalam kode repositori ini:

### 1. Storage Race Condition (Teratasi)
*   **Masalah**: Kegagalan penyimpanan token otentikasi saat login karena modul `@ionic/storage-angular` belum selesai diinisialisasi ketika fungsi login dipanggil.
*   **Perbaikan**: Menambahkan penampung Promise `storageReady` pada `auth.service.ts` untuk memastikan panggilan fungsi tulis storage selalu menunggu selesainya proses inisialisasi modul storage.

### 2. Header Content-Type Hilang saat Login (Teratasi)
*   **Masalah**: Request POST login ditolak server atau dianggap tidak valid karena absennya header data json.
*   **Perbaikan**: Menambahkan header `'Content-Type': 'application/json'` dan `'Accept': 'application/json'` pada parameter HTTP request di `api.service.ts`.

### 3. Penyesuaian API URL sesuai Skenario Pengujian
Untuk pengujian mobile, sesuaikan berkas `environment.ts` dengan skenario berikut:
*   **Skenario Android Emulator (Lokal)**:
    Gunakan IP Gateway emulator Android `10.0.2.2` untuk mengakses localhost komputer:
    `apiUrl: 'http://10.0.2.2:8000/api'`
*   **Skenario Perangkat Fisik (Jaringan Lokal Wifi yang Sama)**:
    Gunakan alamat IP lokal laptop Anda (cari melalui perintah `ipconfig` di command prompt):
    `apiUrl: 'http://IP_LOKAL_LAPTOP:8000/api'`
    *Catatan: Jalankan server Laravel menggunakan binding interface:* `php artisan serve --host=0.0.0.0 --port=8000`
*   **Skenario Produksi (Server Live)**:
    Gunakan domain server SSL Anda:
    `apiUrl: 'https://admin.bentengsiber.com/api'`

---

## 🔑 Kredensial Uji Coba Default (Demo Accounts)

Sistem database default menggunakan kata sandi global: **`password123`** untuk seluruh akun uji coba berikut:

*   **Dispatcher / Admin Panel (Hanya melalui Web Browser)**:
    *   Email: `admin@fsm.com`
    *   *Catatan: Akun dengan role admin akan ditolak jika mencoba masuk ke aplikasi mobile teknisi.*
*   **Teknisi Lapangan (Aplikasi Mobile / PWA)**:
    *   `alex@fsm.com`
    *   `budi@fsm.com`
    *   `citra@fsm.com`
    *   `dedi@fsm.com`
    *   `eka@fsm.com`

---

## 📡 Daftar Endpoint API Utama (Routes Reference)

Seluruh endpoint terproteksi memerlukan Header otentikasi Bearer Token (`Authorization: Bearer <token_key>`).

### 🔓 Endpoint Publik
*   `POST /api/login` -> Autentikasi user dan mengembalikan personal token.

### 🔒 Endpoint Teknisi (Terproteksi Sanctum)
*   `POST /api/logout` -> Menghapus sesi token aktif.
*   `GET /api/me` -> Mengambil informasi profil pengguna yang sedang login.
*   `PUT /api/profile` -> Memperbarui informasi profil pengguna.
*   `GET /api/tasks` -> Melihat daftar tugas aktif yang ditugaskan ke teknisi login.
*   `GET /api/tasks/history` -> Melihat riwayat tugas yang telah selesai diselesaikan.
*   `GET /api/tasks/{task}` -> Mengambil detail spesifik dari satu tugas.
*   `POST /api/tasks/{task}/accept` -> Mengubah status tugas menjadi diterima (accepted).
*   `POST /api/tasks/{task}/reject` -> Menolak penugasan tugas dengan alasan tertentu.
*   `POST /api/tasks/{task}/start` -> Memulai pengerjaan tugas (status berubah menjadi in-progress).
*   `POST /api/tasks/{task}/complete` -> Menyelesaikan pengerjaan tugas (status berubah menjadi completed).
*   `POST /api/tasks/{task}/progress` -> Menambahkan catatan progres berkala di dalam tugas.
*   `POST /api/location` -> Mengirimkan koordinat latitude & longitude posisi terkini teknisi ke server.
*   `GET /api/tasks/{task}/proof` -> Mengambil berkas dokumentasi bukti kerja tugas.
*   `POST /api/tasks/{task}/proof` -> Mengunggah berkas gambar bukti pengerjaan lapangan (before/after).

### 🔒 Endpoint Administrasi (Terproteksi Sanctum & Middleware Role Admin)
*   `GET /api/admin/dashboard` -> Mengambil ringkasan statistik, jumlah tugas, dan daftar teknisi aktif.
*   `GET /api/admin/tasks` -> Mengambil daftar seluruh tugas di sistem.
*   `POST /api/admin/tasks` -> Membuat lembar tugas baru.
*   `GET /api/admin/tasks/{task}` -> Melihat detail tugas dari sisi admin.
*   `PUT /api/admin/tasks/{task}` -> Memperbarui data tugas.
*   `POST /api/admin/tasks/{task}/assign` -> Menugaskan tugas kepada teknisi tertentu.
*   `GET /api/admin/technicians` -> Mengambil daftar data akun teknisi lapangan.
*   `POST /api/admin/technicians` -> Mendaftarkan akun teknisi baru.
*   `PUT /api/admin/technicians/{user}` -> Memperbarui data akun teknisi.
*   `DELETE /api/admin/technicians/{user}` -> Menonaktifkan/menghapus akun teknisi.
