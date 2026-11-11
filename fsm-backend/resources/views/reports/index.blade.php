@extends('layouts.admin')

@section('title', 'Laporan & Analitik')
@section('header', 'Laporan & Analitik Operasional')

@push('styles')
<style>
    .metric-card {
        transition: all 0.25s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    @media print {
        #admin-sidebar, header, #filter-bar, .no-print {
            display: none !important;
        }
        body {
            background-color: white !important;
            color: black !important;
        }
        .bg-white {
            border: 1px solid #cbd5e1 !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')

<!-- Header & Quick Export Controls -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-textPrimary tracking-tight">Executive Performance & Field Analytics</h2>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                FSM Intelligence
            </span>
        </div>
        <p class="text-xs text-textSecondary mt-0.5">Analisis metrik produktivitas armada teknisi, kepatuhan SLA pekerjaan, dan dokumentasi bukti lapangan.</p>
    </div>

    <!-- Export & Print Action Buttons -->
    <div class="flex items-center gap-2 no-print">
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="px-3.5 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl shadow-sm shadow-emerald-600/20 transition-all flex items-center gap-1.5" title="Unduh data laporan dalam format CSV / Excel">
            <ion-icon name="cloud-download-outline" class="text-sm"></ion-icon>
            <span>Ekspor CSV</span>
        </a>
        <button type="button" onclick="window.print()" class="px-3.5 py-2 text-xs font-semibold bg-white border border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl shadow-sm transition-all flex items-center gap-1.5" title="Cetak atau simpan sebagai PDF">
            <ion-icon name="print-outline" class="text-sm"></ion-icon>
            <span>Cetak PDF</span>
        </button>
    </div>
</div>

<!-- Smart Filtering & Date Range Bar -->
<div id="filter-bar" class="bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm mb-6 no-print">
    <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
        <!-- Preset Timeframes -->
        <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-xs font-bold text-slate-500 mr-1 flex items-center gap-1">
                <ion-icon name="calendar-outline"></ion-icon> Periode:
            </span>
            @php
                $presets = [
                    'all'        => 'Semua Waktu',
                    'today'      => 'Hari Ini',
                    '7_days'     => '7 Hari',
                    '30_days'    => '30 Hari',
                    'this_month' => 'Bulan Ini',
                ];
            @endphp
            @foreach($presets as $key => $label)
                <a href="{{ route('admin.reports', array_merge(request()->query(), ['timeframe' => $key, 'start_date' => null, 'end_date' => null])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $timeframe === $key ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- Filters Form Elements -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Technician Filter -->
            <select name="technician_id" class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Teknisi</option>
                @foreach($techniciansList as $tech)
                    <option value="{{ $tech->id }}" {{ $technicianId == $tech->id ? 'selected' : '' }}>
                        {{ $tech->name }}
                    </option>
                @endforeach
            </select>

            <!-- Custom Date Inputs -->
            <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-xl px-2 py-1">
                <input type="date" name="start_date" value="{{ $startDate }}" class="bg-transparent text-xs text-slate-700 focus:outline-none" title="Tanggal Mulai">
                <span class="text-slate-400 text-xs">-</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="bg-transparent text-xs text-slate-700 focus:outline-none" title="Tanggal Selesai">
                <input type="hidden" name="timeframe" value="custom">
            </div>

            <!-- Submit Button -->
            <button type="submit" class="px-3.5 py-1.5 bg-primary hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all flex items-center gap-1">
                <ion-icon name="funnel-outline"></ion-icon>
                <span>Filter</span>
            </button>

            <!-- Reset Filter -->
            @if(request()->hasAny(['technician_id', 'start_date', 'end_date']) || $timeframe !== 'all')
                <a href="{{ route('admin.reports') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition-all" title="Reset semua filter">
                    <ion-icon name="refresh-outline"></ion-icon>
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Executive Summary Cards Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    
    <!-- Metric 1: Total Tasks & Completion -->
    <div class="metric-card bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider block">Tingkat Penyelesaian</span>
                <h3 class="text-2xl font-bold text-textPrimary leading-none mt-1.5">{{ $completionRate }}%</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <ion-icon name="checkmark-done-circle-outline"></ion-icon>
            </div>
        </div>
        <div class="mt-3">
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-primary h-1.5 rounded-full transition-all duration-500" style="width: {{ $completionRate }}%"></div>
            </div>
            <div class="flex justify-between items-center text-[10px] text-textSecondary mt-1.5 font-medium">
                <span>{{ $completedTasks }} Sukses</span>
                <span>Total {{ $totalTasks }} Tugas</span>
            </div>
        </div>
    </div>

    <!-- Metric 2: Proof Compliance Quality Gate -->
    <div class="metric-card bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider block">Kepatuhan Bukti Kerja</span>
                <h3 class="text-2xl font-bold text-emerald-600 leading-none mt-1.5">{{ $proofComplianceRate }}%</h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
            </div>
        </div>
        <div class="mt-3">
            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $proofComplianceRate }}%"></div>
            </div>
            <div class="flex justify-between items-center text-[10px] text-textSecondary mt-1.5 font-medium">
                <span>Dokumentasi Foto Lengkap</span>
                <a href="{{ route('admin.proofs') }}" class="text-blue-600 hover:underline">Lihat Proofs &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Metric 3: Average Resolution Time -->
    <div class="metric-card bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider block">Rata-Rata Waktu Selesai</span>
                <h3 class="text-2xl font-bold text-indigo-600 leading-none mt-1.5">{{ $avgResolutionHours }} <span class="text-xs font-normal text-textSecondary">Jam</span></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <ion-icon name="timer-outline"></ion-icon>
            </div>
        </div>
        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-textSecondary">
            <span class="flex items-center gap-1 font-medium text-emerald-600">
                <ion-icon name="flash-outline"></ion-icon> SLA Turnaround Terukur
            </span>
            <span>Dari pembuatan tiket</span>
        </div>
    </div>

    <!-- Metric 4: Urgent & Active Workload -->
    <div class="metric-card bg-white rounded-2xl border border-slate-200/70 p-4 shadow-sm flex flex-col justify-between">
        <div class="flex items-start justify-between">
            <div>
                <span class="text-[11px] font-semibold text-textSecondary uppercase tracking-wider block">Beban Kerja Aktif</span>
                <h3 class="text-2xl font-bold text-amber-600 leading-none mt-1.5">{{ $ongoingTasks + $pendingTasks }} <span class="text-xs font-normal text-textSecondary">Tiket</span></h3>
            </div>
            <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <ion-icon name="construct-outline"></ion-icon>
            </div>
        </div>
        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-textSecondary">
            <span class="font-medium text-red-600 flex items-center gap-1">
                <ion-icon name="warning-outline"></ion-icon> {{ $urgentTasks }} Prioritas Tinggi
            </span>
            <span>{{ $pendingTasks }} Perlu Penugasan</span>
        </div>
    </div>
</div>

<!-- Primary Visual Analytics Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <!-- Chart 1: 14-Day Timeline Trend (Takes 2 Columns) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 p-5 shadow-sm flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Tren Pekerjaan Lapangan (14 Hari Terakhir)</h3>
                <span class="text-[10px] text-textSecondary">Perbandingan tiket masuk vs pekerjaan yang tuntas diselesaikan</span>
            </div>
            <div class="flex items-center gap-3 text-xs font-semibold text-textSecondary">
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> Tiket Masuk</span>
                <span class="flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Tiket Selesai</span>
            </div>
        </div>
        <div class="flex-1 w-full min-h-[260px] max-h-[300px]">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Task Status Distribution (Takes 1 Column) -->
    <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-sm flex flex-col">
        <div class="mb-3">
            <h3 class="text-sm font-bold text-textPrimary">Distribusi Status Tiket</h3>
            <span class="text-[10px] text-textSecondary">Persentase status operasional seluruh penugasan</span>
        </div>
        <div class="flex-1 flex items-center justify-center max-h-[260px]">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<!-- Secondary Analytics Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <!-- Chart 3: Technician Performance Comparison (Takes 2 Columns) -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/70 p-5 shadow-sm flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Komparasi Produktivitas Teknisi</h3>
                <span class="text-[10px] text-textSecondary">Jumlah pekerjaan selesai dibandingkan dengan total tiket yang dialokasikan</span>
            </div>
        </div>
        <div class="flex-1 w-full min-h-[260px] max-h-[280px]">
            <canvas id="technicianChart"></canvas>
        </div>
    </div>

    <!-- Chart 4: Priority Distribution (Takes 1 Column) -->
    <div class="bg-white rounded-2xl border border-slate-200/70 p-5 shadow-sm flex flex-col">
        <div class="mb-3">
            <h3 class="text-sm font-bold text-textPrimary">Distribusi Tingkat Urgensi</h3>
            <span class="text-[10px] text-textSecondary">Komposisi prioritas (Low, Medium, High / Darurat)</span>
        </div>
        <div class="flex-1 flex items-center justify-center max-h-[260px]">
            <canvas id="priorityChart"></canvas>
        </div>
    </div>
</div>

<!-- Technician Benchmarking & Operational Performance Table -->
<div class="bg-white rounded-2xl border border-slate-200/70 shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <ion-icon name="podium-outline" class="text-xl text-primary"></ion-icon>
            <div>
                <h3 class="text-base font-bold text-textPrimary">Matriks Evaluasi Kinerja Armada Teknisi</h3>
                <span class="text-[10px] text-textSecondary">Benchmarking efisiensi penyelesaian tugas, bukti foto, dan status kehadiran</span>
            </div>
        </div>
        <span class="text-xs font-semibold text-slate-500">
            Total Armada: {{ $technicianCompletions->count() }} Teknisi
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Teknisi & Kontak</th>
                    <th class="p-4 text-center">Status Kehadiran</th>
                    <th class="p-4 text-center">Total Dialokasikan</th>
                    <th class="p-4 text-center">Tuntas (Selesai)</th>
                    <th class="p-4 text-center">Sedang Berjalan</th>
                    <th class="p-4 text-center">Foto Bukti Diserahkan</th>
                    <th class="p-4">Tingkat Keberhasilan</th>
                    <th class="p-4 text-center no-print">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-slate-100">
                @forelse($technicianCompletions as $tech)
                <tr class="hover:bg-blue-50/20 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                {{ substr($tech['name'], 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-textPrimary">{{ $tech['name'] }}</div>
                                <div class="text-[10px] text-textSecondary">{{ $tech['phone'] }} &bull; {{ $tech['email'] }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="p-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tech['is_online'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $tech['is_online'] ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            {{ $tech['is_online'] ? 'Online di Lapangan' : 'Offline' }}
                        </span>
                    </td>
                    <td class="p-4 text-center font-bold text-slate-700">{{ $tech['total_assigned'] }}</td>
                    <td class="p-4 text-center font-bold text-emerald-600">{{ $tech['completed'] }}</td>
                    <td class="p-4 text-center font-bold text-indigo-600">{{ $tech['active'] }}</td>
                    <td class="p-4 text-center">
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                            <ion-icon name="image-outline" class="text-slate-400"></ion-icon>
                            {{ $tech['proofs_count'] }}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $tech['completion_rate'] }}%"></div>
                            </div>
                            <span class="font-bold text-[11px] text-textPrimary">{{ $tech['completion_rate'] }}%</span>
                        </div>
                    </td>
                    <td class="p-4 text-center no-print">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('admin.tracking', ['technician_id' => $tech['id']]) }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-primary hover:text-white text-slate-600 transition-all" title="Lacak Live GPS">
                                <ion-icon name="locate-outline" class="text-sm"></ion-icon>
                            </a>
                            <a href="{{ route('admin.proofs', ['technician_id' => $tech['id']]) }}" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 transition-all" title="Lihat Bukti Foto">
                                <ion-icon name="shield-checkmark-outline" class="text-sm"></ion-icon>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-textSecondary">
                        Tidak ada data teknisi yang sesuai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // --- 1. 14-Day Timeline Trend Line Chart ---
        var trendCtx = document.getElementById('trendChart').getContext('2d');
        var gradientBlue = trendCtx.createLinearGradient(0, 0, 0, 250);
        gradientBlue.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        gradientBlue.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

        var gradientGreen = trendCtx.createLinearGradient(0, 0, 0, 250);
        gradientGreen.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
        gradientGreen.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [
                    {
                        label: 'Tiket Masuk',
                        data: @json($trendCreated),
                        borderColor: '#2563EB',
                        backgroundColor: gradientBlue,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2
                    },
                    {
                        label: 'Tiket Selesai',
                        data: @json($trendCompleted),
                        borderColor: '#10B981',
                        backgroundColor: gradientGreen,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 10 } },
                        grid: { color: '#F1F5F9' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // --- 2. Status Doughnut Chart ---
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Assigned', 'Accepted', 'On-Going', 'Completed', 'Rejected'],
                datasets: [{
                    data: [
                        {{ $statusCounts['pending'] ?? 0 }},
                        {{ $statusCounts['assigned'] ?? 0 }},
                        {{ $statusCounts['accepted'] ?? 0 }},
                        {{ $statusCounts['on-going'] ?? 0 }},
                        {{ $statusCounts['completed'] ?? 0 }},
                        {{ $statusCounts['rejected'] ?? 0 }},
                    ],
                    backgroundColor: [
                        '#94A3B8', // pending
                        '#3B82F6', // assigned
                        '#6366F1', // accepted
                        '#F59E0B', // ongoing
                        '#10B981', // completed
                        '#EF4444'  // rejected
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 10 },
                            boxWidth: 10,
                            padding: 8
                        }
                    }
                },
                cutout: '68%'
            }
        });

        // --- 3. Technician Performance Bar Chart ---
        var techData = @json($technicianCompletions);
        var techLabels = techData.map(function(t) { return t.name; });
        var techCompleted = techData.map(function(t) { return t.completed; });
        var techAssigned = techData.map(function(t) { return t.total_assigned; });

        new Chart(document.getElementById('technicianChart'), {
            type: 'bar',
            data: {
                labels: techLabels,
                datasets: [
                    {
                        label: 'Tuntas Selesai',
                        data: techCompleted,
                        backgroundColor: '#10B981',
                        borderRadius: 6
                    },
                    {
                        label: 'Total Dialokasikan',
                        data: techAssigned,
                        backgroundColor: '#3B82F6',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Inter', size: 11 }, boxWidth: 12 }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 10 } },
                        grid: { color: '#F1F5F9' }
                    },
                    x: {
                        ticks: { font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });

        // --- 4. Priority Bar Chart ---
        new Chart(document.getElementById('priorityChart'), {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High / Darurat'],
                datasets: [{
                    label: 'Jumlah Tiket',
                    data: [
                        {{ $priorityCounts['low'] ?? 0 }},
                        {{ $priorityCounts['medium'] ?? 0 }},
                        {{ $priorityCounts['high'] ?? 0 }}
                    ],
                    backgroundColor: ['#94A3B8', '#F59E0B', '#DC2626'],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter', size: 10 } },
                        grid: { color: '#F1F5F9' }
                    },
                    y: {
                        ticks: { font: { family: 'Inter', size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
