@extends('layouts.admin')

@section('title', 'Task Management')
@section('header', 'Task Management')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-base font-bold text-textPrimary">All Operations Tasks</h2>
        <p class="text-xs text-textSecondary">Manage, schedule, and assign tickets to wifi technicians.</p>
    </div>
    <div>
        <a href="{{ route('admin.tasks.create') }}" class="bg-primary hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
            <ion-icon name="add-outline" class="text-base"></ion-icon>
            Create New Task
        </a>
    </div>
</div>

<!-- Filters & Search Bar -->
<div class="bg-white p-4 rounded-xl border border-fsmBorder shadow-sm mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="flex overflow-x-auto pb-2 md:pb-0 w-full md:w-auto gap-1.5 hide-scrollbar">
        <a href="{{ route('admin.tasks.index') }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ !request('status') ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">All Statuses</a>
        <a href="{{ route('admin.tasks.index', ['status' => 'pending']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ request('status') == 'pending' ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">Pending</a>
        <a href="{{ route('admin.tasks.index', ['status' => 'assigned']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ request('status') == 'assigned' ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">Assigned</a>
        <a href="{{ route('admin.tasks.index', ['status' => 'accepted']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ request('status') == 'accepted' ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">Accepted</a>
        <a href="{{ route('admin.tasks.index', ['status' => 'on-going']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ request('status') == 'on-going' ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">On-Going</a>
        <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap {{ request('status') == 'completed' ? 'bg-primary text-white shadow-sm' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200/60' }}">Completed</a>
    </div>

    <form action="{{ route('admin.tasks.index') }}" method="GET" class="flex w-full md:w-80 relative">
        @if(request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <ion-icon name="search-outline"></ion-icon>
        </div>
        <input type="text" name="search" value="{{ request('search') }}" 
            class="block w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all text-textPrimary" 
            placeholder="Search task title, customer name...">
    </form>
</div>

<!-- Table View -->
<div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Task Details</th>
                    <th class="p-4">Customer & Location</th>
                    <th class="p-4">Technician</th>
                    <th class="p-4">Status & Priority</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @forelse($tasks as $task)
                <tr class="hover:bg-slate-50/30 transition-colors">
                    <td class="p-4">
                        <a href="{{ route('admin.tasks.show', $task->id) }}" class="font-bold text-textPrimary hover:text-primary transition-all block mb-1 text-sm">
                            {{ $task->title }}
                        </a>
                        <span class="text-[10px] text-textSecondary font-mono uppercase">
                            #TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }} • Created {{ $task->created_at->format('d M Y') }}
                        </span>
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col gap-1">
                            <div class="font-medium text-textPrimary flex items-center gap-1.5">
                                <ion-icon name="person-outline" class="text-slate-400"></ion-icon>
                                {{ $task->customer_name }}
                            </div>
                            <div class="text-[10px] text-textSecondary flex items-center gap-1.5 truncate max-w-xs" title="{{ $task->resolved_location }}">
                                <ion-icon name="location-outline" class="text-slate-400 flex-shrink-0"></ion-icon>
                                <span class="truncate">{{ $task->resolved_location }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        @if($task->technician)
                            <a href="{{ route('admin.tracking', ['technician_id' => $task->technician->id]) }}" class="flex items-center gap-2 hover:text-primary transition-all hover:underline">
                                <div class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center font-bold text-[10px] text-slate-700">
                                    {{ substr($task->technician->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-textPrimary hover:text-primary">{{ $task->technician->name }}</span>
                            </a>
                        @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-50 text-slate-400 border border-slate-200/60">Unassigned</span>
                        @endif
                    </td>
                    <td class="p-4">
                        <div class="flex flex-col gap-1.5 items-start">
                            @php
                                $statusColors = [
                                    'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'accepted' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'on-going' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'completed' => 'bg-green-50 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                ];
                                $color = $statusColors[$task->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $color }}">
                                {{ $task->status_label }}
                            </span>

                            @php
                                $priorityStyles = [
                                    'low' => 'text-slate-500 bg-slate-100',
                                    'medium' => 'text-amber-700 bg-amber-50',
                                    'high' => 'text-red-700 bg-red-50',
                                ];
                                $pStyle = $priorityStyles[$task->priority] ?? 'text-slate-500 bg-slate-100';
                            @endphp
                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded uppercase tracking-wider {{ $pStyle }}">
                                {{ $task->priority }} Priority
                            </span>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.tasks.show', $task->id) }}" class="p-1.5 text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 hover:text-primary transition-all" title="View details">
                                <ion-icon name="eye-outline" class="text-sm"></ion-icon>
                            </a>
                            <a href="{{ route('admin.tasks.edit', $task->id) }}" class="p-1.5 text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 hover:text-blue-600 transition-all" title="Edit">
                                <ion-icon name="create-outline" class="text-sm"></ion-icon>
                            </a>
                            <form action="{{ route('admin.tasks.destroy', $task->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all" title="Delete">
                                    <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="p-12 text-center text-textSecondary">
                        <div class="max-w-xs mx-auto">
                            <ion-icon name="document-text-outline" class="text-5xl mb-2 text-slate-300"></ion-icon>
                            <p class="text-sm font-bold text-textPrimary">No tasks found</p>
                            <p class="text-xs mt-1">Try resetting the filters or schedule a new field troubleshooting task.</p>
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

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
@endsection
