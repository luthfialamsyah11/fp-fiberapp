@extends('layouts.admin')

@section('title', 'Proof of Work')
@section('header', 'Verifikasi & Bukti Pekerjaan')

@push('styles')
<style>
    .proof-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .proof-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px -10px rgba(0, 0, 0, 0.1);
    }
    .img-zoom-container {
        position: relative;
        overflow: hidden;
        cursor: zoom-in;
    }
    .img-zoom-container img {
        transition: transform 0.3s ease;
    }
    .img-zoom-container:hover img {
        transform: scale(1.05);
    }
</style>
@endpush

@section('content')
<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-textPrimary tracking-tight">Completion Proofs & Quality Audits</h2>
        <p class="text-xs text-textSecondary mt-0.5">Inspeksi visual dokumentasi Before & After dari teknisi lapangan untuk verifikasi SLA dan validasi billing.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.tasks.index') }}" class="px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5">
            <ion-icon name="list-outline" class="text-sm"></ion-icon>
            <span>Daftar Tugas</span>
        </a>
        <a href="{{ route('admin.tracking') }}" class="px-3.5 py-2 text-xs font-semibold bg-primary hover:bg-blue-700 text-white rounded-xl shadow-sm shadow-blue-500/20 transition-all flex items-center gap-1.5">
            <ion-icon name="locate-outline" class="text-sm"></ion-icon>
            <span>Live Tracking</span>
        </a>
    </div>
</div>

<!-- KPI Metrics Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Metric 1: Total Proofs -->
    <div class="bg-white rounded-2xl border border-slate-200/60 p-4 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Total Foto Bukti</span>
            <div class="text-2xl font-bold text-textPrimary mt-1">{{ $metrics['total_proofs'] }}</div>
            <span class="text-[10px] font-medium text-blue-600 flex items-center gap-1 mt-0.5">
                <ion-icon name="cloud-upload-outline"></ion-icon> Tersimpan di storage
            </span>
        </div>
        <div class="h-11 w-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
            <ion-icon name="images-outline"></ion-icon>
        </div>
    </div>

    <!-- Metric 2: Verified Tasks -->
    <div class="bg-white rounded-2xl border border-slate-200/60 p-4 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Tiket Terverifikasi</span>
            <div class="text-2xl font-bold text-textPrimary mt-1">{{ $metrics['verified_tasks'] }}</div>
            <span class="text-[10px] font-medium text-emerald-600 flex items-center gap-1 mt-0.5">
                <ion-icon name="checkmark-done-circle-outline"></ion-icon> Pekerjaan tuntas 100%
            </span>
        </div>
        <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
            <ion-icon name="shield-checkmark-outline"></ion-icon>
        </div>
    </div>

    <!-- Metric 3: Active Contributing Techs -->
    <div class="bg-white rounded-2xl border border-slate-200/60 p-4 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Teknisi Pengunggah</span>
            <div class="text-2xl font-bold text-textPrimary mt-1">{{ $metrics['active_techs'] }}</div>
            <span class="text-[10px] font-medium text-purple-600 flex items-center gap-1 mt-0.5">
                <ion-icon name="people-outline"></ion-icon> Tim lapangan aktif
            </span>
        </div>
        <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
            <ion-icon name="person-circle-outline"></ion-icon>
        </div>
    </div>

    <!-- Metric 4: Latest Activity -->
    <div class="bg-white rounded-2xl border border-slate-200/60 p-4 shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Upload Terbaru</span>
            <div class="text-sm font-bold text-textPrimary mt-1 truncate max-w-[150px]">
                {{ $metrics['latest_upload_at'] ? \Carbon\Carbon::parse($metrics['latest_upload_at'])->diffForHumans() : 'Belum ada' }}
            </div>
            <span class="text-[10px] font-medium text-amber-600 flex items-center gap-1 mt-0.5">
                <ion-icon name="time-outline"></ion-icon> Real-time sync
            </span>
        </div>
        <div class="h-11 w-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
            <ion-icon name="refresh-circle-outline"></ion-icon>
        </div>
    </div>
</div>

<!-- Search & Filtering Toolbar -->
<div class="bg-white rounded-2xl border border-slate-200/60 p-4 shadow-sm mb-6">
    <form method="GET" action="{{ route('admin.proofs') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1">
            <ion-icon name="search-outline" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-base"></ion-icon>
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Cari ID tugas (#TSK-0001), pelanggan, atau kata kunci..."
                class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-textPrimary transition-all placeholder:text-slate-400"
            >
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Proof Status Filter -->
            <select name="proof_status" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary font-medium">
                <option value="with_proof" {{ request('proof_status', 'with_proof') == 'with_proof' ? 'selected' : '' }}>Ada Foto Bukti (Terverifikasi)</option>
                <option value="missing_proof" {{ request('proof_status') == 'missing_proof' ? 'selected' : '' }}>Belum Ada Foto (Menunggu Upload)</option>
                <option value="all" {{ request('proof_status') == 'all' ? 'selected' : '' }}>Semua Tiket</option>
            </select>

            <!-- Technician Filter -->
            <select name="technician_id" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Semua Teknisi</option>
                @foreach($technicians as $tech)
                    <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>
                        {{ $tech->name }}
                    </option>
                @endforeach
            </select>

            <!-- Date Filter -->
            <select name="date_filter" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                <option value="">Semua Waktu</option>
                <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>Hari Ini</option>
                <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>Minggu Ini</option>
                <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
            </select>

            <!-- Submit Button -->
            <button type="submit" class="px-4 py-2 bg-primary hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all flex items-center gap-1.5">
                <ion-icon name="funnel-outline"></ion-icon>
                <span>Filter</span>
            </button>

            <!-- Reset Filter if active -->
            @if(request()->hasAny(['search', 'technician_id', 'date_filter', 'proof_status']))
                <a href="{{ route('admin.proofs') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-all flex items-center gap-1" title="Reset filter">
                    <ion-icon name="close-circle-outline"></ion-icon>
                    <span>Reset</span>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Proof Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
    @forelse($tasksWithProof as $task)
        @php
            $proofList = $task->proofs;
            $before = $proofList->count() > 1 ? $proofList->first() : null;
            $after  = $proofList->last();
        @endphp

        <div class="proof-card bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-hidden flex flex-col justify-between">
            <!-- Card Header -->
            <div class="p-4 border-b border-slate-100 flex justify-between items-start bg-gradient-to-r from-slate-50 to-white">
                <div class="overflow-hidden pr-2">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-slate-200/70 text-slate-700">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                        @if($task->status === 'completed')
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                <ion-icon name="checkmark-circle"></ion-icon> Terverifikasi
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                {{ $task->status_label }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('admin.tasks.show', $task->id) }}" class="text-sm font-bold text-textPrimary hover:text-primary transition-colors truncate block" title="{{ $task->title }}">
                        {{ $task->title }}
                    </a>
                </div>

                <button 
                    type="button"
                    onclick="openInspectorModal({{ json_encode([
                        'id' => $task->id,
                        'title' => $task->title,
                        'customer' => $task->customer_name,
                        'phone' => $task->customer_phone,
                        'address' => $task->resolved_location,
                        'technician' => $task->technician ? $task->technician->name : 'Tidak Ditugaskan',
                        'status' => $task->status_label,
                        'priority' => ucfirst($task->priority),
                        'updated_at' => $task->updated_at ? $task->updated_at->format('d M Y H:i') : '-',
                        'proofs' => $task->proofs->map(function($p) {
                            return [
                                'image_url' => $p->image_url,
                                'description' => $p->description,
                                'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : '-'
                            ];
                        })
                    ]) }})"
                    class="p-2 rounded-xl bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-all shadow-sm flex items-center justify-center flex-shrink-0"
                    title="Buka Inspektor Bukti Resolusi Penuh"
                >
                    <ion-icon name="scan-outline" class="text-base"></ion-icon>
                </button>
            </div>

            <!-- Image Display Compartment (Handles 0, 1, or 2+ photos cleanly) -->
            @if($proofList->count() === 0)
                <div class="h-44 border-b border-slate-100 bg-slate-50 flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                    <ion-icon name="camera-outline" class="text-3xl mb-1.5 text-slate-300"></ion-icon>
                    <span class="text-xs font-bold text-slate-600">Dokumentasi Belum Diunggah</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Teknisi lapangan belum melampirkan foto verifikasi</span>
                </div>
            @elseif($proofList->count() === 1)
                @php $singleProof = $proofList->first(); @endphp
                <div class="relative h-44 img-zoom-container bg-slate-100 group border-b border-slate-100"
                     onclick="openInspectorModal({{ json_encode([
                        'id' => $task->id,
                        'title' => $task->title,
                        'customer' => $task->customer_name,
                        'phone' => $task->customer_phone,
                        'address' => $task->resolved_location,
                        'technician' => $task->technician ? $task->technician->name : 'Tidak Ditugaskan',
                        'status' => $task->status_label,
                        'priority' => ucfirst($task->priority),
                        'updated_at' => $task->updated_at ? $task->updated_at->format('d M Y H:i') : '-',
                        'proofs' => $task->proofs->map(function($p) {
                            return [
                                'image_url' => $p->image_url,
                                'description' => $p->description,
                                'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : '-'
                            ];
                        })
                    ]) }}, 0)">
                    <img src="{{ $singleProof->image_url }}" class="w-full h-full object-cover" alt="Proof" loading="lazy">
                    <div class="absolute inset-0 bg-slate-950/20 group-hover:bg-transparent transition-colors"></div>
                    <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[9px] font-bold bg-blue-600/95 text-white uppercase tracking-wider shadow-sm backdrop-blur-sm">Foto Bukti</span>
                    <div class="absolute bottom-2 right-2 p-1.5 rounded-lg bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                        <ion-icon name="search-outline" class="text-xs"></ion-icon>
                    </div>
                </div>
            @else
                <!-- Double-Image Compartment (Before & After) -->
                <div class="grid grid-cols-2 border-b border-slate-100 bg-slate-900/5 divide-x divide-slate-100">
                    <!-- Before Photo Frame -->
                    <div class="relative h-44 img-zoom-container bg-slate-100 group"
                         onclick="openInspectorModal({{ json_encode([
                            'id' => $task->id,
                            'title' => $task->title,
                            'customer' => $task->customer_name,
                            'phone' => $task->customer_phone,
                            'address' => $task->resolved_location,
                            'technician' => $task->technician ? $task->technician->name : 'Tidak Ditugaskan',
                            'status' => $task->status_label,
                            'priority' => ucfirst($task->priority),
                            'updated_at' => $task->updated_at ? $task->updated_at->format('d M Y H:i') : '-',
                            'proofs' => $task->proofs->map(function($p) {
                                return [
                                    'image_url' => $p->image_url,
                                    'description' => $p->description,
                                    'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : '-'
                                ];
                            })
                        ]) }}, 0)">
                        @if($before)
                            <img src="{{ $before->image_url }}" class="w-full h-full object-cover" alt="Before" loading="lazy">
                            <div class="absolute inset-0 bg-slate-950/20 group-hover:bg-transparent transition-colors"></div>
                            <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/95 text-white uppercase tracking-wider shadow-sm backdrop-blur-sm">Before</span>
                            <div class="absolute bottom-2 right-2 p-1.5 rounded-lg bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                <ion-icon name="search-outline" class="text-xs"></ion-icon>
                            </div>
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                                <ion-icon name="image-outline" class="text-2xl mb-1 text-slate-300"></ion-icon>
                                <span class="text-[10px] font-medium">Foto Awal Nihil</span>
                            </div>
                            <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[9px] font-bold bg-slate-400 text-white uppercase tracking-wider">Before</span>
                        @endif
                    </div>

                    <!-- After Photo Frame -->
                    <div class="relative h-44 img-zoom-container bg-slate-100 group"
                         onclick="openInspectorModal({{ json_encode([
                            'id' => $task->id,
                            'title' => $task->title,
                            'customer' => $task->customer_name,
                            'phone' => $task->customer_phone,
                            'address' => $task->resolved_location,
                            'technician' => $task->technician ? $task->technician->name : 'Tidak Ditugaskan',
                            'status' => $task->status_label,
                            'priority' => ucfirst($task->priority),
                            'updated_at' => $task->updated_at ? $task->updated_at->format('d M Y H:i') : '-',
                            'proofs' => $task->proofs->map(function($p) {
                                return [
                                    'image_url' => $p->image_url,
                                    'description' => $p->description,
                                    'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : '-'
                                ];
                            })
                        ]) }}, {{ $before ? 1 : 0 }})">
                        @if($after)
                            <img src="{{ $after->image_url }}" class="w-full h-full object-cover" alt="After" loading="lazy">
                            <div class="absolute inset-0 bg-slate-950/20 group-hover:bg-transparent transition-colors"></div>
                            <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-600/95 text-white uppercase tracking-wider shadow-sm backdrop-blur-sm">After</span>
                            <div class="absolute bottom-2 right-2 p-1.5 rounded-lg bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                <ion-icon name="search-outline" class="text-xs"></ion-icon>
                            </div>
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-slate-400 p-2 text-center">
                                <ion-icon name="image-outline" class="text-2xl mb-1 text-slate-300"></ion-icon>
                                <span class="text-[10px] font-medium">Foto Selesai Nihil</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Card Body / Context Metadata -->
            <div class="p-4 flex-1 flex flex-col justify-between">
                <div class="space-y-2.5 mb-3">
                    <!-- Customer & Address -->
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-textPrimary">{{ $task->customer_name }}</span>
                            @if($task->customer_phone)
                                <a href="tel:{{ $task->customer_phone }}" class="text-[11px] text-blue-600 hover:underline flex items-center gap-0.5">
                                    <ion-icon name="call-outline"></ion-icon> {{ $task->customer_phone }}
                                </a>
                            @endif
                        </div>
                        <p class="text-[11px] text-textSecondary truncate mt-0.5" title="{{ $task->resolved_location }}">
                            <ion-icon name="location-outline" class="text-slate-400 align-middle"></ion-icon> {{ $task->resolved_location }}
                        </p>
                    </div>

                    <!-- Dispatched Technician -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                        <span class="text-textSecondary text-[11px]">Teknisi Penanggung Jawab:</span>
                        <div class="flex items-center gap-1.5">
                            <div class="h-5 w-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[9px]">
                                {{ substr($task->technician ? $task->technician->name : 'N', 0, 1) }}
                            </div>
                            <span class="font-semibold text-textPrimary">{{ $task->technician ? $task->technician->name : 'Tidak Ditugaskan' }}</span>
                        </div>
                    </div>

                    <!-- Technician Note / Proof Description -->
                    @if($after && $after->description)
                        <div class="text-[11px] text-slate-700 bg-slate-50 border border-slate-200/60 rounded-xl p-2.5 leading-relaxed">
                            <span class="font-bold text-textPrimary block mb-0.5 text-[10px] uppercase tracking-wider text-slate-500">Catatan Pengerjaan:</span>
                            {{ $after->description }}
                        </div>
                    @endif
                </div>

                <!-- Footer Card Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[10px] text-slate-400 font-medium">
                        Diperbarui {{ $task->updated_at ? $task->updated_at->diffForHumans() : '-' }}
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="text-[11px] font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                            <span>Detail Tugas</span>
                            <ion-icon name="arrow-forward-outline"></ion-icon>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white border border-slate-200/70 rounded-2xl p-12 text-center shadow-sm">
            <div class="max-w-md mx-auto">
                <div class="h-16 w-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl">
                    <ion-icon name="images-outline"></ion-icon>
                </div>
                <h3 class="text-base font-bold text-textPrimary">Tidak Ada Bukti Pekerjaan Ditemukan</h3>
                <p class="text-xs text-textSecondary mt-1 leading-relaxed">
                    Tidak ditemukan data foto bukti yang sesuai dengan filter atau pencarian Anda. Pastikan teknisi telah mengunggah dokumentasi melalui aplikasi lapangan.
                </p>
                @if(request()->hasAny(['search', 'technician_id', 'date_filter']))
                    <a href="{{ route('admin.proofs') }}" class="inline-flex items-center gap-1.5 px-4 py-2 mt-4 bg-primary text-white text-xs font-semibold rounded-xl hover:bg-blue-700 transition-all shadow-sm">
                        <ion-icon name="refresh-outline"></ion-icon>
                        <span>Reset Semua Filter</span>
                    </a>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- Pagination -->
@if($tasksWithProof->hasPages())
    <div class="bg-white border border-slate-200/70 rounded-2xl p-4 shadow-sm mb-8">
        {{ $tasksWithProof->links() }}
    </div>
@endif

<!-- Interactive Proof Inspector Modal / Lightbox -->
<div id="inspector-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 transition-all duration-300">
    <div class="bg-white w-full max-w-5xl max-h-[92vh] rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-slate-700/20">
        <!-- Modal Top Bar -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div class="flex items-center gap-3">
                <span id="modal-task-badge" class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-700">#TSK-0000</span>
                <h3 id="modal-task-title" class="text-sm sm:text-base font-bold text-textPrimary truncate max-w-[280px] sm:max-w-md">Detail Bukti Pekerjaan</h3>
            </div>
            <div class="flex items-center gap-2">
                <a id="modal-download-btn" href="#" target="_blank" download class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-primary hover:border-blue-300 transition-all text-sm flex items-center gap-1" title="Unduh foto resolusi penuh">
                    <ion-icon name="download-outline"></ion-icon>
                </a>
                <button type="button" onclick="closeInspectorModal()" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-200 transition-all text-lg">
                    <ion-icon name="close-outline"></ion-icon>
                </button>
            </div>
        </div>

        <!-- Modal Content (Split: Image Showcase + Context Metadata) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 flex-1 overflow-hidden min-h-[400px]">
            <!-- Image Showcase (2 cols) -->
            <div class="lg:col-span-2 bg-slate-950 flex flex-col justify-between p-4 relative overflow-hidden">
                <!-- Proof Switcher Tabs -->
                <div id="modal-tabs" class="flex items-center gap-2 mb-3 z-10">
                    <!-- Populated via JS -->
                </div>

                <!-- Active Photo Display -->
                <div class="flex-1 flex items-center justify-center relative overflow-hidden">
                    <img id="modal-active-img" src="" alt="Proof high resolution" class="max-h-[60vh] max-w-full object-contain rounded-lg shadow-lg transition-transform duration-200">
                </div>

                <!-- Image Subtitle / Note -->
                <div class="mt-3 pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <span id="modal-img-meta">Foto Bukti Lapangan</span>
                    <span id="modal-img-timestamp">-</span>
                </div>
            </div>

            <!-- Task & Quality Audit Metadata (1 col) -->
            <div class="p-5 bg-white overflow-y-auto max-h-[65vh] flex flex-col justify-between space-y-4">
                <div class="space-y-4">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Status Pekerjaan</span>
                        <div class="flex items-center gap-2">
                            <span id="modal-task-status" class="px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 border border-green-200">Selesai</span>
                            <span id="modal-task-priority" class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 capitalize">Normal</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Pelanggan & Lokasi</span>
                        <h4 id="modal-customer-name" class="text-sm font-bold text-textPrimary">-</h4>
                        <div id="modal-customer-phone" class="text-xs text-blue-600 mt-0.5">-</div>
                        <p id="modal-task-address" class="text-xs text-textSecondary mt-1 leading-relaxed">-</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Teknisi Bertugas</span>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="h-7 w-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                <ion-icon name="person"></ion-icon>
                            </div>
                            <div>
                                <h5 id="modal-technician-name" class="text-xs font-bold text-textPrimary">-</h5>
                                <span class="text-[10px] text-slate-400">Field Service Engineer</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Catatan Pengerjaan</span>
                        <div id="modal-proof-desc" class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 leading-relaxed italic">
                            Tidak ada catatan tambahan.
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <a id="modal-task-detail-link" href="#" class="w-full py-2.5 bg-primary hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-1.5">
                        <ion-icon name="open-outline"></ion-icon>
                        <span>Buka Detail Tugas Lengkap</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var currentTaskData = null;
    var currentProofIndex = 0;

    function openInspectorModal(taskData, defaultIndex = 0) {
        currentTaskData = taskData;
        currentProofIndex = defaultIndex;

        // Populate Metadata
        document.getElementById('modal-task-badge').innerText = '#TSK-' + String(taskData.id).padStart(4, '0');
        document.getElementById('modal-task-title').innerText = taskData.title;
        document.getElementById('modal-customer-name').innerText = taskData.customer || '-';
        document.getElementById('modal-customer-phone').innerText = taskData.phone || '-';
        document.getElementById('modal-task-address').innerText = taskData.address || '-';
        document.getElementById('modal-technician-name').innerText = taskData.technician || '-';
        document.getElementById('modal-task-status').innerText = taskData.status || '-';
        document.getElementById('modal-task-priority').innerText = taskData.priority || 'Normal';
        document.getElementById('modal-task-detail-link').href = "{{ url('/tasks') }}/" + taskData.id;

        // Populate Tabs
        var tabsContainer = document.getElementById('modal-tabs');
        tabsContainer.innerHTML = '';

        if (taskData.proofs && taskData.proofs.length > 0) {
            taskData.proofs.forEach(function(proof, idx) {
                var btn = document.createElement('button');
                btn.type = 'button';
                var label = idx === 0 && taskData.proofs.length > 1 ? 'Foto Sebelum (Before)' : (idx === taskData.proofs.length - 1 && taskData.proofs.length > 1 ? 'Foto Selesai (After)' : 'Foto Bukti #' + (idx + 1));
                btn.className = `px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${idx === currentProofIndex ? 'bg-primary text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}`;
                btn.innerText = label;
                btn.onclick = function() {
                    switchProof(idx);
                };
                tabsContainer.appendChild(btn);
            });
            switchProof(currentProofIndex);
        } else {
            document.getElementById('modal-active-img').src = '';
            document.getElementById('modal-proof-desc').innerText = 'Belum ada foto bukti diunggah.';
        }

        // Show Modal
        var modal = document.getElementById('inspector-modal');
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function switchProof(index) {
        if (!currentTaskData || !currentTaskData.proofs[index]) return;
        currentProofIndex = index;
        var proof = currentTaskData.proofs[index];

        document.getElementById('modal-active-img').src = proof.image_url;
        document.getElementById('modal-download-btn').href = proof.image_url;
        document.getElementById('modal-proof-desc').innerText = proof.description || 'Tidak ada catatan pengerjaan spesifik pada foto ini.';
        document.getElementById('modal-img-meta').innerText = 'Foto #' + (index + 1) + ' dari ' + currentTaskData.proofs.length;
        document.getElementById('modal-img-timestamp').innerText = proof.created_at || '-';

        // Refresh Tab styles
        var tabsContainer = document.getElementById('modal-tabs');
        Array.from(tabsContainer.children).forEach(function(btn, idx) {
            if (idx === index) {
                btn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-primary text-white shadow';
            } else {
                btn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 hover:bg-slate-700';
            }
        });
    }

    function closeInspectorModal() {
        var modal = document.getElementById('inspector-modal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeInspectorModal();
        }
    });

    // Close when clicking modal backdrop
    document.getElementById('inspector-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeInspectorModal();
        }
    });
</script>
@endpush
@endsection
