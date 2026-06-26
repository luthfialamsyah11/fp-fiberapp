@extends('layouts.admin')

@section('title', 'Assignment Monitoring')
@section('header', 'Assignment Monitoring')

@section('content')
<!-- Stats overview row -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Total Dispatched</span>
        <h3 class="text-2xl font-bold text-textPrimary leading-none mt-2">{{ $totalAssigned }} Tasks</h3>
        <span class="text-[10px] text-textSecondary mt-1.5 block">Total tickets assigned to technicians</span>
    </a>
    
    <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Acceptance Rate</span>
        <h3 class="text-2xl font-bold text-green-600 leading-none mt-2">{{ $acceptanceRate }}%</h3>
        <span class="text-[10px] text-textSecondary mt-1.5 block">Percentage of accepted and completed tickets</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'assigned']) }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Awaiting Acceptance</span>
        <h3 class="text-2xl font-bold text-blue-600 leading-none mt-2">{{ $pendingAcceptance }} Tasks</h3>
        <span class="text-[10px] text-textSecondary mt-1.5 block">Technicians have not accepted yet</span>
    </a>

    <a href="{{ route('admin.tasks.index', ['status' => 'rejected']) }}" class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all block">
        <span class="text-xs font-semibold text-textSecondary uppercase tracking-wider block">Rejected Tickets</span>
        <h3 class="text-2xl font-bold text-red-600 leading-none mt-2">{{ $rejectedCount }} Tasks</h3>
        <span class="text-[10px] text-textSecondary mt-1.5 block">Declined by dispatch agents</span>
    </a>
</div>

<!-- Main Assignment Layout Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <!-- Left Column: Assignment Timeline & History -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-fsmBorder shadow-sm flex flex-col">
        <div class="px-6 py-4 border-b border-fsmBorder">
            <h3 class="text-sm font-bold text-textPrimary">Recent Dispatches & Timelines</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                        <th class="p-4">Task Details</th>
                        <th class="p-4">Assigned Agent</th>
                        <th class="p-4">Acceptance Status</th>
                        <th class="p-4">Assigned Date</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-fsmBorder">
                    @forelse($assignments as $assignment)
                    <tr class="hover:bg-slate-50/30 transition-colors">
                        <td class="p-4">
                            <a href="{{ route('admin.tasks.show', $assignment->id) }}" class="font-bold text-textPrimary hover:text-primary transition-all block mb-1">
                                {{ $assignment->title }}
                            </a>
                            <span class="text-[10px] text-textSecondary font-mono uppercase">#TSK-{{ str_pad($assignment->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] text-slate-700">
                                    {{ substr($assignment->technician->name, 0, 1) }}
                                </div>
                                <span class="font-semibold text-textPrimary">{{ $assignment->technician->name }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'on-going' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'completed' => 'bg-green-50 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                            @endphp
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusColors[$assignment->status] ?? 'bg-slate-100' }}">
                                {{ $assignment->status_label }}
                            </span>
                        </td>
                        <td class="p-4 text-textSecondary">
                            {{ $assignment->updated_at->format('d M Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-textSecondary">
                            <ion-icon name="alert-circle-outline" class="text-4xl mb-2 text-slate-300"></ion-icon>
                            <p class="text-xs">No active assignments dispatched yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($assignments->hasPages())
        <div class="px-6 py-4 border-t border-fsmBorder bg-slate-50/30">
            {{ $assignments->links() }}
        </div>
        @endif
    </div>

    <!-- Right Column: Technician Workloads -->
    <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm flex flex-col">
        <div class="mb-5">
            <h3 class="text-sm font-bold text-textPrimary">Technician Workload</h3>
            <p class="text-[11px] text-textSecondary mt-0.5">Active tickets currently dispatched per technician.</p>
        </div>

        <div class="space-y-4">
            @forelse($technicians as $tech)
                @php
                    // Calculate percentage representation
                    $maxLoad = 5; // e.g. 5 tasks is considered 100% capacity
                    $loadPercentage = min(($tech->tasks_count / $maxLoad) * 100, 100);
                    
                    // Choose bar color
                    $barColor = 'bg-primary';
                    if ($tech->tasks_count >= 4) {
                        $barColor = 'bg-red-500';
                    } elseif ($tech->tasks_count >= 2) {
                        $barColor = 'bg-amber-500';
                    }
                @endphp
                <a href="{{ route('admin.history', ['technician_id' => $tech->id]) }}" class="border border-slate-100 rounded-xl p-3.5 bg-slate-50/50 block hover:border-blue-300 hover:shadow-sm transition-all">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-[11px]">
                                {{ substr($tech->name, 0, 1) }}
                            </div>
                            <div>
                                <span class="text-xs font-bold text-textPrimary block">{{ $tech->name }}</span>
                                <span class="text-[9px] text-textSecondary block">Agent Load: {{ $tech->tasks_count }} Tasks</span>
                            </div>
                        </div>
                        <span class="text-xs font-bold @if($tech->tasks_count >= 4) text-red-600 @else text-textPrimary @endif">
                            {{ $tech->tasks_count }} / {{ $maxLoad }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-slate-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $barColor }}" style="width: {{ $loadPercentage }}%"></div>
                    </div>

                    <!-- Status Indicator -->
                    <div class="flex justify-between items-center mt-2 text-[9px] font-semibold uppercase tracking-wider text-slate-400">
                        <span>Load Index</span>
                        @if($tech->tasks_count >= 4)
                            <span class="text-red-500">Overloaded</span>
                        @elseif($tech->tasks_count >= 2)
                            <span class="text-amber-500">Moderate</span>
                        @else
                            <span class="text-green-500">Optimal</span>
                        @endif
                    </div>
                </a>
            @empty
                <p class="text-xs text-textSecondary text-center py-6">No technicians registered.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
