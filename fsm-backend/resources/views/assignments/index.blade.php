@extends('layouts.admin')

@section('title', 'Assignment Monitoring')
@section('header', 'Assignment Monitoring & Smart Dispatch')

@section('content')
<!-- KPI Overview Row (4 Clickable Cards) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <a href="{{ route('admin.assignments') }}" class="bg-white rounded-xl border {{ !request('status') ? 'border-blue-400 ring-1 ring-blue-100' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-textSecondary uppercase tracking-wider">Total Dispatched</span>
            <ion-icon name="paper-plane-outline" class="text-blue-600 text-lg"></ion-icon>
        </div>
        <h3 class="text-2xl font-bold text-textPrimary leading-none mt-2">{{ $totalAssigned }} <span class="text-sm font-medium text-slate-400">Tasks</span></h3>
        <span class="text-[11px] text-textSecondary mt-1.5 block">{{ $ongoingCount }} on-site &bull; {{ $completedCount }} completed</span>
    </a>

    <a href="{{ route('admin.assignments', ['status' => 'completed']) }}" class="bg-white rounded-xl border {{ request('status') === 'completed' ? 'border-emerald-400 ring-1 ring-emerald-100' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-textSecondary uppercase tracking-wider">Acceptance Rate</span>
            <ion-icon name="checkmark-done-circle-outline" class="text-emerald-600 text-lg"></ion-icon>
        </div>
        <h3 class="text-2xl font-bold text-emerald-600 leading-none mt-2">{{ $acceptanceRate }}%</h3>
        <span class="text-[11px] text-textSecondary mt-1.5 block">Accepted &amp; completed vs declined</span>
    </a>

    <a href="{{ route('admin.assignments', ['status' => 'assigned']) }}" class="bg-white rounded-xl border {{ request('status') === 'assigned' ? 'border-blue-400 ring-1 ring-blue-100' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-textSecondary uppercase tracking-wider">Awaiting Acceptance</span>
            <ion-icon name="hourglass-outline" class="text-blue-600 text-lg"></ion-icon>
        </div>
        <h3 class="text-2xl font-bold text-blue-600 leading-none mt-2">{{ $pendingAcceptance }} <span class="text-sm font-medium text-slate-400">Tasks</span></h3>
        <span class="text-[11px] text-textSecondary mt-1.5 block">Waiting for technician response in APK</span>
    </a>

    <a href="#decline-audit-feed" class="bg-white rounded-xl border {{ $rejectedCount > 0 ? 'border-red-200 bg-red-50/20' : 'border-fsmBorder' }} p-5 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold text-red-600 uppercase tracking-wider">Declined Events</span>
            <ion-icon name="close-circle-outline" class="text-red-500 text-lg"></ion-icon>
        </div>
        <h3 class="text-2xl font-bold text-red-600 leading-none mt-2">{{ $rejectedCount }}</h3>
        <span class="text-[11px] text-textSecondary mt-1.5 block">Recorded technician declines in audit log</span>
    </a>
</div>

<!-- Pending Dispatch Queue (Strictly High -> Medium -> Low, Zero Completed Leakage) -->
<div class="bg-white rounded-xl border {{ $pendingQueue->count() > 0 ? 'border-amber-200' : 'border-fsmBorder' }} shadow-sm mb-6 overflow-hidden">
    <div class="px-5 py-3.5 border-b {{ $pendingQueue->count() > 0 ? 'border-amber-200/70 bg-amber-50/70' : 'border-fsmBorder bg-slate-50/70' }} flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <ion-icon name="flash-outline" class="{{ $pendingQueue->count() > 0 ? 'text-amber-600' : 'text-emerald-600' }} text-base"></ion-icon>
            <h3 class="text-sm font-bold text-textPrimary">Pending Dispatch Queue ({{ $pendingQueue->count() }})</h3>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600">Sorted: High &rarr; Medium &rarr; Low</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.tasks.create') }}" class="text-[11px] font-bold text-primary hover:underline inline-flex items-center gap-1">
                <ion-icon name="add-circle-outline"></ion-icon> New Work Order
            </a>
        </div>
    </div>

    @if($pendingQueue->count() > 0)
    <div class="overflow-x-auto bg-white">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[10px] font-semibold uppercase tracking-wider">
                    <th class="p-3.5">Work Order &amp; SLA</th>
                    <th class="p-3.5">Customer &amp; Site</th>
                    <th class="p-3.5">Priority</th>
                    <th class="p-3.5">GPS Recommendation</th>
                    <th class="p-3.5">Dispatch Action</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @foreach($pendingQueue as $pendingTask)
                @php
                    $isSla = $pendingTask->isSlaBreached($slaHours ?? 2);
                    $priorityBadge = match ($pendingTask->priority) {
                        'high'   => 'bg-red-50 text-red-700 border-red-200',
                        'medium' => 'bg-amber-50 text-amber-700 border-amber-200',
                        default  => 'bg-slate-100 text-slate-600 border-slate-200',
                    };
                @endphp
                <tr class="hover:bg-slate-50/60 {{ $isSla ? 'bg-rose-50/25' : '' }}">
                    <td class="p-3.5 align-top">
                        <a href="{{ route('admin.tasks.show', $pendingTask->id) }}" class="font-bold text-textPrimary hover:text-primary block">
                            {{ $pendingTask->title }}
                        </a>
                        <div class="flex items-center flex-wrap gap-1.5 mt-1 text-[10px] text-textSecondary">
                            <span class="font-mono font-bold text-slate-600">#TSK-{{ str_pad($pendingTask->id, 4, '0', STR_PAD_LEFT) }}</span>
                            <span>&bull;</span>
                            <span>{{ $pendingTask->created_at->diffForHumans() }}</span>
                            @if($pendingTask->status === 'rejected')
                                <span class="px-1.5 py-0.2 rounded bg-red-100 text-red-700 font-bold uppercase">Rejected</span>
                            @endif
                            @if($isSla)
                                <span class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-700 border border-rose-200 font-bold uppercase">SLA Overdue</span>
                            @endif
                        </div>
                    </td>
                    <td class="p-3.5 align-top">
                        <div class="font-semibold text-textPrimary">{{ $pendingTask->customer_name }}</div>
                        <div class="text-[10px] text-textSecondary truncate max-w-[220px]" title="{{ $pendingTask->resolved_location }}">{{ $pendingTask->resolved_location }}</div>
                    </td>
                    <td class="p-3.5 align-top">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $priorityBadge }}">
                            {{ $pendingTask->priority }}
                        </span>
                    </td>
                    <td class="p-3.5 align-top">
                        @if($pendingTask->recommended_tech)
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
                                    <ion-icon name="navigate-circle-outline" class="text-sm pointer-events-none"></ion-icon>
                                    {{ $pendingTask->recommended_tech->name }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    @if($pendingTask->recommended_distance_km !== null)
                                        {{ $pendingTask->recommended_distance_km }} km &bull;
                                    @endif
                                    {{ $pendingTask->recommended_tech->tasks_count }} active
                                    @if($pendingTask->recommended_tech->effectively_online)
                                        &bull; <span class="text-emerald-600 font-semibold">Online</span>
                                    @endif
                                </span>
                            </div>
                        @else
                            <span class="text-[10px] text-slate-400">No active techs</span>
                        @endif
                    </td>
                    <td class="p-3.5 align-top">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($pendingTask->recommended_tech)
                                <form action="{{ route('admin.assignments.auto_dispatch', $pendingTask->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-lg shadow-sm transition-all inline-flex items-center gap-1 whitespace-nowrap"
                                        title="Auto-Dispatch to {{ $pendingTask->recommended_tech->name }}">
                                        <ion-icon name="flash-outline" class="pointer-events-none"></ion-icon> Auto-Dispatch
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.tasks.assign', $pendingTask->id) }}" method="POST" class="flex items-center gap-1.5">
                                @csrf
                                <select name="technician_id" required class="px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-textPrimary focus:outline-none focus:ring-2 focus:ring-primary/20 max-w-[180px]">
                                    <option value="">Choose tech...</option>
                                    @foreach($technicians as $tech)
                                        <option value="{{ $tech->id }}" {{ ($pendingTask->recommended_tech?->id ?? $pendingTask->technician_id) == $tech->id ? 'selected' : '' }}>
                                            {{ $tech->name }} ({{ $tech->tasks_count }} active){{ $tech->effectively_online ? ' ●' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-3 py-1.5 bg-primary hover:bg-blue-700 text-white text-[11px] font-bold rounded-lg shadow-sm transition-all whitespace-nowrap">
                                    Dispatch
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="p-6 text-center text-xs text-emerald-700 bg-emerald-50/30 flex items-center justify-center gap-2">
        <ion-icon name="checkmark-circle" class="text-base text-emerald-600"></ion-icon>
        <span class="font-semibold">All pending tickets have been dispatched. Dispatch queue is clear!</span>
    </div>
    @endif
</div>

<!-- Main 2-Column Grid: Recent Dispatches + Right Sidebar (Workload + Decline Audit) -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    <!-- Left Column (2/3): Filterable Recent Dispatches & Timelines -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-fsmBorder shadow-sm flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-fsmBorder flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Recent Dispatches &amp; Timelines</h3>
                <p class="text-[11px] text-textSecondary">Live sync with Technician APK acceptance, check-in, and completion events.</p>
            </div>
            <a href="{{ route('admin.tracking') }}" class="text-[11px] font-semibold text-blue-600 hover:underline inline-flex items-center gap-1">
                <ion-icon name="map-outline"></ion-icon> Open Live Map
            </a>
        </div>

        <!-- Filter Bar -->
        <div class="p-3.5 bg-slate-50 border-b border-fsmBorder">
            <form action="{{ route('admin.assignments') }}" method="GET" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <ion-icon name="search-outline" class="absolute left-3 top-2.5 text-slate-400 text-sm pointer-events-none"></ion-icon>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search #TSK-ID, title, or customer..."
                           class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                <select name="status" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white focus:outline-none">
                    <option value="">All Statuses</option>
                    <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Assigned (Awaiting)</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted (En Route)</option>
                    <option value="on-going" {{ request('status') === 'on-going' ? 'selected' : '' }}>On-Going (On-Site)</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select name="technician_id" onchange="this.form.submit()" class="text-xs border border-slate-200 rounded-lg px-2.5 py-1.5 bg-white focus:outline-none">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ (string) request('technician_id') === (string) $tech->id ? 'selected' : '' }}>
                            {{ $tech->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">Filter</button>
                @if(request()->hasAny(['search', 'status', 'technician_id']))
                    <a href="{{ route('admin.assignments') }}" class="px-2.5 py-1.5 bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold inline-flex items-center justify-center">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[10px] font-semibold uppercase tracking-wider">
                        <th class="p-3.5">Task &amp; Customer</th>
                        <th class="p-3.5">Assigned Agent</th>
                        <th class="p-3.5">Status &amp; Progress</th>
                        <th class="p-3.5">Updated</th>
                        <th class="p-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-fsmBorder">
                    @forelse($assignments as $assignment)
                    @php
                        $statusColors = [
                            'pending'   => 'bg-slate-100 text-slate-700 border-slate-200',
                            'assigned'  => 'bg-blue-50 text-blue-700 border-blue-200',
                            'accepted'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                            'on-going'  => 'bg-amber-50 text-amber-700 border-amber-200',
                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'rejected'  => 'bg-red-50 text-red-700 border-red-200',
                        ];
                        $pct = (int) $assignment->progress_percent;
                        $asgSla = $assignment->isSlaBreached($slaHours ?? 2);
                        $asgCode = '#TSK-' . str_pad($assignment->id, 4, '0', STR_PAD_LEFT);
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors {{ $asgSla ? 'bg-rose-50/20' : '' }}">
                        <td class="p-3.5 align-top">
                            <a href="{{ route('admin.tasks.show', $assignment->id) }}" class="font-bold text-textPrimary hover:text-primary transition-all block">
                                {{ $assignment->title }}
                            </a>
                            <div class="text-[10px] text-textSecondary mt-0.5 flex items-center gap-1.5 flex-wrap">
                                <span class="font-mono font-bold text-slate-600">{{ $asgCode }}</span>
                                <span>&bull;</span>
                                <span>{{ $assignment->customer_name }}</span>
                                @if($asgSla)
                                    <span class="px-1.5 py-0.2 rounded bg-rose-100 text-rose-700 font-bold uppercase text-[9px]">SLA</span>
                                @endif
                            </div>
                        </td>
                        <td class="p-3.5 align-top">
                            @if($assignment->technician)
                                <a href="{{ route('admin.tracking', ['technician_id' => $assignment->technician->id]) }}" class="flex items-center gap-2 hover:text-primary">
                                    <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center font-bold text-[10px] text-blue-700 flex-shrink-0">
                                        {{ strtoupper(substr($assignment->technician->name, 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-textPrimary">{{ $assignment->technician->name }}</span>
                                </a>
                            @else
                                <span class="text-slate-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="p-3.5 align-top min-w-[130px]">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusColors[$assignment->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $assignment->status_label }}
                            </span>
                            <div class="mt-1.5 flex items-center gap-1.5">
                                <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-[10px] font-mono text-slate-500">{{ $pct }}%</span>
                            </div>
                        </td>
                        <td class="p-3.5 align-top text-textSecondary whitespace-nowrap text-[11px]">
                            {{ $assignment->updated_at->format('d M H:i') }}
                            <div class="text-[10px] text-slate-400">{{ $assignment->updated_at->diffForHumans() }}</div>
                        </td>
                        <td class="p-3.5 align-top text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.tasks.show', $assignment->id) }}"
                                   class="w-7 h-7 inline-flex items-center justify-center rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:bg-blue-50 hover:text-primary"
                                   title="View Task Details">
                                    <ion-icon name="eye-outline" class="text-sm pointer-events-none"></ion-icon>
                                </a>
                                @if($assignment->technician)
                                    <a href="{{ route('admin.tracking', ['technician_id' => $assignment->technician->id]) }}"
                                       class="w-7 h-7 inline-flex items-center justify-center rounded-lg bg-slate-50 border border-slate-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700"
                                       title="Track Technician Live">
                                        <ion-icon name="locate-outline" class="text-sm pointer-events-none"></ion-icon>
                                    </a>
                                @endif
                                @if($assignment->status !== 'completed')
                                    <button type="button"
                                        onclick="openReassignModal(this)"
                                        data-assign-url="{{ route('admin.tasks.assign', $assignment->id) }}"
                                        data-task-code="{{ $asgCode }}"
                                        data-task-title="{{ $assignment->title }}"
                                        data-current-tech="{{ $assignment->technician_id ?? '' }}"
                                        class="w-7 h-7 inline-flex items-center justify-center rounded-lg bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100"
                                        title="Reassign Technician">
                                        <ion-icon name="swap-horizontal-outline" class="text-sm pointer-events-none"></ion-icon>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-textSecondary">
                            <ion-icon name="alert-circle-outline" class="text-4xl mb-2 text-slate-300"></ion-icon>
                            <p class="text-xs font-semibold">No dispatched work orders match your filter.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assignments->hasPages())
        <div class="px-5 py-3.5 border-t border-fsmBorder bg-slate-50/40">
            {{ $assignments->links() }}
        </div>
        @endif
    </div>

    <!-- Right Column (1/3): Live Technician Workload & Technician Decline Audit Feed -->
    <div class="space-y-6">
        <!-- Live Technician Workload & Presence -->
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-textPrimary">Live Technician Workload</h3>
                    <p class="text-[11px] text-textSecondary mt-0.5">GPS presence &amp; active field capacity.</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ $technicians->where('effectively_online', true)->count() }} Online
                </span>
            </div>

            <div class="space-y-3">
                @forelse($technicians as $tech)
                    @php
                        $maxLoad = 5;
                        $loadPercentage = min(($tech->tasks_count / $maxLoad) * 100, 100);
                        $barColor = $tech->tasks_count >= 4 ? 'bg-red-500' : ($tech->tasks_count >= 2 ? 'bg-amber-500' : 'bg-primary');
                    @endphp
                    <div class="border border-slate-100 rounded-xl p-3.5 bg-slate-50/60 hover:border-blue-200 transition-all">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2.5">
                                <div class="relative">
                                    <div class="h-8 w-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs">
                                        {{ strtoupper(substr($tech->name, 0, 1)) }}
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white {{ $tech->effectively_online ? 'bg-emerald-500' : 'bg-slate-300' }}"
                                          title="{{ $tech->effectively_online ? 'Online' : 'Offline' }}"></span>
                                </div>
                                <div>
                                    <a href="{{ route('admin.assignments', ['technician_id' => $tech->id]) }}" class="text-xs font-bold text-textPrimary hover:text-primary block">
                                        {{ $tech->name }}
                                    </a>
                                    <span class="text-[10px] text-textSecondary block">
                                        {{ $tech->effectively_online ? 'Online' : 'Offline' }}
                                        &bull; {{ $tech->completed_tasks_count ?? 0 }} done
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold {{ $tech->tasks_count >= 4 ? 'text-red-600' : 'text-textPrimary' }}">
                                    {{ $tech->tasks_count }} / {{ $maxLoad }}
                                </span>
                                <a href="{{ route('admin.tracking', ['technician_id' => $tech->id]) }}" class="block text-[10px] text-blue-600 hover:underline font-semibold">
                                    GPS &rarr;
                                </a>
                            </div>
                        </div>

                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $barColor }}" style="width: {{ $loadPercentage }}%"></div>
                        </div>

                        <div class="flex justify-between items-center mt-2 text-[9px] font-semibold uppercase tracking-wider text-slate-400">
                            <span>
                                @if($tech->latestLocation)
                                    GPS {{ $tech->latestLocation->created_at?->diffForHumans() }}
                                @else
                                    No GPS ping yet
                                @endif
                            </span>
                            @if($tech->tasks_count >= 4)
                                <span class="text-red-500">Overloaded</span>
                            @elseif($tech->tasks_count >= 2)
                                <span class="text-amber-500">Moderate</span>
                            @else
                                <span class="text-emerald-600">Optimal</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-textSecondary text-center py-6">No active technicians registered.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Technician Decline Audit Feed -->
        <div id="decline-audit-feed" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-bold text-textPrimary">Technician Decline Audit</h3>
                    <p class="text-[11px] text-textSecondary">Reasons submitted when technicians decline from the APK.</p>
                </div>
                <ion-icon name="shield-half-outline" class="text-red-500 text-lg"></ion-icon>
            </div>

            <div class="space-y-2.5">
                @forelse($recentDeclines as $decline)
                    <div class="p-3 rounded-xl bg-red-50/50 border border-red-100 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            @if($decline->task)
                                <a href="{{ route('admin.tasks.show', $decline->task->id) }}" class="font-bold text-red-800 hover:underline truncate">
                                    #TSK-{{ str_pad($decline->task->id, 4, '0', STR_PAD_LEFT) }} {{ $decline->task->title }}
                                </a>
                            @else
                                <span class="font-bold text-slate-500">Deleted Task #{{ $decline->task_id }}</span>
                            @endif
                            <span class="text-[10px] text-slate-400 whitespace-nowrap">{{ $decline->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="text-[11px] text-slate-700 mt-1 italic">
                            &ldquo;{{ $decline->reason ?: 'No reason provided' }}&rdquo;
                        </p>
                        <div class="text-[10px] text-slate-500 mt-1">
                            By <span class="font-semibold">{{ $decline->actor?->name ?? 'Technician' }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-center text-[11px] text-slate-500">
                        No technician declines recorded. Excellent dispatch acceptance!
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Inline Reassign Modal -->
<div id="modal-reassign" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Reassign Field Technician</h3>
                <p id="reassign-task-label" class="text-[11px] text-textSecondary truncate max-w-[300px] mt-0.5">#TSK</p>
            </div>
            <button type="button" onclick="closeReassignModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>

        <form id="form-reassign" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select New Field Technician *</label>
                <select id="reassign-tech-select" name="technician_id" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
                    <option value="">-- Choose Technician --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">
                            {{ $tech->name }} ({{ $tech->tasks_count }} active){{ $tech->effectively_online ? ' • Online' : ' • Offline' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-slate-400 mt-1.5">Reassigning resets the ticket to <strong>Assigned</strong> and syncs immediately to the technician's APK.</p>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeReassignModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white text-xs font-bold shadow-sm inline-flex items-center gap-1.5">
                    <ion-icon name="swap-horizontal-outline" class="pointer-events-none"></ion-icon> Confirm Reassign
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openReassignModal(btn) {
        const modal = document.getElementById('modal-reassign');
        document.getElementById('form-reassign').action = btn.getAttribute('data-assign-url');
        document.getElementById('reassign-task-label').textContent = `${btn.getAttribute('data-task-code')} — ${btn.getAttribute('data-task-title')}`;
        document.getElementById('reassign-tech-select').value = btn.getAttribute('data-current-tech') || '';
        modal.classList.remove('hidden');
    }

    function closeReassignModal() {
        document.getElementById('modal-reassign')?.classList.add('hidden');
    }

    document.getElementById('modal-reassign')?.addEventListener('click', function(e) {
        if (e.target === this) closeReassignModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeReassignModal();
    });
</script>
@endpush
@endsection
