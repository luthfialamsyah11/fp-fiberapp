@extends('layouts.admin')

@section('title', 'Control Center Dashboard')
@section('header', 'Monitoring Control Center')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #dashboard-map {
        height: 440px;
        border-radius: 12px;
        z-index: 1;
        border: 1px solid #E2E8F0;
    }
    @media (max-width: 640px) {
        #dashboard-map { height: 340px; }
    }
    .side-tab-btn.active {
        background-color: #0F172A;
        color: #FFFFFF;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.16);
    }
    .side-tab-btn:not(.active) {
        color: #64748B;
    }
    .side-tab-btn:not(.active):hover {
        color: #0F172A;
        background-color: rgba(255, 255, 255, 0.7);
    }
    .map-layer-btn.active {
        background-color: #0F172A;
        color: #FFFFFF;
    }
    .map-layer-btn:not(.active) {
        color: #475569;
        background-color: transparent;
    }
    .map-layer-btn:not(.active):hover {
        background-color: #F1F5F9;
        color: #0F172A;
    }
    .trend-bar {
        transition: height 280ms cubic-bezier(0.22, 1, 0.36, 1), opacity 150ms ease;
    }
    .dash-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
    .dash-scroll::-webkit-scrollbar-track { background: transparent; }
    .dash-scroll::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 999px; }
    .dash-scroll::-webkit-scrollbar-thumb:hover { background: #94A3B8; }
</style>
@endpush

@section('content')
@php
    $weekCreatedTotal = collect($weeklyTrend ?? [])->sum('created');
    $weekCompletedTotal = collect($weeklyTrend ?? [])->sum('completed');
    $weekResolutionPct = $weekCreatedTotal > 0
        ? min(100, round(($weekCompletedTotal / $weekCreatedTotal) * 100))
        : ($weekCompletedTotal > 0 ? 100 : 0);

    $jsMapStyle = $mapStyle ?? 'openstreetmap';
    $jsPollInterval = $pollInterval ?? 15;
    $jsLiveDataUrl = route('admin.tracking.live');
    $jsTasksBaseUrl = url('/tasks');
    $jsTasksIndexUrl = route('admin.tasks.index');
    $jsTrackingUrl = route('admin.tracking');
@endphp

<div class="space-y-6">

    <!-- 1. EXECUTIVE NOC COMMAND STRIP -->
    <section class="relative overflow-hidden rounded-2xl bg-slate-900 text-white border border-slate-800 p-5 sm:p-6 shadow-sm">
        <div class="absolute right-0 top-0 h-full w-1/3 bg-gradient-to-l from-blue-600/10 to-transparent pointer-events-none"></div>

        <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-5">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-blue-300 bg-blue-500/15 border border-blue-400/25 px-2.5 py-0.5 rounded-full">
                        {{ $companyName }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-400/25">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-400"></span>
                        </span>
                        Telemetry Live &bull; {{ $pollInterval }}s Poll
                    </span>
                    <span id="dash-last-sync-pill" class="text-[10px] font-mono text-slate-400 tabular-nums">
                        Synced {{ now()->format('H:i:s') }}
                    </span>
                </div>

                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white">
                        NOC Field Operations Command Center
                    </h2>
                    <p class="text-xs sm:text-[13px] text-slate-400 mt-1 max-w-2xl">
                        Real-time work order dispatch, {{ $onlineWindowMinutes }}-minute technician fleet presence, SLA breach monitoring ({{ $slaHours }}h target), and cryptographic BAST verification.
                    </p>
                </div>

                <!-- Actionable Live Operational Alerts -->
                @if($stats['sla_breached'] > 0 || $stats['low_stock_items'] > 0 || $stats['pending'] > 0)
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        @if($stats['sla_breached'] > 0)
                            <a href="{{ route('admin.dashboard', ['filter' => 'sla']) }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-red-500/15 hover:bg-red-500/25 border border-red-400/30 text-red-200 text-[11px] font-semibold transition-colors">
                                <ion-icon name="alert-circle" class="text-red-400 text-sm"></ion-icon>
                                <span class="tabular-nums">{{ $stats['sla_breached'] }} SLA Breached (&gt;{{ $slaHours }}h)</span>
                                <ion-icon name="chevron-forward" class="text-[10px]"></ion-icon>
                            </a>
                        @endif
                        @php
                            $dashCanDispatch = auth()->user()?->hasAnyRole(['admin', 'super_admin', 'noc_dispatcher']);
                            $dashCanInventory = auth()->user()?->hasAnyRole(['admin', 'super_admin', 'warehouse_admin', 'noc_dispatcher']);
                        @endphp
                        @if($stats['pending'] > 0 && $dashCanDispatch)
                            <a href="{{ route('admin.assignments') }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 border border-amber-400/30 text-amber-200 text-[11px] font-semibold transition-colors">
                                <ion-icon name="flash" class="text-amber-400 text-sm"></ion-icon>
                                <span class="tabular-nums">{{ $stats['pending'] }} Unassigned &mdash; Auto-Dispatch Ready</span>
                                <ion-icon name="chevron-forward" class="text-[10px]"></ion-icon>
                            </a>
                        @endif
                        @if($stats['low_stock_items'] > 0 && $dashCanInventory)
                            <a href="{{ route('admin.inventory.index') }}"
                               class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-[11px] font-semibold transition-colors">
                                <ion-icon name="cube-outline" class="text-amber-400 text-sm"></ion-icon>
                                <span class="tabular-nums">{{ $stats['low_stock_items'] }} Low-Stock Materials</span>
                                <ion-icon name="chevron-forward" class="text-[10px]"></ion-icon>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Primary Action Hub (44px Ergonomic Touch Targets) -->
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                @if(auth()->user()?->hasAnyRole(['admin', 'super_admin', 'noc_dispatcher']))
                    <a href="{{ route('admin.tasks.create') }}"
                       class="min-h-[40px] px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-500 active:scale-[0.98] text-white rounded-xl transition-all flex items-center gap-1.5 shadow-sm shadow-blue-950/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                        <ion-icon name="add-circle-outline" class="text-base"></ion-icon>
                        <span>New Work Order</span>
                    </a>
                    <a href="{{ route('admin.assignments') }}"
                       class="min-h-[40px] px-3.5 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 active:scale-[0.98] border border-slate-700 text-slate-100 rounded-xl transition-all flex items-center gap-1.5">
                        <ion-icon name="navigate-circle-outline" class="text-base text-amber-400"></ion-icon>
                        <span>Dispatch Queue</span>
                        @if($stats['pending'] > 0)
                            <span class="ml-0.5 px-1.5 py-0.5 rounded-md bg-amber-500 text-slate-950 text-[10px] font-extrabold tabular-nums">{{ $stats['pending'] }}</span>
                        @endif
                    </a>
                @endif
                <a href="{{ route('admin.tracking') }}"
                   class="min-h-[40px] px-3.5 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 active:scale-[0.98] border border-slate-700 text-slate-100 rounded-xl transition-all flex items-center gap-1.5">
                    <ion-icon name="locate-outline" class="text-base text-sky-400"></ion-icon>
                    <span>Full Map</span>
                </a>
                <a href="{{ route('admin.proofs') }}"
                   class="min-h-[40px] px-3.5 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-700 active:scale-[0.98] border border-slate-700 text-slate-100 rounded-xl transition-all flex items-center gap-1.5">
                    <ion-icon name="shield-checkmark-outline" class="text-base text-emerald-400"></ion-icon>
                    <span>Proof Audit</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 2. SIX-CARD OPERATIONAL KPI GRID (Real Metrics + Progress Bars, Tabular Numbers) -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">

        <!-- KPI 1: Total Work Orders -->
        <a href="{{ route('admin.tasks.index') }}"
           class="group bg-white rounded-2xl border border-slate-200/80 hover:border-blue-300 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Orders</span>
                <span class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-blue-50 text-slate-600 group-hover:text-blue-600 flex items-center justify-center transition-colors">
                    <ion-icon name="layers-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ number_format($stats['total_tasks']) }}</div>
                <div class="mt-1.5 flex items-center justify-between text-[11px]">
                    <span class="text-emerald-600 font-semibold tabular-nums">+{{ $stats['completed_today'] }} closed today</span>
                    <ion-icon name="arrow-forward" class="text-slate-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all"></ion-icon>
                </div>
            </div>
        </a>

        <!-- KPI 2: Pending Queue -->
        <a href="{{ route('admin.assignments') }}"
           class="group bg-white rounded-2xl border {{ $stats['pending'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/80' }} hover:border-amber-400 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pending Queue</span>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                    <ion-icon name="time-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold {{ $stats['pending'] > 0 ? 'text-amber-700' : 'text-slate-900' }} tabular-nums tracking-tight">{{ number_format($stats['pending']) }}</div>
                <div class="mt-1.5 flex items-center justify-between text-[11px]">
                    <span class="{{ $stats['pending'] > 0 ? 'text-amber-700 font-semibold' : 'text-slate-400 font-medium' }}">
                        {{ $stats['pending'] > 0 ? 'Awaiting dispatch' : 'Queue cleared' }}
                    </span>
                    <ion-icon name="arrow-forward" class="text-slate-300 group-hover:text-amber-600 group-hover:translate-x-0.5 transition-all"></ion-icon>
                </div>
            </div>
        </a>

        <!-- KPI 3: Active Field Operations -->
        <a href="{{ route('admin.dashboard', ['filter' => 'ongoing']) }}"
           class="group bg-white rounded-2xl border border-slate-200/80 hover:border-indigo-300 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Active Field</span>
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <ion-icon name="construct-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ number_format($stats['active_field']) }}</div>
                <div class="mt-1.5 flex items-center justify-between text-[11px] text-slate-500 tabular-nums">
                    <span><strong class="text-indigo-600">{{ $stats['on_going'] }}</strong> on-site &bull; {{ $stats['assigned'] + $stats['accepted'] }} en route</span>
                </div>
            </div>
        </a>

        <!-- KPI 4: Urgent & SLA Breaches -->
        <a href="{{ route('admin.dashboard', ['filter' => $stats['sla_breached'] > 0 ? 'sla' : 'urgent']) }}"
           class="group bg-white rounded-2xl border {{ ($stats['urgent_active'] > 0 || $stats['sla_breached'] > 0) ? 'border-red-300 bg-red-50/20' : 'border-slate-200/80' }} hover:border-red-400 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Urgent / SLA</span>
                <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                    <ion-icon name="warning-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3">
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold {{ $stats['urgent_active'] > 0 ? 'text-red-600' : 'text-slate-900' }} tabular-nums tracking-tight">{{ number_format($stats['urgent_active']) }}</span>
                    @if($stats['sla_breached'] > 0)
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700 border border-red-200 tabular-nums">{{ $stats['sla_breached'] }} SLA</span>
                    @endif
                </div>
                <div class="mt-1.5 text-[11px] {{ ($stats['urgent_active'] > 0 || $stats['sla_breached'] > 0) ? 'text-red-600 font-semibold' : 'text-emerald-600 font-semibold' }}">
                    {{ ($stats['urgent_active'] > 0 || $stats['sla_breached'] > 0) ? 'Priority escalation active' : 'All SLAs healthy' }}
                </div>
            </div>
        </a>

        <!-- KPI 5: Completion Rate -->
        <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}"
           class="group bg-white rounded-2xl border border-slate-200/80 hover:border-emerald-300 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Completion</span>
                <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <ion-icon name="checkmark-done-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3 space-y-1.5">
                <div class="flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">{{ $stats['completion_rate'] }}%</span>
                    <span class="text-[10px] font-semibold text-emerald-600 tabular-nums">{{ $stats['completed'] }} done</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ min(100, max(0, $stats['completion_rate'])) }}%"></div>
                </div>
            </div>
        </a>

        <!-- KPI 6: Online Fleet Presence -->
        <a href="{{ route('admin.technicians.index') }}"
           class="group bg-white rounded-2xl border border-slate-200/80 hover:border-blue-300 p-4 transition-all duration-150 active:scale-[0.99] flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Online Fleet</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <ion-icon name="people-outline" class="text-base"></ion-icon>
                </span>
            </div>
            <div class="mt-3 space-y-1.5">
                <div class="flex items-baseline justify-between">
                    <span class="text-2xl font-extrabold text-slate-900 tabular-nums tracking-tight">
                        {{ $stats['online_technicians'] }}<span class="text-xs font-medium text-slate-400"> / {{ $stats['active_technicians'] }}</span>
                    </span>
                    <span class="text-[10px] font-semibold text-blue-600 tabular-nums">{{ $stats['fleet_utilization'] }}%</span>
                </div>
                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: {{ min(100, max(0, $stats['fleet_utilization'])) }}%"></div>
                </div>
            </div>
        </a>
    </section>

    <!-- 3. OPERATIONAL ANALYTICS STRIP: 7-DAY THROUGHPUT & WORK ORDER PIPELINE -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- 7-Day Operational Throughput Chart (Real DB Data) -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">7-Day Work Order Throughput</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Daily ticket ingestion vs verified field completions</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-sm bg-slate-300"></span>
                        Created (<span class="tabular-nums text-slate-900">{{ $weekCreatedTotal }}</span>)
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 rounded-sm bg-blue-600"></span>
                        Completed (<span class="tabular-nums text-blue-700">{{ $weekCompletedTotal }}</span>)
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-7 gap-2 sm:gap-3 items-end h-36 pt-4 px-1 border-b border-slate-100 pb-2">
                @foreach($weeklyTrend as $day)
                    @php
                        $createdH = $day['created'] > 0 ? max(10, round(($day['created'] / $maxTrendValue) * 100)) : 4;
                        $completedH = $day['completed'] > 0 ? max(10, round(($day['completed'] / $maxTrendValue) * 100)) : 4;
                    @endphp
                    <div class="group flex flex-col items-center h-full justify-end gap-1.5"
                         title="{{ $day['day_num'] }}: {{ $day['created'] }} created, {{ $day['completed'] }} completed">
                        <div class="text-[10px] font-mono font-bold text-slate-400 group-hover:text-slate-800 transition-colors tabular-nums">
                            {{ $day['completed'] }}/{{ $day['created'] }}
                        </div>
                        <div class="w-full flex items-end justify-center gap-1 h-24">
                            <div class="trend-bar w-3 sm:w-4 rounded-t-md {{ $day['created'] > 0 ? 'bg-slate-300 group-hover:bg-slate-400' : 'bg-slate-100' }}"
                                 style="height: {{ $createdH }}%;"></div>
                            <div class="trend-bar w-3 sm:w-4 rounded-t-md {{ $day['completed'] > 0 ? 'bg-blue-600 group-hover:bg-blue-500' : 'bg-blue-100/70' }}"
                                 style="height: {{ $completedH }}%;"></div>
                        </div>
                        <span class="text-[10px] font-semibold {{ $day['is_today'] ? 'text-blue-700 font-bold' : 'text-slate-500' }}">
                            {{ $day['is_today'] ? 'Today' : $day['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex items-center justify-between text-[11px] text-slate-500">
                <span>7-day resolution ratio: <strong class="text-slate-800 tabular-nums">{{ $weekResolutionPct }}%</strong> &bull; Total proofs archived: <strong class="text-slate-800 tabular-nums">{{ number_format($stats['total_proofs']) }}</strong></span>
                <a href="{{ route('admin.reports') }}" class="text-blue-600 hover:underline font-semibold inline-flex items-center gap-1">
                    Full Analytics <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
            </div>
        </div>

        <!-- Work Order Pipeline & Operational Health -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Work Order Pipeline</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Live stage distribution across all orders</p>
                    </div>
                    <span class="text-[11px] font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-lg tabular-nums">
                        {{ $stats['total_tasks'] }} total
                    </span>
                </div>

                <!-- Segmented Multi-Stage Bar -->
                <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex gap-0.5 p-0.5 mb-4">
                    @foreach($statusBreakdown as $stage)
                        @if($stage['count'] > 0)
                            <a href="{{ $stage['url'] }}"
                               class="{{ $stage['bar'] }} h-full first:rounded-l-full last:rounded-r-full hover:opacity-85 transition-opacity"
                               style="width: {{ max(3, $stage['pct']) }}%;"
                               title="{{ $stage['label'] }}: {{ $stage['count'] }} ({{ $stage['pct'] }}%)"></a>
                        @endif
                    @endforeach
                </div>

                <!-- Clickable Stage Breakdown List -->
                <div class="space-y-1.5">
                    @foreach($statusBreakdown as $stage)
                        <a href="{{ $stage['url'] }}"
                           class="flex items-center justify-between px-2.5 py-1.5 rounded-xl hover:bg-slate-50 transition-colors text-xs group">
                            <span class="flex items-center gap-2 font-medium text-slate-700 group-hover:text-slate-900">
                                <span class="w-2 h-2 rounded-full {{ $stage['dot'] }}"></span>
                                {{ $stage['label'] }}
                            </span>
                            <span class="flex items-center gap-2 tabular-nums">
                                <span class="font-bold text-slate-900">{{ $stage['count'] }}</span>
                                <span class="text-[10px] text-slate-400 w-10 text-right">{{ $stage['pct'] }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Compact System Shortcuts Footer -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px]">
                <a href="{{ route('admin.inventory.index') }}" class="text-slate-600 hover:text-slate-900 font-semibold inline-flex items-center gap-1">
                    <ion-icon name="cube-outline" class="text-sm text-violet-600"></ion-icon>
                    <span>Inventory</span>
                    @if($stats['low_stock_items'] > 0)
                        <span class="px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 text-[10px] font-bold tabular-nums">{{ $stats['low_stock_items'] }} low</span>
                    @endif
                </a>
                <a href="{{ route('admin.settings') }}" class="text-slate-600 hover:text-slate-900 font-semibold inline-flex items-center gap-1">
                    <ion-icon name="settings-outline" class="text-sm text-slate-500"></ion-icon>
                    <span>SLA &amp; Guardrails</span>
                </a>
                <a href="{{ route('admin.guide') }}" class="text-blue-600 hover:underline font-semibold inline-flex items-center gap-1">
                    <ion-icon name="book-outline" class="text-sm"></ion-icon>
                    <span>SOP v2.0</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. CENTRAL COMMAND GRID: LIVE FLEET MAP (8 COLS) + FLEET & ACTIVITY RAIL (4 COLS) -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Live Fleet Telemetry Map -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm flex flex-col">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                        <ion-icon name="map-outline" class="text-lg"></ion-icon>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Live Fleet Telemetry Map</h3>
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-500">
                            <span>Unified GPS presence ({{ $onlineWindowMinutes }}-min window)</span>
                            <span>&bull;</span>
                            <span id="map-marker-count" class="font-semibold text-slate-700 tabular-nums">{{ $markerData->count() }} pinned</span>
                        </div>
                    </div>
                </div>

                <!-- Map Toolbar: Layer Switcher + Fit + Sync + Full Map -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center p-0.5 rounded-xl bg-slate-100 border border-slate-200/80 text-[11px] font-semibold">
                        <button type="button" data-map-layer="openstreetmap" class="map-layer-btn px-2.5 py-1 rounded-lg transition-all cursor-pointer">Street</button>
                        <button type="button" data-map-layer="carto" class="map-layer-btn px-2.5 py-1 rounded-lg transition-all cursor-pointer">Minimal</button>
                        <button type="button" data-map-layer="mapbox" class="map-layer-btn px-2.5 py-1 rounded-lg transition-all cursor-pointer">Satellite</button>
                    </div>

                    <button type="button" id="btn-map-fit" title="Fit all technicians"
                        class="h-8 px-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 active:scale-[0.97] text-slate-700 text-xs font-semibold inline-flex items-center gap-1 transition-all cursor-pointer">
                        <ion-icon name="scan-outline" class="text-sm"></ion-icon>
                        <span class="hidden sm:inline">Fit</span>
                    </button>

                    <button type="button" id="btn-map-sync" title="Sync GPS coordinates now"
                        class="h-8 px-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 active:scale-[0.97] text-slate-700 text-xs font-semibold inline-flex items-center gap-1 transition-all cursor-pointer">
                        <ion-icon id="icon-map-sync" name="refresh-outline" class="text-sm text-blue-600"></ion-icon>
                        <span class="hidden sm:inline">Sync</span>
                    </button>

                    <a href="{{ route('admin.tracking') }}"
                       class="h-8 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[0.97] text-white text-xs font-semibold inline-flex items-center gap-1 transition-all">
                        <ion-icon name="expand-outline" class="text-sm"></ion-icon>
                        <span>Full Map</span>
                    </a>
                </div>
            </div>

            <div class="relative flex-1 min-h-[340px]">
                <div id="dashboard-map"></div>
            </div>

            <!-- Unified Marker Legend (Matches /tracking & /guide v2.0) -->
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
                <div class="flex flex-wrap items-center gap-3 font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-slate-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Active Task
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-slate-700">
                        <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span> Online Standby
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-slate-500">
                        <span class="h-2.5 w-2.5 rounded-full bg-slate-400"></span> Offline / Stale
                    </span>
                </div>
                <span class="text-[10px] text-slate-400">Click any technician in Fleet Roster to fly directly to their GPS pin.</span>
            </div>
        </div>

        <!-- Dual-Tab Operational Rail: Fleet Roster & Live Activity -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm flex flex-col">
            <div class="flex items-center justify-between gap-2 mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl w-full sm:w-auto">
                    <button type="button" onclick="switchSideTab('roster')" id="btn-tab-roster"
                        class="side-tab-btn active flex-1 sm:flex-initial px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <ion-icon name="people-outline"></ion-icon>
                        <span>Fleet ({{ $fleetRoster->count() }})</span>
                    </button>
                    <button type="button" onclick="switchSideTab('timeline')" id="btn-tab-timeline"
                        class="side-tab-btn flex-1 sm:flex-initial px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <ion-icon name="pulse-outline"></ion-icon>
                        <span>Activity ({{ $timelineLogs->count() }})</span>
                    </button>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live
                </span>
            </div>

            <!-- Tab 1 (Default): Interactive Fleet Roster -->
            <div id="tab-content-roster" class="dash-scroll flex-1 overflow-y-auto max-h-[445px] pr-1 space-y-2.5">
                @forelse($fleetRoster as $tech)
                    <div class="p-3 rounded-xl border border-slate-200/80 hover:border-blue-300 bg-slate-50/40 hover:bg-white transition-all {{ $tech['has_gps'] ? 'cursor-pointer' : '' }}"
                         @if($tech['has_gps']) onclick="focusTechOnMap({{ $tech['id'] }})" title="Click to focus technician on map" @endif>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="relative flex-shrink-0">
                                    <div class="h-9 w-9 rounded-full {{ $tech['is_online'] ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($tech['name'], 0, 1)) }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white {{ $tech['is_online'] ? ($tech['active_task'] ? 'bg-emerald-500' : 'bg-blue-500') : 'bg-slate-400' }}"></span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 truncate">{{ $tech['name'] }}</h4>
                                    <div class="flex items-center gap-1.5 text-[10px] text-slate-500 mt-0.5">
                                        <span class="truncate">{{ $tech['last_ping'] }}</span>
                                        @if($tech['accuracy_m'] !== null)
                                            <span class="text-slate-300">&bull;</span>
                                            <span class="font-mono tabular-nums">&plusmn;{{ $tech['accuracy_m'] }}m</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $tech['is_online'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                                    {{ $tech['is_online'] ? 'Online' : 'Offline' }}
                                </span>
                                @if($tech['active_tasks_qty'] > 0)
                                    <span class="text-[10px] font-semibold text-indigo-600 tabular-nums">{{ $tech['active_tasks_qty'] }} active</span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px]">
                            @if($tech['active_task'])
                                <a href="{{ route('admin.tasks.show', $tech['active_task_id']) }}"
                                   onclick="event.stopPropagation()"
                                   class="font-semibold text-blue-600 hover:underline truncate flex-1"
                                   title="{{ $tech['active_task'] }}">
                                    {{ $tech['active_task'] }}
                                </a>
                            @else
                                <span class="text-slate-400 italic text-[10px]">Standby / Available</span>
                            @endif

                            @if($tech['has_gps'])
                                <span class="text-[10px] font-semibold text-slate-500 inline-flex items-center gap-0.5 flex-shrink-0">
                                    <ion-icon name="locate-outline" class="text-blue-600"></ion-icon> Pin
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 space-y-2">
                        <ion-icon name="people-outline" class="text-3xl text-slate-300"></ion-icon>
                        <p class="text-xs font-semibold text-slate-500">No field technicians registered yet.</p>
                        <a href="{{ route('admin.technicians.create') }}" class="inline-block text-xs font-bold text-blue-600 hover:underline">+ Add Technician</a>
                    </div>
                @endforelse
            </div>

            <!-- Tab 2: Live Activity Timeline -->
            <div id="tab-content-timeline" class="hidden dash-scroll flex-1 overflow-y-auto max-h-[445px] pr-1 space-y-4">
                @forelse($timelineLogs as $log)
                    <div class="flex gap-3 relative group">
                        @if(!$loop->last)
                            <div class="absolute left-3.5 top-7 bottom-[-16px] w-px bg-slate-200 group-hover:bg-blue-300 transition-colors"></div>
                        @endif

                        <div class="h-7 w-7 rounded-full flex items-center justify-center flex-shrink-0 z-10
                            @if($log->progress_percent == 100) bg-emerald-100 text-emerald-700
                            @elseif($log->progress_percent >= 50) bg-blue-100 text-blue-700
                            @else bg-slate-100 text-slate-600 @endif">
                            <ion-icon name="@if($log->progress_percent == 100) checkmark-circle @elseif($log->progress_percent >= 50) construct @else ellipse @endif" class="text-xs"></ion-icon>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-xs font-bold text-slate-900 truncate">{{ $log->user->name ?? 'System' }}</span>
                                <span class="text-[10px] text-slate-400 flex-shrink-0">{{ $log->created_at ? $log->created_at->diffForHumans() : '-' }}</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-0.5 leading-snug break-words">
                                {{ $log->note ?? 'Work order progress update' }}
                            </p>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                @if($log->task)
                                    <a href="{{ route('admin.tasks.show', $log->task->id) }}"
                                       class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-700 border border-slate-200/80 truncate max-w-[175px] transition-colors">
                                        #TSK-{{ str_pad($log->task->id, 4, '0', STR_PAD_LEFT) }} {{ $log->task->title }}
                                    </a>
                                @endif
                                <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded tabular-nums">
                                    {{ $log->progress_percent }}%
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 space-y-2">
                        <ion-icon name="pulse-outline" class="text-3xl text-slate-300"></ion-icon>
                        <p class="text-xs font-semibold text-slate-500">No field activity logged yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- 5. ACTIVE WORK ORDER MONITORING TABLE (Filter Presets + Quick Search + Inline Progress + SLA) -->
    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center flex-shrink-0">
                        <ion-icon name="clipboard-outline" class="text-lg"></ion-icon>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Recent &amp; Active Work Orders</h3>
                        <p class="text-[11px] text-slate-500">Latest 12 operational work orders matching active filter</p>
                    </div>
                </div>
                <a href="{{ route('admin.tasks.index') }}" class="xl:hidden text-xs font-bold text-blue-600 hover:underline whitespace-nowrap">View All &rarr;</a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                <!-- Instant Table Search -->
                <div class="relative">
                    <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></ion-icon>
                    <input type="text" id="dash-task-search" placeholder="Filter visible tickets..."
                           class="w-full sm:w-52 pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>

                <!-- Server Filter Tabs -->
                <div class="flex flex-wrap items-center gap-1 text-xs font-semibold">
                    <a href="{{ route('admin.dashboard', ['filter' => 'all']) }}"
                       class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        All ({{ $stats['total_tasks'] }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['filter' => 'pending']) }}"
                       class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Pending ({{ $stats['pending'] }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['filter' => 'ongoing']) }}"
                       class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'ongoing' ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Active ({{ $stats['on_going'] + $stats['accepted'] }})
                    </a>
                    <a href="{{ route('admin.dashboard', ['filter' => 'urgent']) }}"
                       class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'urgent' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Urgent ({{ $stats['urgent_active'] }})
                    </a>
                    @if($stats['sla_breached'] > 0 || $filter === 'sla')
                        <a href="{{ route('admin.dashboard', ['filter' => 'sla']) }}"
                           class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'sla' ? 'bg-red-700 text-white shadow-xs' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' }}">
                            SLA ({{ $stats['sla_breached'] }})
                        </a>
                    @endif
                    <a href="{{ route('admin.dashboard', ['filter' => 'completed']) }}"
                       class="px-2.5 py-1.5 rounded-xl transition-all tabular-nums {{ $filter === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                        Done ({{ $stats['completed'] }})
                    </a>
                </div>

                <a href="{{ route('admin.tasks.index') }}" class="hidden xl:inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:underline pl-1 whitespace-nowrap">
                    All Tasks <ion-icon name="arrow-forward-outline"></ion-icon>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/90 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Work Order</th>
                        <th class="py-3 px-4">Subscriber</th>
                        <th class="py-3 px-4">Technician</th>
                        <th class="py-3 px-4">Status &amp; Progress</th>
                        <th class="py-3 px-4">Priority</th>
                        <th class="py-3 px-4">Location</th>
                        <th class="py-3 px-4">Proofs</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="dash-tasks-tbody" class="text-xs divide-y divide-slate-100">
                    @forelse($recentTasks as $task)
                        @php
                            $isSlaBreach = $task->isSlaBreached($slaHours);
                            $badgeColors = [
                                'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                'assigned'  => 'bg-sky-50 text-sky-700 border-sky-200',
                                'accepted'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'on-going'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected'  => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $colorClass = $badgeColors[$task->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            $priorityColors = [
                                'low'    => 'text-slate-600 bg-slate-100 border-slate-200',
                                'medium' => 'text-amber-700 bg-amber-50 border-amber-200',
                                'high'   => 'text-red-700 bg-red-50 border-red-200',
                            ];
                            $pColor = $priorityColors[$task->priority] ?? 'text-slate-600 bg-slate-100 border-slate-200';
                            $progressVal = max(0, min(100, (int) ($task->progress_percent ?? 0)));
                            $techIsOnline = $task->technician ? ($task->technician->is_active && $task->technician->isEffectivelyOnline()) : false;
                        @endphp
                        <tr class="dash-task-row hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('admin.tasks.show', $task->id) }}" class="font-mono font-bold text-slate-900 hover:text-blue-600 hover:underline">
                                        #TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}
                                    </a>
                                    @if($isSlaBreach)
                                        <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700 border border-red-200 text-[9px] font-extrabold uppercase tracking-wide" title="Exceeds {{ $slaHours }}h SLA threshold">SLA</span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-500 truncate max-w-[190px] mt-0.5" title="{{ $task->title }}">{{ $task->title }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-900">{{ $task->customer_name }}</div>
                                @if($task->customer_phone)
                                    <a href="tel:{{ $task->customer_phone }}" class="text-[11px] text-blue-600 hover:underline inline-flex items-center gap-0.5 mt-0.5 tabular-nums">
                                        <ion-icon name="call-outline" class="text-[11px]"></ion-icon> {{ $task->customer_phone }}
                                    </a>
                                @else
                                    <span class="text-[10px] text-slate-400">No phone</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($task->technician)
                                    <a href="{{ route('admin.tracking', ['technician_id' => $task->technician->id]) }}"
                                       class="inline-flex items-center gap-2 group">
                                        <div class="relative flex-shrink-0">
                                            <div class="h-7 w-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">
                                                {{ strtoupper(substr($task->technician->name, 0, 1)) }}
                                            </div>
                                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white {{ $techIsOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                        </div>
                                        <span class="font-semibold text-slate-800 group-hover:text-blue-600 transition-colors">{{ $task->technician->name }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('admin.assignments') }}"
                                       class="inline-flex items-center gap-1 text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-lg text-[10px] font-bold border border-amber-200 transition-colors">
                                        <ion-icon name="flash-outline"></ion-icon>
                                        Unassigned
                                    </a>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 min-w-[140px]">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $colorClass }}">
                                        {{ $task->status_label }}
                                    </span>
                                    <span class="text-[10px] font-bold text-slate-600 tabular-nums">{{ $progressVal }}%</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $task->status === 'completed' ? 'bg-emerald-500' : ($task->status === 'rejected' ? 'bg-red-500' : 'bg-blue-600') }}"
                                         style="width: {{ $progressVal }}%"></div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $pColor }} capitalize inline-flex items-center gap-1">
                                    @if($task->priority === 'high')
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    @endif
                                    {{ $task->priority }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-slate-600 truncate max-w-[180px]" title="{{ $task->resolved_location }}">
                                <span class="inline-flex items-center gap-1 truncate max-w-full">
                                    <ion-icon name="location-outline" class="text-slate-400 flex-shrink-0"></ion-icon>
                                    <span class="truncate">{{ $task->resolved_location }}</span>
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($task->proofs->count() > 0)
                                    <a href="{{ route('admin.proofs', ['search' => '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT)]) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[10px] hover:bg-emerald-100 transition-colors tabular-nums">
                                        <ion-icon name="images-outline"></ion-icon>
                                        <span>{{ $task->proofs->count() }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-300 text-[11px]">&mdash;</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.tasks.show', $task->id) }}"
                                       class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-900 hover:text-white text-slate-600 inline-flex items-center justify-center transition-colors"
                                       title="View Command Details">
                                        <ion-icon name="eye-outline" class="text-sm"></ion-icon>
                                    </a>
                                    @if($task->status === 'completed')
                                        <a href="{{ route('admin.tasks.bast', $task->id) }}" target="_blank"
                                           class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 border border-emerald-200 inline-flex items-center justify-center transition-colors"
                                           title="Open BAST Certificate PDF">
                                            <ion-icon name="document-text-outline" class="text-sm"></ion-icon>
                                        </a>
                                    @else
                                        <a href="{{ route('admin.tasks.edit', $task->id) }}"
                                           class="w-8 h-8 rounded-lg {{ $task->status === 'pending' ? 'bg-amber-50 hover:bg-amber-600 text-amber-700 border border-amber-200' : 'bg-slate-100 hover:bg-blue-600 text-slate-600' }} hover:text-white inline-flex items-center justify-center transition-colors"
                                           title="{{ $task->status === 'pending' ? 'Edit & Assign Technician' : 'Edit Work Order' }}">
                                            <ion-icon name="{{ $task->status === 'pending' ? 'person-add-outline' : 'create-outline' }}" class="text-sm"></ion-icon>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-10 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2">
                                    <ion-icon name="file-tray-outline" class="text-2xl"></ion-icon>
                                </div>
                                <p class="text-xs font-bold text-slate-700">No work orders match this filter.</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Try clearing the filter or dispatch a new work order.</p>
                                @if($filter !== 'all')
                                    <a href="{{ route('admin.dashboard') }}" class="inline-block mt-3 px-3.5 py-1.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition-colors">
                                        Reset Filter
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    <tr id="dash-search-empty-row" class="hidden">
                        <td colspan="8" class="p-8 text-center text-slate-500 text-xs">
                            No visible rows match your quick search.
                            <a href="{{ route('admin.tasks.index') }}" class="text-blue-600 font-bold hover:underline ml-1">Search entire database &rarr;</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</div>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    function switchSideTab(tabName) {
        var tabTimeline = document.getElementById('tab-content-timeline');
        var tabRoster = document.getElementById('tab-content-roster');
        var btnTimeline = document.getElementById('btn-tab-timeline');
        var btnRoster = document.getElementById('btn-tab-roster');

        if (tabName === 'timeline') {
            tabTimeline.classList.remove('hidden');
            tabRoster.classList.add('hidden');
            btnTimeline.classList.add('active');
            btnRoster.classList.remove('active');
        } else {
            tabTimeline.classList.add('hidden');
            tabRoster.classList.remove('hidden');
            btnRoster.classList.add('active');
            btnTimeline.classList.remove('active');
        }
    }

    var dashMapInstance = null;
    var dashMarkerByTechId = {};

    function focusTechOnMap(techId) {
        var marker = dashMarkerByTechId[techId];
        if (!dashMapInstance || !marker) return;
        dashMapInstance.flyTo(marker.getLatLng(), 15, { duration: 0.65 });
        marker.openPopup();
    }

    document.addEventListener('DOMContentLoaded', function() {
        var mapStyle = @json($jsMapStyle);
        var pollIntervalSec = @json($jsPollInterval);
        var liveDataUrl = @json($jsLiveDataUrl);
        var tasksBaseUrl = @json($jsTasksBaseUrl);
        var tasksIndexUrl = @json($jsTasksIndexUrl);
        var trackingUrl = @json($jsTrackingUrl);
        var initialMarkers = @json($markerData);

        var map = L.map('dashboard-map', { zoomControl: true }).setView([-6.200000, 106.816666], 11);
        dashMapInstance = map;

        var tileConfigs = {
            openstreetmap: {
                url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                attr: '&copy; OpenStreetMap contributors'
            },
            carto: {
                url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                attr: '&copy; OpenStreetMap &copy; CARTO'
            },
            mapbox: {
                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                attr: 'Tiles &copy; Esri'
            }
        };

        var currentTileLayer = null;
        function setMapLayer(styleKey) {
            var cfg = tileConfigs[styleKey] || tileConfigs.openstreetmap;
            if (currentTileLayer) {
                map.removeLayer(currentTileLayer);
            }
            currentTileLayer = L.tileLayer(cfg.url, { maxZoom: 19, attribution: cfg.attr }).addTo(map);
            document.querySelectorAll('.map-layer-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.mapLayer === (tileConfigs[styleKey] ? styleKey : 'openstreetmap'));
            });
        }
        setMapLayer(mapStyle);

        document.querySelectorAll('.map-layer-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                setMapLayer(btn.dataset.mapLayer);
            });
        });

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

        // Unified marker icons matching /tracking & /guide v2.0
        var icons = {
            ongoing: createMarkerIcon('green'),
            active: createMarkerIcon('blue'),
            inactive: createMarkerIcon('grey')
        };

        function escapeHtml(str) {
            if (!str) return '';
            var div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        function buildPopupHtml(item) {
            var taskLink = item.taskId ? (tasksBaseUrl + '/' + item.taskId) : tasksIndexUrl;
            var badgeBg = item.markerColor === 'ongoing'
                ? '#DCFCE7; color: #166534'
                : (item.markerColor === 'active' ? '#DBEAFE; color: #1E40AF' : '#F1F5F9; color: #475569');
            var badgeLabel = item.markerColor === 'ongoing'
                ? 'Active Task'
                : (item.markerColor === 'active' ? 'Standby' : 'Offline');

            return '<div style="font-family: Inter, sans-serif; font-size: 12px; width: 225px;">'
                + '<div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:4px;">'
                + '<h4 style="margin:0;font-weight:700;color:#0F172A;font-size:12px;">' + escapeHtml(item.name) + '</h4>'
                + '<span style="font-size:9px;font-weight:700;padding:2px 7px;border-radius:999px;background:' + badgeBg + ';">' + badgeLabel + '</span>'
                + '</div>'
                + '<div style="color:#64748B;font-size:10px;margin-bottom:6px;">Last GPS: ' + escapeHtml(item.last_ping || 'Live')
                + (item.accuracy_m ? (' &bull; &plusmn;' + escapeHtml(item.accuracy_m) + 'm') : '') + '</div>'
                + '<div style="background:#F8FAFC;border:1px solid #E2E8F0;padding:6px 8px;border-radius:8px;margin-bottom:6px;">'
                + '<span style="font-weight:600;color:#64748B;font-size:9px;text-transform:uppercase;display:block;">Assignment</span>'
                + '<a href="' + taskLink + '" style="color:#2563EB;font-weight:600;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'
                + escapeHtml(item.taskTitle || 'Standby / Ready for Dispatch') + '</a></div>'
                + '<div style="display:flex;justify-content:space-between;align-items:center;font-size:10px;color:#64748B;border-top:1px solid #E2E8F0;padding-top:5px;">'
                + '<span style="font-family:monospace;">' + Number(item.latitude).toFixed(5) + ', ' + Number(item.longitude).toFixed(5) + '</span>'
                + '<a href="' + trackingUrl + '?technician_id=' + item.tech_id + '" style="color:#2563EB;font-weight:700;text-decoration:none;">Track &rarr;</a>'
                + '</div></div>';
        }

        function upsertMarker(item) {
            if (item.latitude === null || item.longitude === null || isNaN(Number(item.latitude))) return;
            var icon = icons[item.markerColor] || icons.active;
            var popupHtml = buildPopupHtml(item);

            if (dashMarkerByTechId[item.tech_id]) {
                dashMarkerByTechId[item.tech_id].setLatLng([item.latitude, item.longitude]);
                dashMarkerByTechId[item.tech_id].setIcon(icon);
                dashMarkerByTechId[item.tech_id].setPopupContent(popupHtml);
            } else {
                var m = L.marker([item.latitude, item.longitude], { icon: icon }).addTo(map);
                m.bindPopup(popupHtml);
                dashMarkerByTechId[item.tech_id] = m;
            }
        }

        initialMarkers.forEach(upsertMarker);

        function fitAllMarkers() {
            var all = Object.values(dashMarkerByTechId);
            if (all.length === 0) return;
            var group = new L.featureGroup(all);
            map.fitBounds(group.getBounds(), { padding: [48, 48], maxZoom: 15 });
        }
        fitAllMarkers();

        document.getElementById('btn-map-fit')?.addEventListener('click', fitAllMarkers);

        var syncIcon = document.getElementById('icon-map-sync');
        var lastSyncPill = document.getElementById('dash-last-sync-pill');
        var markerCountEl = document.getElementById('map-marker-count');

        async function syncLiveFleet(manual) {
            if (manual && syncIcon) syncIcon.classList.add('animate-spin');
            try {
                var resp = await fetch(liveDataUrl, { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) return;
                var data = await resp.json();
                if (!Array.isArray(data.technicians)) return;

                data.technicians.forEach(function(tech) {
                    if (tech.latitude === null || tech.longitude === null) return;
                    var colorKey = !tech.is_online ? 'inactive' : (tech.active_task ? 'ongoing' : 'active');
                    upsertMarker({
                        tech_id: tech.id,
                        name: tech.name,
                        latitude: Number(tech.latitude),
                        longitude: Number(tech.longitude),
                        accuracy_m: tech.accuracy_m || null,
                        last_ping: tech.last_ping || 'Just now',
                        markerColor: colorKey,
                        taskTitle: tech.active_task ? tech.active_task.title : 'Standby / Ready for Dispatch',
                        taskId: tech.active_task ? tech.active_task.id : null
                    });
                });

                if (markerCountEl) {
                    markerCountEl.textContent = Object.keys(dashMarkerByTechId).length + ' pinned';
                }
                if (lastSyncPill) {
                    var nowD = new Date();
                    lastSyncPill.textContent = 'Synced ' + nowD.toTimeString().slice(0, 8);
                }
            } catch (e) {
            } finally {
                if (manual && syncIcon) {
                    setTimeout(function() { syncIcon.classList.remove('animate-spin'); }, 350);
                }
            }
        }

        document.getElementById('btn-map-sync')?.addEventListener('click', function() {
            syncLiveFleet(true);
        });

        var dashPollMs = Math.max(8000, Number(pollIntervalSec) * 1000);
        var dashInterval = setInterval(function() {
            if (!document.hidden) syncLiveFleet(false);
        }, dashPollMs);

        window.addEventListener('beforeunload', function() {
            clearInterval(dashInterval);
        });

        // Quick Client-Side Filter on Visible Work Order Rows
        var searchInput = document.getElementById('dash-task-search');
        var rows = Array.from(document.querySelectorAll('.dash-task-row'));
        var emptySearchRow = document.getElementById('dash-search-empty-row');

        if (searchInput && rows.length > 0) {
            searchInput.addEventListener('input', function(e) {
                var q = (e.target.value || '').toLowerCase().trim();
                var matched = 0;
                rows.forEach(function(row) {
                    var show = !q || row.innerText.toLowerCase().includes(q);
                    row.style.display = show ? '' : 'none';
                    if (show) matched++;
                });
                if (emptySearchRow) {
                    emptySearchRow.classList.toggle('hidden', matched > 0);
                }
            });
        }
    });
</script>
@endpush
@endsection
