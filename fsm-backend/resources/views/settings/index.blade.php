@extends('layouts.admin')

@section('title', 'Pengaturan Platform & Sistem')
@section('header', 'Pengaturan Platform')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Banner: Shortcut ke Panduan Web Admin Lengkap -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-5 sm:p-6 text-white shadow-lg border border-blue-700/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-300 flex-shrink-0">
                <ion-icon name="book" class="text-2xl"></ion-icon>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-400/10 px-2.5 py-0.5 rounded-full border border-amber-400/20">
                        Dokumentasi Lengkap
                    </span>
                    <span class="text-[10px] text-slate-300">v1.2 Operations Manual</span>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-white mt-1">Panduan Penggunaan Web Admin FiberOps</h3>
                <p class="text-xs text-slate-300 mt-0.5 max-w-xl">
                    Pelajari SOP dispatching tiket, panduan membaca peta live tracking, verifikasi bukti kerja foto redaman OPM, dan manajemen armada teknisi.
                </p>
            </div>
        </div>
        <a href="{{ route('admin.guide') }}" class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition-all shadow-md active:scale-95 flex items-center gap-2 flex-shrink-0 relative z-10">
            <span>Buka Buku Panduan</span>
            <ion-icon name="arrow-forward-outline" class="text-sm"></ion-icon>
        </a>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3 shadow-sm">
            <ion-icon name="checkmark-circle" class="text-lg text-emerald-600 flex-shrink-0"></ion-icon>
            <span class="font-semibold">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Main Settings Form -->
    <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm">
        <div class="mb-6 border-b border-fsmBorder pb-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-textPrimary">Konfigurasi Pengaturan Sistem</h2>
                <p class="text-xs text-textSecondary">Atur identitas platform, guardrails SLA lapangan, integrasi webhook notifikasi, dan optimasi database.</p>
            </div>
            <span class="text-[11px] font-mono text-slate-400 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg">
                FSM Core v1.4
            </span>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="space-y-8">
                
                <!-- 1. Organization Details -->
                <div>
                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <ion-icon name="business-outline" class="text-sm"></ion-icon>
                        1. Informasi Perusahaan & Layanan
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Perusahaan / ISP</label>
                            <input type="text" name="company_name" value="{{ $settings['company_name'] }}" required
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Dukungan Pelanggan</label>
                            <input type="email" name="support_email" value="{{ $settings['support_email'] }}" required
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Hotline Dukungan Lapangan (Call Center)</label>
                            <input type="text" name="support_phone" value="{{ $settings['support_phone'] }}" required
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        </div>
                    </div>
                </div>

                <!-- 2. Dispatch & Tracking Engine -->
                <div class="border-t border-fsmBorder pt-6">
                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <ion-icon name="navigate-outline" class="text-sm"></ion-icon>
                        2. Mesin Dispatching & Live Tracking GPS
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Interval Polling GPS Peta (Detik)</label>
                            <select name="gps_refresh_interval" 
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="5" {{ $settings['gps_refresh_interval'] == '5' ? 'selected' : '' }}>5 Detik (Ultra Realtime)</option>
                                <option value="15" {{ $settings['gps_refresh_interval'] == '15' ? 'selected' : '' }}>15 Detik (Rekomendasi Standar)</option>
                                <option value="30" {{ $settings['gps_refresh_interval'] == '30' ? 'selected' : '' }}>30 Detik (Hemat Bandwidth)</option>
                                <option value="60" {{ $settings['gps_refresh_interval'] == '60' ? 'selected' : '' }}>1 Menit</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gaya Layer Peta Leaflet</label>
                            <select name="map_style" 
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="openstreetmap" {{ $settings['map_style'] == 'openstreetmap' ? 'selected' : '' }}>OpenStreetMap Standard</option>
                                <option value="carto" {{ $settings['map_style'] == 'carto' ? 'selected' : '' }}>CartoDB Positron (Minimalis)</option>
                                <option value="mapbox" {{ $settings['map_style'] == 'mapbox' ? 'selected' : '' }}>Satellite Hybrid Layer</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 mt-1">
                            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer select-none hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="auto_assign" value="1" 
                                    class="w-4 h-4 mt-0.5 text-blue-600 border-slate-300 rounded focus:ring-primary cursor-pointer" 
                                    {{ $settings['auto_assign'] == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Aktifkan Auto-Dispatch Berbasis Jarak Terdekat</span>
                                    <span class="text-[11px] text-slate-500">Sistem otomatis merekomendasikan teknisi yang sedang idle dengan radius terdekat saat tiket dibuat.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. NEW FEATURE 1: Operational SLA & Task Guardrails -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="shield-checkmark-outline" class="text-sm"></ion-icon>
                            3. Fitur Baru: Guardrails SLA & Integritas Bukti Lapangan
                        </h3>
                        <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-bold">Baru</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ambang Peringatan SLA Tiket (Target Waktu)</label>
                            <select name="sla_warning_hours" 
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="1" {{ $settings['sla_warning_hours'] == '1' ? 'selected' : '' }}>1 Jam (Kritis / Enterprise High SLA)</option>
                                <option value="2" {{ $settings['sla_warning_hours'] == '2' ? 'selected' : '' }}>2 Jam (Standar Gangguan WiFi & Fiber)</option>
                                <option value="4" {{ $settings['sla_warning_hours'] == '4' ? 'selected' : '' }}>4 Jam (Reguler Pasang Baru)</option>
                                <option value="8" {{ $settings['sla_warning_hours'] == '8' ? 'selected' : '' }}>8 Jam (Next Day Service)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Tiket akan otomatis berwarna merah bila belum ada progres melebihi waktu ini.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Toleransi Radius Geofencing Lokasi Pelanggan</label>
                            <select name="geofence_check_meters" 
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="0" {{ $settings['geofence_check_meters'] == '0' ? 'selected' : '' }}>Nonaktif (Bebas check-in)</option>
                                <option value="50" {{ $settings['geofence_check_meters'] == '50' ? 'selected' : '' }}>50 Meter (Sangat Ketat)</option>
                                <option value="100" {{ $settings['geofence_check_meters'] == '100' ? 'selected' : '' }}>100 Meter (Rekomendasi Area Padat)</option>
                                <option value="250" {{ $settings['geofence_check_meters'] == '250' ? 'selected' : '' }}>250 Meter (Area Luas / Kompleks)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Memperingatkan teknisi bila check-in tugas melebihi radius alamat pelanggan.</span>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/60 border border-emerald-200 cursor-pointer select-none hover:bg-emerald-50 transition-colors">
                                <input type="checkbox" name="require_proof_photo" value="1" 
                                    class="w-4 h-4 mt-0.5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 cursor-pointer" 
                                    {{ $settings['require_proof_photo'] == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Wajibkan Foto Bukti Pekerjaan Sebelum Selesaikan Tugas (Strict Quality Gate)</span>
                                    <span class="text-[11px] text-slate-500">Mencegah teknisi menandai tugas selesai tanpa mengunggah foto redaman OPM atau perangkat aktif.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 4. NEW FEATURE 2: Telegram & WhatsApp Webhook Alert Dispatcher -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="send-outline" class="text-sm"></ion-icon>
                            4. Fitur Baru: Webhook Alert & Integrasi Bot Telegram/WhatsApp
                        </h3>
                        <span class="text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-bold">Baru</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700">Webhook Endpoint URL (Bot Notification Hook)</label>
                                <span class="text-[10px] text-slate-400">Format: HTTPS POST endpoint</span>
                            </div>
                            <div class="flex gap-2">
                                <input type="url" id="webhook_url_input" name="webhook_url" value="{{ $settings['webhook_url'] }}" placeholder="https://api.telegram.org/bot.../sendMessage"
                                    class="flex-1 rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                <button type="button" id="btn-test-webhook" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all active:scale-95 flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                                    <ion-icon name="flash-outline" class="text-sm"></ion-icon>
                                    Uji Webhook
                                </button>
                            </div>
                            <span id="webhook-feedback" class="text-[11px] mt-1.5 hidden block"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_emergency_tickets" value="1" class="rounded text-indigo-600" {{ $settings['notify_emergency_tickets'] == '1' ? 'checked' : '' }}>
                                <span>Kirim Notifikasi Saat Ada Tiket Prioritas Tinggi (High/Emergency)</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_task_completed" value="1" class="rounded text-indigo-600" {{ $settings['notify_task_completed'] == '1' ? 'checked' : '' }}>
                                <span>Kirim Notifikasi Saat Teknisi Menyelesaikan Tugas</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 5. NEW FEATURE 3: System Optimization & Telemetry Retention -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-amber-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="server-outline" class="text-sm"></ion-icon>
                            5. Fitur Baru: Pemeliharaan Database & Retensi Telemetri GPS
                        </h3>
                        <span class="text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-bold">Baru</span>
                    </div>

                    <div class="bg-amber-50/50 border border-amber-200/80 rounded-xl p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase">Total Log GPS Tersimpan</div>
                                <div class="text-base font-bold text-slate-800 mt-0.5">{{ number_format($totalGpsPoints) }} Titik</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase">Estimasi Bobot Data Telemetri</div>
                                <div class="text-base font-bold text-slate-800 mt-0.5">{{ $databaseSize }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase">Masa Retensi Otomatis</div>
                                <select name="telemetry_retention_days" class="text-xs font-bold text-slate-800 bg-transparent outline-none w-full mt-0.5">
                                    <option value="7" {{ $settings['telemetry_retention_days'] == '7' ? 'selected' : '' }}>7 Hari</option>
                                    <option value="14" {{ $settings['telemetry_retention_days'] == '14' ? 'selected' : '' }}>14 Hari</option>
                                    <option value="30" {{ $settings['telemetry_retention_days'] == '30' ? 'selected' : '' }}>30 Hari (Default)</option>
                                    <option value="90" {{ $settings['telemetry_retention_days'] == '90' ? 'selected' : '' }}>90 Hari</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-[11px] text-slate-500">
                                Bersihkan log koordinat lama untuk mempercepat pemuatan Leaflet map.
                            </span>
                            <button type="button" id="btn-purge-cache" class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-all active:scale-95 flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                                <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                                Bersihkan Log Usang
                            </button>
                        </div>
                        <span id="purge-feedback" class="text-[11px] hidden block"></span>
                    </div>
                </div>

            </div>

            <!-- Submit Button Bar -->
            <div class="mt-8 pt-5 border-t border-fsmBorder flex items-center justify-between">
                <span class="text-xs text-slate-400">
                    Perubahan langsung berlaku ke seluruh armada teknisi dan admin dashboard.
                </span>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center gap-2 cursor-pointer">
                    <ion-icon name="save-outline" class="text-sm"></ion-icon>
                    Simpan Semua Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // 1. AJAX Test Webhook
        const btnTestWebhook = document.getElementById('btn-test-webhook');
        const webhookInput = document.getElementById('webhook_url_input');
        const webhookFeedback = document.getElementById('webhook-feedback');

        if (btnTestWebhook) {
            btnTestWebhook.addEventListener('click', async function() {
                btnTestWebhook.disabled = true;
                btnTestWebhook.innerHTML = '<ion-icon name="sync" class="animate-spin"></ion-icon> Menguji...';
                
                try {
                    const resp = await fetch("{{ route('admin.settings.test_webhook') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ webhook_url: webhookInput?.value || '' })
                    });
                    const res = await resp.json();
                    
                    webhookFeedback.className = 'text-[11px] mt-1.5 block text-emerald-600 font-semibold';
                    webhookFeedback.textContent = '✅ ' + res.message;
                } catch (e) {
                    webhookFeedback.className = 'text-[11px] mt-1.5 block text-red-600 font-semibold';
                    webhookFeedback.textContent = '❌ Gagal mengirim webhook uji coba: ' + e.message;
                } finally {
                    btnTestWebhook.disabled = false;
                    btnTestWebhook.innerHTML = '<ion-icon name="flash-outline" class="text-sm"></ion-icon> Uji Webhook';
                }
            });
        }

        // 2. AJAX Purge Telemetry Cache
        const btnPurge = document.getElementById('btn-purge-cache');
        const purgeFeedback = document.getElementById('purge-feedback');

        if (btnPurge) {
            btnPurge.addEventListener('click', async function() {
                if (!confirm('Apakah Anda yakin ingin mengarsipkan log telemetri GPS lama? Data rute aktif 100 titik terakhir tetap aman.')) {
                    return;
                }

                btnPurge.disabled = true;
                btnPurge.innerHTML = '<ion-icon name="sync" class="animate-spin"></ion-icon> Membersihkan...';

                try {
                    const resp = await fetch("{{ route('admin.settings.purge_cache') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const res = await resp.json();

                    purgeFeedback.className = 'text-[11px] block text-emerald-700 font-semibold mt-2';
                    purgeFeedback.textContent = '✅ ' + res.message;
                } catch (e) {
                    purgeFeedback.className = 'text-[11px] block text-red-600 font-semibold mt-2';
                    purgeFeedback.textContent = '❌ Gagal membersihkan cache: ' + e.message;
                } finally {
                    btnPurge.disabled = false;
                    btnPurge.innerHTML = '<ion-icon name="trash-outline" class="text-sm"></ion-icon> Bersihkan Log Usang';
                }
            });
        }
    });
</script>
@endpush
@endsection
