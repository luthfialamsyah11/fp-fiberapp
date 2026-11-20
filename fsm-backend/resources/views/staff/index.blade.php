@extends('layouts.admin')

@section('title', 'Portal Staff & Roles')
@section('header', 'Portal Staff & RBAC Roles')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-base font-bold text-textPrimary">Portal Staff Directory</h2>
        <p class="text-xs text-textSecondary">Manage admin users, dispatchers, warehouse controllers, and field supervisors with granular RBAC permissions.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.technicians.index') }}" class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-3.5 py-2 rounded-xl font-medium text-xs shadow-sm transition-all flex items-center gap-1.5">
            <ion-icon name="people-outline" class="text-base text-slate-500"></ion-icon>
            Technician Fleet ({{ \App\Models\User::where('role', 'technician')->count() }})
        </a>
        <a href="{{ route('admin.staff.create') }}" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-xl font-medium text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
            <ion-icon name="person-add-outline" class="text-base"></ion-icon>
            Provision Staff
        </a>
    </div>
</div>

<!-- Quick Role Filter Pills -->
<div class="flex flex-wrap items-center gap-2 mb-5">
    <a href="{{ route('admin.staff.index') }}" 
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border {{ !request('role') ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
        All Staff ({{ $roleCounts['all'] }})
    </a>
    <a href="{{ route('admin.staff.index', ['role' => 'super_admin']) }}" 
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border {{ request('role') === 'super_admin' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-purple-700 border-purple-200 hover:bg-purple-50' }}">
        Super Admin ({{ $roleCounts['super_admin'] }})
    </a>
    <a href="{{ route('admin.staff.index', ['role' => 'noc_dispatcher']) }}" 
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border {{ request('role') === 'noc_dispatcher' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-blue-700 border-blue-200 hover:bg-blue-50' }}">
        NOC Dispatcher ({{ $roleCounts['noc_dispatcher'] }})
    </a>
    <a href="{{ route('admin.staff.index', ['role' => 'warehouse_admin']) }}" 
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border {{ request('role') === 'warehouse_admin' ? 'bg-amber-600 text-white border-amber-600' : 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50' }}">
        Warehouse Admin ({{ $roleCounts['warehouse_admin'] }})
    </a>
    <a href="{{ route('admin.staff.index', ['role' => 'field_supervisor']) }}" 
       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border {{ request('role') === 'field_supervisor' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50' }}">
        Field Supervisor ({{ $roleCounts['field_supervisor'] }})
    </a>
</div>

<!-- Search Bar -->
<div class="bg-white p-4 rounded-xl border border-fsmBorder shadow-sm mb-6 flex justify-between items-center">
    <form action="{{ route('admin.staff.index') }}" method="GET" class="flex w-full max-w-md relative">
        @if(request('role'))
            <input type="hidden" name="role" value="{{ request('role') }}">
        @endif
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <ion-icon name="search-outline"></ion-icon>
        </div>
        <input type="text" name="search" value="{{ request('search') }}" 
            class="block w-full pl-9 pr-24 py-2 border border-slate-200 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all text-textPrimary" 
            placeholder="Search staff name, email, phone...">
        <button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 rounded-lg text-[10px] border border-slate-200 transition-all">
            Search
        </button>
    </form>
    @if(request('search') || request('role'))
        <a href="{{ route('admin.staff.index') }}" class="text-xs text-red-500 hover:text-red-700 font-semibold transition-all">
            Clear Filter
        </a>
    @endif
</div>

<!-- Staff Directory Table -->
<div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Staff Member</th>
                    <th class="p-4">Assigned Role</th>
                    <th class="p-4">Contact</th>
                    <th class="p-4">Account Status</th>
                    <th class="p-4">Created Date</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-fsmBorder text-xs text-textPrimary">
                @forelse($staffMembers as $member)
                    <tr class="hover:bg-slate-50/80 transition-colors {{ !$member->is_active ? 'opacity-60 bg-slate-50/40' : '' }}">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                    {{ substr($member->name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block text-xs flex items-center gap-1.5">
                                        {{ $member->name }}
                                        @if(auth()->id() === $member->id)
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-700">You</span>
                                        @endif
                                    </span>
                                    <span class="text-slate-400 text-[11px]">{{ $member->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            @php
                                $roleBadge = match($member->role) {
                                    'admin', 'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'noc_dispatcher'       => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'warehouse_admin'      => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'field_supervisor'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    default                => 'bg-slate-50 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $roleBadge }}">
                                {{ $member->roleLabel() }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-slate-600 block">{{ $member->phone ?: 'No phone' }}</span>
                        </td>
                        <td class="p-4">
                            @if($member->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-500 text-[11px]">
                            {{ $member->created_at ? $member->created_at->format('M d, Y') : '-' }}
                        </td>
                        <td class="p-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('admin.staff.edit', $member) }}" 
                                   class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit Staff Member">
                                    <ion-icon name="create-outline" class="text-base"></ion-icon>
                                </a>

                                @if(auth()->id() !== $member->id)
                                    <form action="{{ route('admin.staff.toggle_status', $member) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                            class="p-1.5 {{ $member->is_active ? 'text-amber-500 hover:text-amber-700 hover:bg-amber-50' : 'text-emerald-500 hover:text-emerald-700 hover:bg-emerald-50' }} rounded-lg transition-all" 
                                            title="{{ $member->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                            <ion-icon name="{{ $member->is_active ? 'pause-circle-outline' : 'play-circle-outline' }}" class="text-base"></ion-icon>
                                        </button>
                                    </form>

                                    <form action="{{ route('admin.staff.destroy', $member) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete staff member {{ $member->name }}? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete Staff Account">
                                            <ion-icon name="trash-outline" class="text-base"></ion-icon>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400">
                            <ion-icon name="shield-outline" class="text-4xl text-slate-300 mb-2"></ion-icon>
                            <p class="font-medium text-xs">No portal staff members found matching criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($staffMembers->hasPages())
        <div class="p-4 border-t border-fsmBorder bg-slate-50/50">
            {{ $staffMembers->links() }}
        </div>
    @endif
</div>
@endsection
