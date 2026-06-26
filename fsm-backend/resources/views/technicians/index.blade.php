@extends('layouts.admin')

@section('title', 'Technician Management')
@section('header', 'Technicians')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <h2 class="text-base font-bold text-textPrimary">Technician Directory</h2>
        <p class="text-xs text-textSecondary">Manage active field agents, toggle account status, and view metrics.</p>
    </div>
    <div>
        <a href="{{ route('admin.technicians.create') }}" class="bg-primary hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-xs shadow-md shadow-blue-500/10 transition-all flex items-center gap-1.5">
            <ion-icon name="add-outline" class="text-base"></ion-icon>
            Add Technician
        </a>
    </div>
</div>

<!-- Search Bar -->
<div class="bg-white p-4 rounded-xl border border-fsmBorder shadow-sm mb-6 flex justify-between items-center">
    <form action="{{ route('admin.technicians.index') }}" method="GET" class="flex w-full max-w-md relative">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <ion-icon name="search-outline"></ion-icon>
        </div>
        <input type="text" name="search" value="{{ request('search') }}" 
            class="block w-full pl-9 pr-24 py-2 border border-slate-200 rounded-xl text-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all text-textPrimary" 
            placeholder="Search name, email, phone number...">
        <button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 rounded-lg text-[10px] border border-slate-200 transition-all">
            Search
        </button>
    </form>
    @if(request('search'))
        <a href="{{ route('admin.technicians.index') }}" class="text-xs text-red-500 hover:text-red-700 font-semibold transition-all">
            Clear Search
        </a>
    @endif
</div>

<!-- Directory Table -->
<div class="bg-white rounded-xl border border-fsmBorder shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-fsmBorder text-textSecondary text-[11px] font-semibold uppercase tracking-wider">
                    <th class="p-4">Technician</th>
                    <th class="p-4">Contact Info</th>
                    <th class="p-4">Account Status</th>
                    <th class="p-4">Active Workload</th>
                    <th class="p-4">Registered Date</th>
                    <th class="p-4 text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-xs divide-y divide-fsmBorder">
                @forelse($technicians as $tech)
                @php
                    // Compute workload (active tasks count)
                    $workloadCount = $tech->tasks()->whereIn('status', ['assigned', 'accepted', 'on-going'])->count();
                @endphp
                <tr class="hover:bg-slate-50/30 transition-colors">
                    <td class="p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($tech->name, 0, 1)) }}
                            </div>
                            <div>
                                <span class="font-bold text-textPrimary block text-sm">{{ $tech->name }}</span>
                                <span class="text-[10px] text-textSecondary font-medium">Field Technician</span>
                            </div>
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="space-y-1">
                            <div class="text-textPrimary flex items-center gap-1.5">
                                <ion-icon name="mail-outline" class="text-slate-400"></ion-icon>
                                <span>{{ $tech->email }}</span>
                            </div>
                            @if($tech->phone)
                            <div class="text-textSecondary flex items-center gap-1.5 text-[11px]">
                                <ion-icon name="call-outline" class="text-slate-400"></ion-icon>
                                <span>{{ $tech->phone }}</span>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td class="p-4 text-[10px]">
                        <div class="flex flex-col gap-1.5">
                            @if($tech->is_active)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold bg-green-50 text-green-700 border border-green-200 w-fit">
                                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold bg-red-50 text-red-700 border border-red-200 w-fit">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Disabled
                                </span>
                            @endif

                            @if($tech->is_active)
                                @if($tech->is_online)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold bg-blue-50 text-blue-700 border border-blue-200 w-fit">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500 animate-pulse"></span> Online
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold bg-slate-50 text-slate-500 border border-slate-200 w-fit">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Offline
                                    </span>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                <div class="bg-primary h-1.5 rounded-full" style="width: {{ min(($workloadCount/4)*100, 100) }}%"></div>
                            </div>
                            <span class="font-semibold text-textPrimary">{{ $workloadCount }} tasks</span>
                        </div>
                    </td>
                    <td class="p-4 text-textSecondary">
                        {{ $tech->created_at->format('d M Y') }}
                    </td>
                    <td class="p-4">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.technicians.edit', $tech->id) }}" class="p-1.5 text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 hover:text-blue-600 transition-all" title="Edit Profile">
                                <ion-icon name="create-outline" class="text-sm"></ion-icon>
                            </a>
                            
                            @if($tech->is_active)
                                <form action="{{ route('admin.technicians.destroy', $tech->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menonaktifkan teknisi ini? Akun tidak akan dapat masuk ke aplikasi mobile.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-600 bg-slate-50 border border-slate-200 rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all" title="Deactivate Agent">
                                        <ion-icon name="ban-outline" class="text-sm"></ion-icon>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-textSecondary">
                        <div class="max-w-xs mx-auto">
                            <ion-icon name="people-outline" class="text-5xl mb-2 text-slate-300"></ion-icon>
                            <p class="text-sm font-bold text-textPrimary">No technicians found</p>
                            <p class="text-xs mt-1">Try adapting your search parameter or register a new technician account.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($technicians->hasPages())
    <div class="px-6 py-4 border-t border-fsmBorder bg-slate-50/50">
        {{ $technicians->links() }}
    </div>
    @endif
</div>
@endsection
