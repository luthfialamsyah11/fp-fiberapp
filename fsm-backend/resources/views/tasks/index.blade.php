@extends('layouts.admin')

@section('title', 'Task Management')
@section('header', 'Task Management & Dispatch Hub')

@section('content')
<!-- Top Header Row -->
<div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-base sm:text-lg font-bold text-textPrimary tracking-tight">Operations Work Orders</h2>
        <p class="text-xs text-textSecondary">Create, filter, quick-dispatch, and monitor SLA compliance across all field tickets.</p>
    </div>
    @php
        $canManageDispatch = auth()->user()?->hasAnyRole(['admin', 'super_admin', 'noc_dispatcher']);
    @endphp
    <div class="flex items-center gap-2">
        @if($canManageDispatch)
            <a href="{{ route('admin.assignments') }}" class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all inline-flex items-center gap-1.5 shadow-sm">
                <ion-icon name="git-network-outline" class="text-sm text-blue-600"></ion-icon>
                Assignment Board
            </a>
            <a href="{{ route('admin.tasks.create') }}" class="bg-primary hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-blue-500/15 transition-all inline-flex items-center gap-1.5">
                <ion-icon name="add-circle-outline" class="text-base pointer-events-none"></ion-icon>
                Create New Task
            </a>
        @else
            <a href="{{ route('admin.tracking') }}" class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 px-3.5 py-2.5 rounded-xl font-semibold text-xs transition-all inline-flex items-center gap-1.5 shadow-sm">
                <ion-icon name="locate-outline" class="text-sm text-blue-600"></ion-icon>
                Live Tracking
            </a>
            <a href="{{ route('admin.proofs') }}" class="bg-primary hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-blue-500/15 transition-all inline-flex items-center gap-1.5">
                <ion-icon name="images-outline" class="text-base pointer-events-none"></ion-icon>
                Proof of Work
            </a>
        @endif
    </div>
</div>

<!-- Executive KPI Strip (5 Clickable Cards) -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="bg-white rounded-xl border {{ !request('status') && !request('priority') ? 'border-blue-400 ring-1 ring-blue-200' : 'border-fsmBorder' }} p-4 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tasks</span>
            <ion-icon name="layers-outline" class="text-blue-600 text-base"></ion-icon>
        </div>
        <div class="text-2xl font-bold text-textPrimary mt-1.5">{{ $summary['total'] ?? 0 }}</div>
        <span class="text-[10px] text-textSecondary">{{ $summary['high_active'] ?? 0 }} urgent active</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'pending']) }}" class="bg-white rounded-xl border {{ request('status') === 'pending' ? 'border-amber-400 ring-1 ring-amber-200' : 'border-fsmBorder' }} p-4 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Pending Dispatch</span>
            <ion-icon name="time-outline" class="text-amber-500 text-base"></ion-icon>
        </div>
        <div class="text-2xl font-bold text-textPrimary mt-1.5">{{ $summary['pending'] ?? 0 }}</div>
        <span class="text-[10px] text-amber-700">Needs technician</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'on-going']) }}" class="bg-white rounded-xl border {{ request('status') === 'on-going' ? 'border-indigo-400 ring-1 ring-indigo-200' : 'border-fsmBorder' }} p-4 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">In Progress</span>
            <ion-icon name="construct-outline" class="text-indigo-500 text-base"></ion-icon>
        </div>
        <div class="text-2xl font-bold text-textPrimary mt-1.5">{{ ($summary['on_going'] ?? 0) + ($summary['accepted'] ?? 0) }}</div>
        <span class="text-[10px] text-indigo-700">{{ $summary['on_going'] ?? 0 }} on-site &bull; {{ $summary['accepted'] ?? 0 }} en route</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'sla_breach']) }}" class="bg-white rounded-xl border {{ ($summary['sla_breach'] ?? 0) > 0 ? 'border-rose-300 bg-rose-50/30' : 'border-fsmBorder' }} {{ request('status') === 'sla_breach' ? 'ring-1 ring-rose-400' : '' }} p-4 shadow-sm hover:shadow-md transition-all block">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider">SLA Breached</span>
            <ion-icon name="alert-circle-outline" class="text-rose-600 text-base"></ion-icon>
        </div>
        <div class="text-2xl font-bold {{ ($summary['sla_breach'] ?? 0) > 0 ? 'text-rose-600' : 'text-textPrimary' }} mt-1.5">{{ $summary['sla_breach'] ?? 0 }}</div>
        <span class="text-[10px] text-rose-700">&gt; {{ $slaHours ?? 2 }}h SLA threshold</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="bg-white rounded-xl border {{ request('status') === 'completed' ? 'border-emerald-400 ring-1 ring-emerald-200' : 'border-fsmBorder' }} p-4 shadow-sm hover:shadow-md transition-all block col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Completed</span>
            <ion-icon name="shield-checkmark-outline" class="text-emerald-600 text-base"></ion-icon>
        </div>
        <div class="text-2xl font-bold text-emerald-700 mt-1.5">{{ $summary['completed'] ?? 0 }}</div>
        <span class="text-[10px] text-emerald-700">BAST sealed tickets</span>
    </a>
</div>

<!-- Multi-Facet Filters & Search Bar -->
<div class="bg-white p-4 rounded-xl border border-fsmBorder shadow-sm mb-6 space-y-3.5">
    <!-- Status Tabs with Live Counters -->
    <div class="flex overflow-x-auto pb-1 gap-1.5 hide-scrollbar">
        @php
            $baseQuery = request()->except(['status', 'page']);
            $statusTabs = [
                ''           => ['label' => 'All', 'count' => $summary['total'] ?? 0, 'active' => 'bg-primary text-white shadow-sm'],
                'pending'    => ['label' => 'Pending', 'count' => $summary['pending'] ?? 0, 'active' => 'bg-amber-600 text-white shadow-sm'],
                'assigned'   => ['label' => 'Assigned', 'count' => $summary['assigned'] ?? 0, 'active' => 'bg-blue-600 text-white shadow-sm'],
                'accepted'   => ['label' => 'Accepted', 'count' => $summary['accepted'] ?? 0, 'active' => 'bg-indigo-600 text-white shadow-sm'],
                'on-going'   => ['label' => 'On-Going', 'count' => $summary['on_going'] ?? 0, 'active' => 'bg-amber-600 text-white shadow-sm'],
                'completed'  => ['label' => 'Completed', 'count' => $summary['completed'] ?? 0, 'active' => 'bg-emerald-600 text-white shadow-sm'],
                'rejected'   => ['label' => 'Rejected', 'count' => $summary['rejected'] ?? 0, 'active' => 'bg-red-600 text-white shadow-sm'],
                'sla_breach' => ['label' => 'SLA Breach', 'count' => $summary['sla_breach'] ?? 0, 'active' => 'bg-rose-600 text-white shadow-sm'],
            ];
        @endphp
        @foreach($statusTabs as $key => $tab)
            @php
                $isActiveTab = (string) request('status', '') === $key;
                $tabUrl = $key === ''
                    ? route('admin.tasks.index', $baseQuery)
                    : route('admin.tasks.index', array_merge($baseQuery, ['status' => $key]));
            @endphp
            <a href="{{ $tabUrl }}"
               class="px-3 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap inline-flex items-center gap-1.5 transition-all {{ $isActiveTab ? $tab['active'] : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/70' }}">
                <span>{{ $tab['label'] }}</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $isActiveTab ? 'bg-white/20 text-white' : 'bg-slate-200/70 text-slate-600' }}">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </div>

    <!-- Search + Priority + Technician Filter Form -->
    <form action="{{ route('admin.tasks.index') }}" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center gap-2.5">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif

        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <ion-icon name="search-outline"></ion-icon>
            </div>
            <input type="text" name="search" value="{{ request('search') }}"
                class="block w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all text-textPrimary bg-slate-50 focus:bg-white"
                placeholder="Search #TSK-ID, title, customer, phone, or address...">
        </div>

        <select name="priority" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-xl text-xs text-textPrimary bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
            <option value="">All Priorities</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High / Urgent</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
        </select>

        <select name="technician_id" onchange="this.form.submit()" class="px-3 py-2 border border-slate-200 rounded-xl text-xs text-textPrimary bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
            <option value="">All Technicians</option>
            <option value="unassigned" {{ request('technician_id') === 'unassigned' ? 'selected' : '' }}>Unassigned Only</option>
            @foreach($technicians as $tech)
                <option value="{{ $tech->id }}" {{ (string) request('technician_id') === (string) $tech->id ? 'selected' : '' }}>
                    {{ $tech->name }} ({{ $tech->active_tasks_count }} active)
                </option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold transition-all inline-flex items-center justify-center gap-1.5">
            <ion-icon name="funnel-outline"></ion-icon> Filter
        </button>

        @if(request()->hasAny(['status', 'priority', 'technician_id', 'search']))
            <a href="{{ route('admin.tasks.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition-all inline-flex items-center justify-center gap-1">
                <ion-icon name="close-circle-outline"></ion-icon> Reset
            </a>
        @endif
    </form>
</div>

<!-- Enhanced Operations Table -->
<div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Work Order &amp; Schedule</th>
                    <th class="p-4">Customer &amp; Premise</th>
                    <th class="p-4">Technician &amp; Dispatch</th>
                    <th class="p-4">Status, Progress &amp; Evidence</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @forelse($tasks as $task)
                @php
                    $slaBreached = $task->isSlaBreached($slaHours ?? 2);
                    $statusColors = [
                        'pending'   => 'bg-slate-100 text-slate-700 border-slate-200',
                        'assigned'  => 'bg-blue-50 text-blue-700 border-blue-200',
                        'accepted'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                        'on-going'  => 'bg-amber-50 text-amber-700 border-amber-200',
                        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'rejected'  => 'bg-red-50 text-red-700 border-red-200',
                    ];
                    $priorityStyles = [
                        'low'    => 'text-slate-600 bg-slate-100',
                        'medium' => 'text-amber-700 bg-amber-50',
                        'high'   => 'text-red-700 bg-red-50',
                    ];
                    $pct = (int) $task->progress_percent;
                @endphp
                <tr class="hover:bg-blue-50/20 transition-colors {{ $slaBreached ? 'bg-rose-50/25' : '' }}">
                    <td class="p-4">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="font-bold text-textPrimary hover:text-primary transition-all block mb-1 text-sm">
                            {{ $task->title }}
                        </a>
                        <div class="flex flex-wrap items-center gap-1.5 text-[10px] text-textSecondary">
                            <span class="font-mono font-bold text-slate-600">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                            @if(($task->source ?? 'admin') === 'client_portal')
                                <span class="px-1.5 py-0.2 rounded bg-sky-50 text-sky-700 border border-sky-200 font-bold inline-flex items-center gap-0.5" title="Submitted via Public Client Portal">
                                    <ion-icon name="globe-outline"></ion-icon> Client Portal
                                </span>
                            @endif
                            <span>&bull;</span>
                            <span>{{ $task->scheduled_at ? 'Due ' . $task->scheduled_at->format('d M H:i') : 'Created ' . $task->created_at->format('d M Y') }}</span>
                            @if($task->latitude && $task->longitude)
                                <span class="px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold inline-flex items-center gap-0.5" title="GPS Pinned: {{ $task->latitude }}, {{ $task->longitude }}">
                                    <ion-icon name="pin-outline"></ion-icon> GPS
                                </span>
                            @else
                                <span class="px-1.5 py-0.2 rounded bg-slate-100 text-slate-400 border border-slate-200 font-semibold">No GPS</span>
                            @endif
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col gap-1">
                            <div class="font-semibold text-textPrimary flex items-center gap-1.5">
                                <ion-icon name="person-outline" class="text-slate-400"></ion-icon>
                                <span>{{ $task->customer_name }}</span>
                                @if($task->customer_phone)
                                    <a href="tel:{{ $task->customer_phone }}" class="text-[10px] text-blue-600 hover:underline font-normal ml-1">{{ $task->customer_phone }}</a>
                                @endif
                            </div>
                            <div class="text-[10px] text-textSecondary flex items-center gap-1.5 truncate max-w-xs" title="{{ $task->resolved_location }}">
                                <ion-icon name="location-outline" class="text-slate-400 flex-shrink-0"></ion-icon>
                                <span class="truncate">{{ $task->resolved_location }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        @if($task->technician)
                            <div class="flex flex-col gap-1">
                                <a href="{{ route('admin.tracking', ['technician_id' => $task->technician->id]) }}" class="flex items-center gap-2 hover:text-primary transition-all hover:underline">
                                    <div class="h-6 w-6 rounded-full bg-blue-100 flex items-center justify-center font-bold text-[10px] text-blue-700 flex-shrink-0">
                                        {{ strtoupper(substr($task->technician->name, 0, 1)) }}
                                    </div>
                                    <span class="font-semibold text-textPrimary hover:text-primary">{{ $task->technician->name }}</span>
                                </a>
                                @if($task->status !== 'completed')
                                    <button type="button" onclick="openQuickAssignModal({{ $task->id }}, {{ json_encode($task->title) }}, '{{ $task->technician_id }}')" class="text-[10px] text-slate-400 hover:text-blue-600 font-semibold text-left w-fit">
                                        Reassign &rarr;
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Unassigned</span>
                                <button type="button" onclick="openQuickAssignModal({{ $task->id }}, {{ json_encode($task->title) }}, '')"
                                    class="px-2.5 py-1 rounded-lg bg-primary hover:bg-blue-700 text-white text-[10px] font-bold transition-all shadow-sm inline-flex items-center gap-1">
                                    <ion-icon name="person-add-outline"></ion-icon> Assign
                                </button>
                            </div>
                        @endif
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col gap-1.5 items-start min-w-[165px]">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $task->status_label }}
                                </span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider {{ $priorityStyles[$task->priority] ?? 'text-slate-500 bg-slate-100' }}">
                                    {{ $task->priority }}
                                </span>
                                @if($slaBreached)
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider bg-rose-100 text-rose-700 border border-rose-200 inline-flex items-center gap-0.5">
                                        <ion-icon name="alert-circle-outline"></ion-icon> SLA Overdue
                                    </span>
                                @endif
                            </div>

                            <!-- Mini Progress Bar -->
                            <div class="w-full flex items-center gap-2">
                                <div class="flex-1 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-[10px] font-mono font-bold text-slate-500">{{ $pct }}%</span>
                            </div>

                            <!-- Evidence & BOM Chips -->
                            <div class="flex items-center gap-2 text-[10px] text-slate-500">
                                <span class="inline-flex items-center gap-0.5 {{ ($task->proofs_count ?? 0) > 0 ? 'text-emerald-700 font-semibold' : 'text-slate-400' }}">
                                    <ion-icon name="images-outline"></ion-icon> {{ $task->proofs_count ?? 0 }} proofs
                                </span>
                                <span>&bull;</span>
                                <span class="inline-flex items-center gap-0.5 {{ ($task->materials_count ?? 0) > 0 ? 'text-blue-700 font-semibold' : 'text-slate-400' }}">
                                    <ion-icon name="cube-outline"></ion-icon> {{ $task->materials_count ?? 0 }} BOM
                                </span>
                                @if($task->customer_signature)
                                    <span>&bull;</span>
                                    <span class="text-emerald-600 font-bold" title="Customer E-Signed">Signed</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        @php
                            $taskCode = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
                            $trackingUrl = $task->tracking_token ? route('customer.tracking', $task->tracking_token) : '';
                        @endphp
                        <div class="flex justify-center items-center gap-1.5">
                            <a href="{{ route('admin.tasks.show', $task->id) }}"
                               class="w-8 h-8 inline-flex items-center justify-center text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-blue-50 hover:text-primary hover:border-blue-200 transition-all"
                               title="View Task Command Details" aria-label="View {{ $taskCode }}">
                                <ion-icon name="eye-outline" class="text-sm pointer-events-none"></ion-icon>
                            </a>
                            @if($trackingUrl)
                                <button type="button"
                                    onclick="copyTaskTrackingLink(this)"
                                    data-url="{{ $trackingUrl }}"
                                    data-code="{{ $taskCode }}"
                                    class="w-8 h-8 inline-flex items-center justify-center text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all"
                                    title="Copy Customer Live Tracking Link" aria-label="Copy Tracking Link {{ $taskCode }}">
                                    <ion-icon name="link-outline" class="text-sm pointer-events-none"></ion-icon>
                                </button>
                            @endif
                            @if($task->status === 'completed' || $task->customer_signature)
                                <a href="{{ route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->tracking_token]) }}"
                                   target="_blank" rel="noopener"
                                   class="w-8 h-8 inline-flex items-center justify-center text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 hover:border-emerald-300 transition-all"
                                   title="Open Official BAST PDF Certificate" aria-label="Print BAST {{ $taskCode }}">
                                    <ion-icon name="document-text-outline" class="text-sm pointer-events-none"></ion-icon>
                                </a>
                            @endif
                            <a href="{{ route('admin.tasks.edit', $task->id) }}"
                               class="w-8 h-8 inline-flex items-center justify-center text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 hover:text-blue-600 hover:border-blue-200 transition-all"
                               title="Edit Work Order" aria-label="Edit {{ $taskCode }}">
                                <ion-icon name="create-outline" class="text-sm pointer-events-none"></ion-icon>
                            </a>
                            <button type="button"
                                onclick="openDeleteTaskModal(this)"
                                data-delete-url="{{ route('admin.tasks.destroy', $task->id) }}"
                                data-task-code="{{ $taskCode }}"
                                data-task-title="{{ $task->title }}"
                                data-task-status="{{ $task->status }}"
                                data-bom-count="{{ (int) ($task->materials_count ?? 0) }}"
                                class="w-8 h-8 inline-flex items-center justify-center text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all"
                                title="Delete Work Order" aria-label="Delete {{ $taskCode }}">
                                <ion-icon name="trash-outline" class="text-sm pointer-events-none"></ion-icon>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-12 text-center text-textSecondary">
                        <div class="max-w-xs mx-auto">
                            <ion-icon name="document-text-outline" class="text-5xl mb-2 text-slate-300"></ion-icon>
                            <p class="text-sm font-bold text-textPrimary">No work orders found</p>
                            <p class="text-xs mt-1">Try resetting the active filters or create a new field dispatch ticket.</p>
                            @if(request()->hasAny(['status', 'priority', 'technician_id', 'search']))
                                <a href="{{ route('admin.tasks.index') }}" class="inline-flex items-center gap-1 mt-3 px-3.5 py-1.5 rounded-lg bg-primary text-white text-xs font-semibold hover:bg-blue-700">
                                    Reset Filters
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tasks->hasPages())
    <div class="px-6 py-4 border-t border-fsmBorder bg-slate-50/50">
        {{ $tasks->links() }}
    </div>
    @endif
</div>

<!-- 1-Click Quick-Assign Modal -->
<div id="quick-assign-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-bold text-textPrimary">Quick Dispatch Technician</h3>
                <p id="qa-task-label" class="text-[11px] text-textSecondary truncate max-w-[300px] mt-0.5">#TSK</p>
            </div>
            <button type="button" onclick="closeQuickAssignModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl"></ion-icon>
            </button>
        </div>

        <form id="qa-form" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Select Active Field Technician *</label>
                <select id="qa-tech-select" name="technician_id" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
                    <option value="">-- Choose Technician --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}">
                            {{ $tech->name }} ({{ $tech->active_tasks_count }} active tasks)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeQuickAssignModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary hover:bg-blue-700 text-white text-xs font-bold shadow-sm inline-flex items-center gap-1.5">
                    <ion-icon name="paper-plane-outline"></ion-icon> Confirm Dispatch
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Work Order Confirmation Modal (Reliable across all browsers & webviews) -->
<div id="delete-task-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-start gap-3 pb-3 mb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center flex-shrink-0">
                <ion-icon name="trash-outline" class="text-xl pointer-events-none"></ion-icon>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-bold text-textPrimary">Delete Work Order</h3>
                <p id="del-task-label" class="text-[11px] text-textSecondary truncate mt-0.5">#TSK</p>
            </div>
            <button type="button" onclick="closeDeleteTaskModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                <ion-icon name="close-outline" class="text-xl pointer-events-none"></ion-icon>
            </button>
        </div>

        <p class="text-xs text-slate-600 leading-relaxed mb-3">
            This will permanently remove the work order, its status audit trail, and uploaded proof references.
        </p>
        <div id="del-task-bom-note" class="hidden mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-[11px] text-amber-800 font-medium flex items-start gap-2">
            <ion-icon name="cube-outline" class="text-base text-amber-600 flex-shrink-0 mt-0.5"></ion-icon>
            <span id="del-task-bom-text">Allocated warehouse BOM materials will be automatically restored to inventory stock.</span>
        </div>

        <form id="del-task-form" method="POST" action="" class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteTaskModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">
                Cancel
            </button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm inline-flex items-center gap-1.5">
                <ion-icon name="trash-outline" class="pointer-events-none"></ion-icon> Confirm Delete
            </button>
        </form>
    </div>
</div>

<!-- Copy Toast Feedback -->
<div id="task-action-toast" class="fixed bottom-5 right-5 z-50 hidden px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold shadow-xl items-center gap-2">
    <ion-icon name="checkmark-circle" class="text-emerald-400 text-base"></ion-icon>
    <span id="task-action-toast-text">Copied!</span>
</div>

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

@push('scripts')
<script>
    function openQuickAssignModal(taskId, taskTitle, currentTechId) {
        const modal = document.getElementById('quick-assign-modal');
        const form = document.getElementById('qa-form');
        const label = document.getElementById('qa-task-label');
        const select = document.getElementById('qa-tech-select');

        form.action = `{{ url('/tasks') }}/${taskId}/assign`;
        label.textContent = `#TSK-${String(taskId).padStart(4, '0')} — ${taskTitle}`;
        select.value = currentTechId || '';
        modal.classList.remove('hidden');
    }

    function closeQuickAssignModal() {
        document.getElementById('quick-assign-modal').classList.add('hidden');
    }

    function openDeleteTaskModal(btn) {
        const modal = document.getElementById('delete-task-modal');
        const form = document.getElementById('del-task-form');
        const label = document.getElementById('del-task-label');
        const bomBox = document.getElementById('del-task-bom-note');
        const bomText = document.getElementById('del-task-bom-text');

        const code = btn.getAttribute('data-task-code') || '';
        const title = btn.getAttribute('data-task-title') || '';
        const bomCount = parseInt(btn.getAttribute('data-bom-count') || '0', 10);

        form.action = btn.getAttribute('data-delete-url');
        label.textContent = `${code} — ${title}`;

        if (bomCount > 0) {
            bomText.textContent = `${bomCount} BOM material record(s) allocated to this ticket will be automatically restored to warehouse stock.`;
            bomBox.classList.remove('hidden');
        } else {
            bomBox.classList.add('hidden');
        }

        modal.classList.remove('hidden');
    }

    function closeDeleteTaskModal() {
        document.getElementById('delete-task-modal').classList.add('hidden');
    }

    let actionToastTimer = null;
    function showActionToast(message) {
        const toast = document.getElementById('task-action-toast');
        const txt = document.getElementById('task-action-toast-text');
        if (!toast || !txt) return;
        txt.textContent = message;
        toast.classList.remove('hidden');
        toast.classList.add('flex');
        if (actionToastTimer) clearTimeout(actionToastTimer);
        actionToastTimer = setTimeout(() => {
            toast.classList.add('hidden');
            toast.classList.remove('flex');
        }, 2600);
    }

    function copyTaskTrackingLink(btn) {
        const url = btn.getAttribute('data-url');
        const code = btn.getAttribute('data-code') || '';
        if (!url) return;

        const onSuccess = () => {
            const icon = btn.querySelector('ion-icon');
            if (icon) icon.setAttribute('name', 'checkmark-outline');
            btn.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
            showActionToast(`Tracking link for ${code} copied to clipboard.`);
            setTimeout(() => {
                if (icon) icon.setAttribute('name', 'link-outline');
                btn.classList.remove('bg-emerald-50', 'text-emerald-700', 'border-emerald-300');
            }, 1800);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(onSuccess).catch(() => fallbackCopy(url, onSuccess));
        } else {
            fallbackCopy(url, onSuccess);
        }
    }

    function fallbackCopy(text, cb) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            if (cb) cb();
        } finally {
            document.body.removeChild(ta);
        }
    }

    document.getElementById('quick-assign-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeQuickAssignModal();
    });

    document.getElementById('delete-task-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteTaskModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeQuickAssignModal();
            closeDeleteTaskModal();
        }
    });
</script>
@endpush
@endsection
