@extends('layouts.admin')

@section('title', 'Buku Panduan Penggunaan Web Admin')
@section('header', 'Panduan Operasional Web Admin')

@push('styles')
<style>
    .guide-nav-item.active {
        background-color: #EFF6FF;
        border-color: #3B82F6;
        color: #1D4ED8;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Sticky Sidebar: Table of Contents -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-fsmBorder p-5 shadow-sm sticky top-6 space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                    <ion-icon name="book-outline" class="text-lg"></ion-icon>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Daftar Isi Panduan</h3>
                    <span class="text-[10px] text-slate-400">FiberOps Manual v1.2</span>
                </div>
            </div>

            <!-- Quick Filter -->
            <div>
                <input type="text" id="guide-search" placeholder="Cari panduan..."
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Navigation Links -->
            <nav id="guide-toc" class="space-y-1 text-xs">
                <a href="#quickstart" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="rocket-outline" class="text-sm flex-shrink-0 text-blue-600"></ion-icon>
                    <span>1. Memulai Cepat & Alur FSM</span>
                </a>
                <a href="#dashboard" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="grid-outline" class="text-sm flex-shrink-0 text-indigo-600"></ion-icon>
                    <span>2. Dashboard & Metrik KPI</span>
                </a>
                <a href="#tasks" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="clipboard-outline" class="text-sm flex-shrink-0 text-emerald-600"></ion-icon>
                    <span>3. Manajemen & Siklus Tiket</span>
                </a>
                <a href="#tracking" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="navigate-outline" class="text-sm flex-shrink-0 text-sky-600"></ion-icon>
                    <span>4. Live Tracking GPS Peta</span>
                </a>
                <a href="#proof" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="camera-outline" class="text-sm flex-shrink-0 text-amber-600"></ion-icon>
                    <span>5. Audit Bukti Foto Redaman</span>
                </a>
                <a href="#technicians" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="people-outline" class="text-sm flex-shrink-0 text-purple-600"></ion-icon>
                    <span>6. Manajemen Armada Teknisi</span>
                </a>
                <a href="#faq" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="help-circle-outline" class="text-sm flex-shrink-0 text-red-600"></ion-icon>
                    <span>7. Troubleshooting & FAQ</span>
                </a>
            </nav>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Hotline: (021) 8062-8888</span>
                <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:underline font-semibold">Pengaturan &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Main Guide Content -->
    <div class="lg:col-span-3 space-y-8">

        <!-- Header Hero -->
        <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold mb-3">
                <ion-icon name="shield-checkmark"></ion-icon>
                <span>Dokumentasi Standar Operasional Prosedur (SOP)</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Panduan Operasional Web Admin FiberOps
            </h1>
            <p class="text-sm text-slate-600 mt-2 max-w-2xl leading-relaxed">
                Panduan komprehensif ini dirancang untuk Dispatcher, Supervisor Lapangan, dan Administrator dalam mengelola tiket perbaikan, dispatching tugas, pemantauan live GPS telemetri, dan validasi bukti pekerjaan teknisi.
            </p>
        </div>

        <!-- 1. Quick Start Section -->
        <div id="quickstart" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">1</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Alur Kerja Sistem (End-to-End Workflow)</h2>
                    <span class="text-xs text-slate-500">Integrasi penuh antara Web Admin Dispatcher dan Aplikasi Mobile Teknisi</span>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                FiberOps FSM beroperasi dengan sistem tertutup dua arah. Setiap aksi yang dilakukan admin di komputer kantor langsung tersinkronisasi ke smartphone teknisi di lapangan, dan sebaliknya:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 my-4">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">1</div>
                    <div class="text-xs font-bold text-slate-800">Tiket Dibuat</div>
                    <div class="text-[10px] text-slate-500 mt-1">Admin input keluhan atau pasang baru di Web Admin.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">2</div>
                    <div class="text-xs font-bold text-slate-800">Dispatch Teknisi</div>
                    <div class="text-[10px] text-slate-500 mt-1">Tugas otomatis masuk ke aplikasi HP teknisi terkait.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">3</div>
                    <div class="text-xs font-bold text-slate-800">Live Eksekusi</div>
                    <div class="text-[10px] text-slate-500 mt-1">Teknisi terima tugas, mulai kerja, dan update progres %.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-amber-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">4</div>
                    <div class="text-xs font-bold text-slate-800">Upload & Selesai</div>
                    <div class="text-[10px] text-slate-500 mt-1">Wajib upload foto bukti redaman OPM sebelum closing tiket.</div>
                </div>
            </div>
        </div>

        <!-- 2. Dashboard & KPI Overview -->
        <div id="dashboard" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">2</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Dashboard & Metrik KPI Operasional</h2>
                    <span class="text-xs text-slate-500">Membaca metrik performa secara instan</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Dashboard utama menyajikan 4 kartu metrik utama:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Pending Tasks</b> (Kuning): Tiket baru yang belum ditugaskan ke teknisi manapun. Wajib segera di-dispatch sebelum melanggar batas SLA.</li>
                    <li><b>Active Tasks / On-Going</b> (Biru): Tiket yang saat ini sedang dalam pengerjaan aktif oleh teknisi di lapangan.</li>
                    <li><b>Completed Tasks</b> (Hijau): Akumulasi tiket yang telah berhasil diselesaikan dan lolos audit bukti foto.</li>
                    <li><b>Online Fleet</b> (Emerald): Jumlah teknisi yang sedang memancarkan sinyal GPS aktif dalam 30 menit terakhir.</li>
                </ul>
            </div>
        </div>

        <!-- 3. Task Management & Lifecycle -->
        <div id="tasks" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">3</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Manajemen & Siklus Hidup Tiket</h2>
                    <span class="text-xs text-slate-500">SOP pembuatan, penugasan, dan penutupan tugas lapangan</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <h4 class="font-bold text-slate-800">A. Cara Membuat Tiket Tugas Baru:</h4>
                <ol class="list-decimal list-inside space-y-1 ml-2">
                    <li>Buka menu <b>Task Management</b> &rarr; klik tombol <b>+ Buat Tugas Baru</b>.</li>
                    <li>Isi data pelanggan (Nama, Nomor Telepon, Alamat Lengkap).</li>
                    <li>Tentukan koordinat Latitude dan Longitude pelanggan (gunakan format desimal, contoh: `-6.2088, 106.8456`).</li>
                    <li>Pilih tingkat prioritas: <b>High</b> (Gangguan total / kabel putus), <b>Medium</b> (WiFi lambat), atau <b>Low</b> (Pengecekan berkala).</li>
                    <li>Pilih teknisi yang ditugaskan (atau biarkan kosong untuk status <i>pending</i>).</li>
                    <li>Klik <b>Simpan Tugas</b>.</li>
                </ol>

                <h4 class="font-bold text-slate-800 mt-4">B. Penjelasan Status Tiket:</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-2">
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">pending</span>
                        <p class="text-[11px] text-slate-600 mt-1">Tiket baru dibuat, belum ada teknisi yang dialokasikan.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">assigned</span>
                        <p class="text-[11px] text-slate-600 mt-1">Teknisi sudah ditunjuk, menunggu teknisi menekan tombol "Terima".</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">accepted</span>
                        <p class="text-[11px] text-slate-600 mt-1">Teknisi telah mengonfirmasi kesiapan penanganan tugas.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">on-going</span>
                        <p class="text-[11px] text-slate-600 mt-1">Teknisi telah tiba di lokasi dan sedang melakukan perbaikan fisik.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 sm:col-span-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">completed</span>
                        <p class="text-[11px] text-slate-600 mt-1">Pekerjaan tuntas, bukti foto sudah terunggah, dan sistem internet pelanggan normal.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Live Tracking GPS Peta -->
        <div id="tracking" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">4</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Live Tracking GPS Peta Lapangan</h2>
                    <span class="text-xs text-slate-500">Memantau pergerakan armada secara real-time</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Halaman <b>Live Tracking</b> (`/tracking`) menggunakan Leaflet Map Engine yang melakukan streaming telemetri setiap 4 detik tanpa reload halaman:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Pin Biru</b>: Menandakan posisi teknisi yang sedang *standby* atau idle.</li>
                    <li><b>Pin Hijau</b>: Menandakan teknisi yang sedang memegang tugas aktif berstatus *on-going*.</li>
                    <li><b>Garis Merah (Route Trail)</b>: Menampilkan jejak lintasan riwayat perjalanan teknisi dari titik ke titik. Klik nama teknisi di sidebar kiri untuk menampilkan rutenya.</li>
                    <li><b>Live Telemetry Log</b>: Feed log di bagian kiri bawah mencatat setiap ping koordinat yang masuk beserta stempel waktu detiknya.</li>
                </ul>
            </div>
        </div>

        <!-- 5. Audit Bukti Foto Redaman -->
        <div id="proof" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">5</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Audit Bukti Pekerjaan (Proof of Work)</h2>
                    <span class="text-xs text-slate-500">Standar mutu hasil instalasi & perbaikan kabel fiber optik</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Sistem secara ketat mewajibkan teknisi mengunggah minimal 1 foto bukti kerja sebelum tugas dapat diselesaikan. Admin wajib memeriksa:</p>
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 space-y-2">
                    <div class="font-bold text-amber-900">Parameter Kunci Standar Redaman Fiber Optik:</div>
                    <ul class="list-disc list-inside space-y-1 text-amber-800">
                        <li><b>Sangat Baik:</b> -15.0 dBm s/d -20.0 dBm (Loss minimal, ONT stabil maksimal).</li>
                        <li><b>Standar Layak:</b> -20.1 dBm s/d -24.0 dBm (Operasional normal tanpa packet loss).</li>
                        <li><b>Kritis / Buruk:</b> Di atas -25.0 dBm (Potensi Red LOS, laser modulasi lemah).</li>
                    </ul>
                </div>
                <p>Jika foto yang diunggah tidak jelas atau angka OPM tidak terbaca, admin berhak meminta teknisi melakukan pengukuran ulang sebelum menandatangani berita acara serah terima.</p>
            </div>
        </div>

        <!-- 6. Technician Fleet Management -->
        <div id="technicians" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">6</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Manajemen Armada Teknisi</h2>
                    <span class="text-xs text-slate-500">Registrasi akun, aktivasi, dan pengawasan shift</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Melalui menu <b>Technicians</b> (`/technicians`), admin dapat:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Menambahkan teknisi baru dengan memasukkan nama, email, nomor WhatsApp, dan password awal.</li>
                    <li>Mengaktifkan / menonaktifkan status akun teknisi yang sedang cuti atau mutasi.</li>
                    <li>Melihat riwayat penyelesaian tugas tiap teknisi untuk keperluan evaluasi kinerja bulanan (KPI Performance).</li>
                </ul>
            </div>
        </div>

        <!-- 7. FAQ & Troubleshooting -->
        <div id="faq" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold">7</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Troubleshooting & Tanya Jawab Umum (FAQ)</h2>
                    <span class="text-xs text-slate-500">Solusi cepat kendala operasional harian</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600">
                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Teknisi mengeluh tidak bisa menekan tombol "Selesaikan Tugas" di HP?</h4>
                    <p class="mt-1 text-slate-600">A: Pastikan teknisi sudah mengunggah minimal 1 foto bukti pekerjaan di bagian bawah layar HP. Sistem keamanan secara otomatis menolak penyelesaian tugas tanpa foto bukti demi mencegah kecurangan lapangan.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Posisi teknisi tidak bergerak di peta Live Tracking?</h4>
                    <p class="mt-1 text-slate-600">A: Periksa apakah izin GPS lokasi di browser HP teknisi dalam kondisi "Diizinkan". Pastikan teknisi tidak berada di basement tertutup yang menghalangi sinyal satelit GPS.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Bagaimana cara mengatur interval refresh peta atau menguji bot notifikasi?</h4>
                    <p class="mt-1 text-slate-600">A: Buka menu <b>Settings</b> &rarr; atur interval polling peta ke 5 detik (ultra-realtime) dan masukkan URL bot Telegram/WhatsApp di bagian Webhook Alert Dispatcher.</p>
                </div>
            </div>
        </div>

        <!-- Back to Top / Footer Navigation -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-semibold flex items-center gap-1">
                &larr; Kembali ke Dashboard Admin
            </a>
            <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                Buka Pengaturan Sistem &rarr;
            </a>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scrolling with offset for TOC items
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    const el = document.querySelector(targetId);
                    if (el) {
                        e.preventDefault();
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        // Quick client-side search in guide
        const searchInput = document.getElementById('guide-search');
        const sections = document.querySelectorAll('.guide-section');
        const navItems = document.querySelectorAll('.guide-nav-item');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                sections.forEach(sec => {
                    const text = sec.innerText.toLowerCase();
                    if (!query || text.includes(query)) {
                        sec.style.display = '';
                    } else {
                        sec.style.display = 'none';
                    }
                });
            });
        }

        // Active link highlighting on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navItems.forEach(item => {
                        if (item.getAttribute('href') === '#' + id) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    });
                }
            });
        }, { rootMargin: '-20% 0px -70% 0px' });

        sections.forEach(sec => observer.observe(sec));
    });
</script>
@endpush
@endsection
