@extends('layouts.admin')

@section('title', 'Control Center Dashboard')
@section('header', 'Monitoring Control Center')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #dashboard-map { height: 440px; border-radius: 12px; z-index: 1; border: 1px solid #E2E8F0; }
    .sparkline-svg { width: 90px; height: 26px; }
    .tab-btn.active {
        background-color: #2563EB;
        color: #FFFFFF;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    .tab-btn:not(.active) {
        background-color: #F1F5F9;
        color: #475569;
    }
    .pulse-danger {
        animation: pulseDanger 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulseDanger {
        0%, 100% { opacity: 1; }
        50% { opacity: .4; }
    }
</style>
@endpush

@section('content')

<!-- Executive Control Bar -->
<div class="bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-blue-50 text-primary flex items-center justify-center text-xl flex-shrink-0">
            <ion-icon name="speedometer-outline"></ion-icon>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-textPrimary tracking-tight">Ringkasan Operasional FSM</h2>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500 animate-ping"></span> Real-time Live
                </span>
            </div>
            <p class="text-xs text-textSecondary mt-0.5">
                Monitoring penugasan lapangan, koordinat GPS armada teknisi, dan validasi bukti kerja secara terintegrasi.
            </p>
        </div>
    </div>

    <!-- Quick Action Shortcut Hub -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.tasks.create') }}" class="px-3.5 py-2 text-xs font-semibold bg-primary hover:bg-blue-700 text-white rounded-xl shadow-sm shadow-blue-500/20 transition-all flex items-center gap-1.5">
            <ion-icon name="add-circle-outline" class="text-sm"></ion-icon>
            <span>+ Buat Tugas</span>
        </a>
        <a href="{{ route('admin.tracking') }}" class="px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5">
            <ion-icon name="locate-outline" class="text-sm text-blue-600"></ion-icon>
            <span>Live Map</span>
        </a>
        <a href="{{ route('admin.proofs') }}" class="px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5">
            <ion-icon name="shield-checkmark-outline" class="text-sm text-emerald-600"></ion-icon>
            <span>Verifikasi Bukti</span>
        </a>
        <a href="{{ route('admin.guide') }}" class="px-3 py-2 text-xs font-semibold bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100 rounded-xl transition-all flex items-center gap-1" title="Buka Panduan Operasional">
            <ion-icon name="book-outline" class="text-sm"></ion-icon>
            <span>Panduan</span>
        </a>
    </div>
</div>

<!-- Structured KPI Row -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    
    <!-- KPI 1: Total Tasks -->
    <a href="{{ route('admin.tasks.index') }}" class="bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-blue-300 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">Total Tasks</span>
            <div class="p-1.5 rounded-xl bg-slate-100 text-slate-600 group-hover:bg-blue-50 group-hover:text-primary transition-colors">
                <ion-icon name="folder-open-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['total_tasks'] }}</h3>
                <span class="text-[10px] font-semibold text-blue-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="layers-outline"></ion-icon> Seluruh Tiket
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,25 Q15,10 30,22 T60,5 T90,12 T100,8" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,25 Q15,10 30,22 T60,5 T90,12 T100,8 L100,30 L0,30 Z" fill="rgba(37, 99, 235, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 2: On-Going Tasks (Active Operations) -->
    <a href="{{ route('admin.tasks.index', ['status' => 'on-going']) }}" class="bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-300 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">On-Going</span>
            <div class="p-1.5 rounded-xl bg-indigo-50 text-indigo-600 group-hover:scale-110 transition-transform">
                <ion-icon name="construct-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['on_going'] }}</h3>
                <span class="text-[10px] font-semibold text-indigo-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="flash-outline"></ion-icon> Sedang Dikerjakan
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,28 L20,20 L40,24 L60,12 L80,15 L100,2" fill="none" stroke="#4F46E5" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,28 L20,20 L40,24 L60,12 L80,15 L100,2 L100,30 L0,30 Z" fill="rgba(79, 70, 229, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 3: Pending Tasks (Need Dispatch) -->
    <a href="{{ route('admin.tasks.index', ['status' => 'pending']) }}" class="bg-white rounded-2xl border {{ $stats['pending'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/70' }} p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">Pending</span>
            <div class="p-1.5 rounded-xl bg-amber-50 text-amber-600 group-hover:scale-110 transition-transform">
                <ion-icon name="time-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['pending'] }}</h3>
                <span class="text-[10px] font-semibold text-amber-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="alert-circle-outline"></ion-icon> Perlu Alokasi
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,15 Q25,25 50,10 T100,18" fill="none" stroke="#D97706" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,15 Q25,25 50,10 T100,18 L100,30 L0,30 Z" fill="rgba(217, 119, 6, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 4: Urgent / High Priority Active -->
    <a href="{{ route('admin.dashboard', ['filter' => 'urgent']) }}" class="bg-white rounded-2xl border {{ $stats['urgent_active'] > 0 ? 'border-red-300 bg-red-50/20' : 'border-slate-200/70' }} p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">Darurat / High</span>
            <div class="p-1.5 rounded-xl bg-red-50 text-red-600 group-hover:scale-110 transition-transform {{ $stats['urgent_active'] > 0 ? 'pulse-danger' : '' }}">
                <ion-icon name="warning-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold {{ $stats['urgent_active'] > 0 ? 'text-red-600' : 'text-textPrimary' }} leading-none">{{ $stats['urgent_active'] }}</h3>
                <span class="text-[10px] font-semibold text-red-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="flame-outline"></ion-icon> Butuh Penanganan
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,20 L30,5 L60,25 L100,2" fill="none" stroke="#DC2626" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,20 L30,5 L60,25 L100,2 L100,30 L0,30 Z" fill="rgba(220, 38, 38, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 5: Field Completion Rate -->
    <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-emerald-300 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">Completion</span>
            <div class="p-1.5 rounded-xl bg-emerald-50 text-emerald-600 group-hover:scale-110 transition-transform">
                <ion-icon name="checkmark-done-circle-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['completion_rate'] }}%</h3>
                <span class="text-[10px] font-semibold text-emerald-600 flex items-center gap-0.5 mt-1">
                    <ion-icon name="shield-checkmark-outline"></ion-icon> {{ $stats['completed'] }} Selesai
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,20 Q20,10 40,25 T80,5 T100,2" fill="none" stroke="#16A34A" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,20 Q20,10 40,25 T80,5 T100,2 L100,30 L0,30 Z" fill="rgba(22, 163, 74, 0.08)"/>
            </svg>
        </div>
    </a>

    <!-- KPI 6: Active Technicians -->
    <a href="{{ route('admin.technicians.index') }}" class="bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-purple-300 transition-all flex flex-col justify-between block group">
        <div class="flex justify-between items-start">
            <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider">Armada Teknisi</span>
            <div class="p-1.5 rounded-xl bg-purple-50 text-purple-600 group-hover:scale-110 transition-transform">
                <ion-icon name="people-outline" class="text-sm"></ion-icon>
            </div>
        </div>
        <div class="mt-3 flex items-end justify-between">
            <div>
                <h3 class="text-2xl font-bold text-textPrimary leading-none">{{ $stats['online_technicians'] }}<span class="text-xs text-textSecondary font-normal"> / {{ $stats['active_technicians'] }}</span></h3>
                <span class="text-[10px] font-semibold text-purple-600 flex items-center gap-0.5 mt-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Online di Lapangan
                </span>
            </div>
            <svg class="sparkline-svg" viewBox="0 0 100 30">
                <path d="M0,15 L25,12 L50,15 L75,10 L100,5" fill="none" stroke="#7C3AED" stroke-width="2" stroke-linecap="round"/>
                <path d="M0,15 L25,12 L50,15 L75,10 L100,5 L100,30 L0,30 Z" fill="rgba(124, 58, 237, 0.08)"/>
            </svg>
        </div>
    </a>
</div>

<!-- Central Grid: Live Interactive Map (Col 2) & Telemetry Roster/Timeline (Col 1) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Map (2 columns) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 shadow-sm flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-2">
                <ion-icon name="map-outline" class="text-xl text-primary"></ion-icon>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-textPrimary">Live Dispatcher Tracking</h2>
                    <span class="text-[10px] text-textSecondary">Titik koordinat telemetri GPS teknisi aktif terkini</span>
                </div>
            </div>

            <!-- Map Controls & Legend -->
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 text-[10px] font-semibold text-textSecondary bg-slate-50 border border-slate-200/60 px-2.5 py-1 rounded-xl">
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-600"></span> Siap</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-500"></span> On-Going</span>
                    <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Offline</span>
                </div>
                <a href="{{ route('admin.tracking') }}" class="p-1.5 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-all text-xs font-semibold flex items-center gap-1" title="Buka Halaman Live Tracking Lengkap">
                    <ion-icon name="expand-outline"></ion-icon>
                    <span class="hidden sm:inline">Peta Penuh</span>
                </a>
            </div>
        </div>

        <div class="flex-1 min-h-[350px]">
            <div id="dashboard-map"></div>
        </div>
    </div>

    <!-- Dual-Tab Card: Timeline Activities & Fleet Roster -->
    <div class="bg-white rounded-2xl border border-slate-200/70 p-4 sm:p-5 shadow-sm flex flex-col">
        <!-- Tab Headers -->
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl">
                <button type="button" onclick="switchSideTab('timeline')" id="btn-tab-timeline" class="tab-btn active px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1">
                    <ion-icon name="time-outline"></ion-icon>
                    <span>Timeline</span>
                </button>
                <button type="button" onclick="switchSideTab('roster')" id="btn-tab-roster" class="tab-btn px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1">
                    <ion-icon name="people-outline"></ion-icon>
                    <span>Armada ({{ $fleetRoster->count() }})</span>
                </button>
            </div>
            <span class="text-[10px] text-slate-400">Auto-sync</span>
        </div>
        
        <!-- Tab 1: Timeline Activities -->
        <div id="tab-content-timeline" class="flex-1 overflow-y-auto max-h-[400px] pr-1 space-y-4">
            @forelse($timelineLogs as $log)
                <div class="flex gap-3 relative group">
                    @if(!$loop->last)
                        <div class="absolute left-3.5 top-6 bottom-[-16px] w-0.5 bg-slate-200 group-hover:bg-blue-300 transition-colors"></div>
                    @endif
                    
                    <div class="h-7 w-7 rounded-full flex items-center justify-center flex-shrink-0 z-10 
                        @if($log->progress_percent == 100) bg-green-100 text-green-700
                        @elseif($log->progress_percent >= 50) bg-amber-100 text-amber-700
                        @else bg-blue-100 text-blue-700 @endif shadow-xs">
                        <ion-icon name="
                            @if($log->progress_percent == 100) checkmark-circle
                            @elseif($log->progress_percent >= 50) construct
                            @else radio-button-on @endif
                        " class="text-sm"></ion-icon>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <span class="text-xs font-bold text-textPrimary block">{{ $log->user->name ?? 'System' }}</span>
                            <span class="text-[10px] text-textSecondary">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                        </div>
                        <p class="text-xs text-textSecondary mt-0.5 leading-snug">
                            {{ $log->note ?? 'Pembaruan progres pekerjaan' }}
                        </p>
                        <div class="flex items-center gap-2 mt-1.5">
                            @if($log->task)
                                <a href="{{ route('admin.tasks.show', $log->task->id) }}" class="text-[9px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 hover:bg-blue-50 hover:text-primary text-slate-700 border border-slate-200 truncate max-w-[150px] transition-all">
                                    #TSK-{{ str_pad($log->task->id, 4, '0', STR_PAD_LEFT) }} {{ $log->task->title }}
                                </a>
                            @endif
                            <span class="text-[9px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                                {{ $log->progress_percent }}%
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="h-full flex flex-col items-center justify-center text-center p-8 text-textSecondary">
                    <ion-icon name="chatbox-ellipses-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">Belum ada aktivitas terekam.</p>
                </div>
            @endforelse
        </div>

        <!-- Tab 2: Fleet Roster -->
        <div id="tab-content-roster" class="hidden flex-1 overflow-y-auto max-h-[400px] pr-1 space-y-3">
            @forelse($fleetRoster as $tech)
                <div class="p-3 rounded-xl border border-slate-200/70 hover:border-blue-300 bg-slate-50/50 hover:bg-white transition-all flex flex-col justify-between">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                    {{ substr($tech['name'], 0, 1) }}
                                </div>
                                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white {{ $tech['is_online'] ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-textPrimary">{{ $tech['name'] }}</h4>
                                <span class="text-[10px] text-textSecondary block">{{ $tech['phone'] ?? 'Tanpa nomor' }}</span>
                            </div>
                        </div>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full {{ $tech['is_online'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $tech['is_online'] ? 'Online' : 'Offline' }}
                        </span>
                    </div>

                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] flex justify-between items-center">
                        <span class="text-textSecondary text-[10px]">Tugas Aktif:</span>
                        @if($tech['active_task'])
                            <a href="{{ route('admin.tasks.show', $tech['active_task_id']) }}" class="text-[10px] font-bold text-primary hover:underline truncate max-w-[130px]" title="{{ $tech['active_task'] }}">
                                {{ $tech['active_task'] }}
                            </a>
                        @else
                            <span class="text-[10px] text-slate-400 italic">Standby</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-textSecondary text-xs">
                    Tidak ada teknisi terdaftar.
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Active Task Monitoring Table with Status Filter Presets -->
<div class="bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-hidden mb-6">
    <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
        <div class="flex items-center gap-2">
            <ion-icon name="list-circle-outline" class="text-xl text-primary"></ion-icon>
            <div>
                <h2 class="text-base font-bold text-textPrimary">Active Task Monitoring</h2>
                <span class="text-[10px] text-textSecondary">Daftar penugasan dan status pengerjaan operasional</span>
            </div>
        </div>

        <!-- Filter Presets Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
            <a href="{{ route('admin.dashboard', ['filter' => 'all']) }}" class="px-3 py-1.5 rounded-xl transition-all {{ $filter === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Semua ({{ $stats['total_tasks'] }})
            </a>
            <a href="{{ route('admin.dashboard', ['filter' => 'pending']) }}" class="px-3 py-1.5 rounded-xl transition-all {{ $filter === 'pending' ? 'bg-amber-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Pending ({{ $stats['pending'] }})
            </a>
            <a href="{{ route('admin.dashboard', ['filter' => 'ongoing']) }}" class="px-3 py-1.5 rounded-xl transition-all {{ $filter === 'ongoing' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                On-Going ({{ $stats['on_going'] }})
            </a>
            <a href="{{ route('admin.dashboard', ['filter' => 'urgent']) }}" class="px-3 py-1.5 rounded-xl transition-all {{ $filter === 'urgent' ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Darurat ({{ $stats['urgent_active'] }})
            </a>
            <a href="{{ route('admin.dashboard', ['filter' => 'completed']) }}" class="px-3 py-1.5 rounded-xl transition-all {{ $filter === 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Selesai ({{ $stats['completed'] }})
            </a>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Task ID</th>
                    <th class="p-4">Customer & Kontak</th>
                    <th class="p-4">Teknisi Bertugas</th>
                    <th class="p-4">Status & Progres</th>
                    <th class="p-4">Prioritas</th>
                    <th class="p-4">Lokasi Pekerjaan</th>
                    <th class="p-4">Bukti Foto</th>
                    <th class="p-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-100">
                @forelse($recentTasks as $task)
                <tr class="hover:bg-blue-50/20 transition-colors">
                    <td class="p-4 font-mono font-bold text-slate-600">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="hover:text-primary hover:underline">
                            #TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}
                        </a>
                    </td>
                    <td class="p-4">
                        <div class="font-bold text-textPrimary">{{ $task->customer_name }}</div>
                        @if($task->customer_phone)
                            <a href="tel:{{ $task->customer_phone }}" class="text-[10px] text-blue-600 hover:underline flex items-center gap-0.5">
                                <ion-icon name="call-outline"></ion-icon> {{ $task->customer_phone }}
                            </a>
                        @else
                            <span class="text-[10px] text-textSecondary">-</span>
                        @endif
                    </td>
                    <td class="p-4">
                        @if($task->technician)
                            <a href="{{ route('admin.tracking', ['technician_id' => $task->technician->id]) }}" class="flex items-center gap-2 hover:text-primary transition-all hover:underline">
                                <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center font-bold text-[10px] text-blue-700">
                                    {{ substr($task->technician->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-textPrimary hover:text-primary">{{ $task->technician->name }}</span>
                            </a>
                        @else
                            <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-[10px] font-semibold border border-amber-200">
                                Belum ditugaskan
                            </span>
                        @endif
                    </td>
                    <td class="p-4">
                        @php
                            $badgeColors = [
                                'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'on-going' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected' => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $colorClass = $badgeColors[$task->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $colorClass }}">
                                {{ $task->status_label }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-500">
                                {{ $task->progress_percent }}%
                            </span>
                        </div>
                    </td>
                    <td class="p-4">
                        @php
                            $priorityColors = [
                                'low' => 'text-slate-600 bg-slate-100 border-slate-200',
                                'medium' => 'text-amber-700 bg-amber-50 border-amber-200',
                                'high' => 'text-red-700 bg-red-50 border-red-200',
                            ];
                            $pColor = $priorityColors[$task->priority] ?? 'text-slate-600 bg-slate-100 border-slate-200';
                        @endphp
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $pColor }} capitalize inline-flex items-center gap-1">
                            @if($task->priority === 'high')
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            @endif
                            {{ $task->priority }}
                        </span>
                    </td>
                    <td class="p-4 text-textSecondary truncate max-w-[180px]" title="{{ $task->resolved_location }}">
                        <ion-icon name="location-outline" class="text-slate-400 align-middle"></ion-icon>
                        {{ $task->resolved_location }}
                    </td>
                    <td class="p-4">
                        @if($task->proofs->count() > 0)
                            <a href="{{ route('admin.proofs', ['search' => '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT)]) }}" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] hover:bg-emerald-100 transition-colors">
                                <ion-icon name="image-outline"></ion-icon>
                                <span>{{ $task->proofs->count() }} Foto</span>
                            </a>
                        @else
                            <span class="text-slate-400 text-[10px] italic">-</span>
                        @endif
                    </td>
                    <td class="p-4 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('admin.tasks.show', $task->id) }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-all" title="Buka Detail Tugas">
                                <ion-icon name="eye-outline" class="text-sm"></ion-icon>
                            </a>
                            @if($task->status === 'pending')
                                <a href="{{ route('admin.tasks.edit', $task->id) }}" class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 transition-all" title="Tugaskan Teknisi">
                                    <ion-icon name="person-add-outline" class="text-sm"></ion-icon>
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-textSecondary">
                        <ion-icon name="file-tray-outline" class="text-4xl mb-2 text-slate-300"></ion-icon>
                        <p class="text-xs font-semibold">Tidak ada data tugas yang sesuai dengan filter.</p>
                        @if($filter !== 'all')
                            <a href="{{ route('admin.dashboard') }}" class="text-[11px] font-bold text-primary hover:underline mt-1 inline-block">
                                Tampilkan semua tugas
                            </a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    // Tab switcher between Timeline & Fleet Roster
    function switchSideTab(tabName) {
        var tabTimeline = document.getElementById('tab-content-timeline');
        var tabRoster = document.getElementById('tab-content-roster');
        var btnTimeline = document.getElementById('btn-tab-timeline');
        var btnRoster = document.getElementById('btn-tab-roster');

        if (tabName === 'timeline') {
            tabTimeline.classList.remove('hidden');
            tabRoster.classList.add('hidden');
            btnTimeline.className = 'tab-btn active px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1';
            btnRoster.className = 'tab-btn px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1';
        } else {
            tabTimeline.classList.add('hidden');
            tabRoster.classList.remove('hidden');
            btnRoster.className = 'tab-btn active px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1';
            btnTimeline.className = 'tab-btn px-3 py-1 rounded-lg text-xs font-bold transition-all flex items-center gap-1';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        var map = L.map('dashboard-map').setView([-6.200000, 106.816666], 11);

        // OSM Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Standard Leaflet Icon Helper
        var createMarkerIcon = function(color) {
            return L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-' + color + '.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });
        };

        var icons = {
            active: createMarkerIcon('blue'),
            inactive: createMarkerIcon('grey'),
            ongoing: createMarkerIcon('gold')
        };

        // Escape HTML helper
        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Marker data
        var markerData = @json($markerData);
        var markers = [];

        markerData.forEach(function(item) {
            if (!item.latitude || !item.longitude) return;

            var iconType = icons[item.markerColor] || icons.active;
            var marker = L.marker([item.latitude, item.longitude], {
                icon: iconType
            }).addTo(map);

            var taskLink = item.taskId ? `{{ url('/tasks') }}/${item.taskId}` : `{{ route('admin.tasks.index') }}`;

            var popupContent = `
                <div style="font-family: 'Inter', sans-serif; font-size: 12px; width: 220px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                        <h4 style="margin: 0; font-weight: 700; color: #0F172A;">${escapeHtml(item.name)}</h4>
                        <span style="font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 999px; background: ${item.markerColor === 'ongoing' ? '#FEF3C7; color: #92400E' : (item.markerColor === 'active' ? '#DBEAFE; color: #1E40AF' : '#F1F5F9; color: #475569')};">
                            ${item.markerColor === 'ongoing' ? 'On-Going' : (item.markerColor === 'active' ? 'Standby' : 'Offline')}
                        </span>
                    </div>
                    <div style="color: #64748B; font-size: 11px; margin-bottom: 6px;">Email: ${escapeHtml(item.email)}</div>
                    <div style="background: #F8FAFC; border: 1px solid #E2E8F0; padding: 6px; border-radius: 8px; margin-bottom: 6px;">
                        <span style="font-weight: 600; color: #64748B; font-size: 10px; display: block;">Tugas Saat Ini:</span>
                        <a href="${taskLink}" style="color: #2563EB; font-weight: 600; text-decoration: none; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${escapeHtml(item.taskTitle)}
                        </a>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 4px;">
                        <span>GPS: ${item.latitude.toFixed(5)}, ${item.longitude.toFixed(5)}</span>
                        <a href="{{ route('admin.tracking') }}?technician_id=${item.tech_id}" style="color: #2563EB; font-weight: 700; text-decoration: none;">Track Live &rarr;</a>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
            markers.push(marker);
        });

        // Auto zoom and fit bounds of all active markers
        if (markers.length > 0) {
            var group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds(), {padding: [50, 50]});
        }
    });
</script>
@endpush
@endsection
