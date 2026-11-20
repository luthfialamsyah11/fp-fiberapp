@extends('layouts.admin')

@section('title', 'Client Requests Inbox')
@section('header', 'Client Requests & Self-Service Intake')

@section('content')
<div id="client-requests-root" data-fleet='@json($technicianFleet)' class="space-y-6">

    <!-- Top Header Strip -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center flex-shrink-0">
                <ion-icon name="mail-unread-outline" class="text-xl"></ion-icon>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Client Service Request Submissions</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                        Live Auto-Sync
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Incoming public submissions from customers without login. Click the eye icon to inspect details &amp; photo, then assign any Online or active technician.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="copyPublicFormUrl('{{ $publicFormUrl }}', this)"
                class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold inline-flex items-center gap-1.5 transition-all cursor-pointer">
                <ion-icon name="copy-outline" class="text-sm text-blue-600"></ion-icon>
                <span>Copy Public Form URL</span>
            </button>
            <a href="{{ $publicFormUrl }}" target="_blank" rel="noopener noreferrer"
                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold inline-flex items-center gap-1.5 transition-all">
                <ion-icon name="open-outline" class="text-sm"></ion-icon>
                <span>Open Public Portal</span>
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('admin.client_requests.index') }}" class="bg-white rounded-2xl border {{ $statusFilter === '' ? 'border-blue-400 ring-1 ring-blue-100' : 'border-slate-200/80' }} p-4 shadow-sm hover:border-blue-300 transition-all block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Requests</span>
                <ion-icon name="file-tray-full-outline" class="text-blue-600 text-base"></ion-icon>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1.5 tabular-nums">{{ $summary['total'] }}</div>
            <span class="text-[11px] text-slate-500">All client submissions</span>
        </a>

        <a href="{{ route('admin.client_requests.index', ['status' => 'pending']) }}" class="bg-white rounded-2xl border {{ $summary['pending'] > 0 ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200/80' }} {{ $statusFilter === 'pending' ? 'ring-1 ring-amber-300' : '' }} p-4 shadow-sm hover:border-amber-400 transition-all block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Awaiting Dispatch</span>
                <ion-icon name="time-outline" class="text-amber-600 text-base"></ion-icon>
            </div>
            <div class="text-2xl font-extrabold {{ $summary['pending'] > 0 ? 'text-amber-700' : 'text-slate-900' }} mt-1.5 tabular-nums">{{ $summary['pending'] }}</div>
            <span class="text-[11px] text-amber-700">{{ $onlineTechCount }} technicians online now</span>
        </a>

        <a href="{{ route('admin.client_requests.index', ['status' => 'active']) }}" class="bg-white rounded-2xl border {{ $statusFilter === 'active' ? 'border-indigo-400 ring-1 ring-indigo-100' : 'border-slate-200/80' }} p-4 shadow-sm hover:border-indigo-300 transition-all block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Assigned / In Progress</span>
                <ion-icon name="construct-outline" class="text-indigo-600 text-base"></ion-icon>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-1.5 tabular-nums">{{ $summary['active'] }}</div>
            <span class="text-[11px] text-indigo-600">Synced live to APK</span>
        </a>

        <a href="{{ route('admin.client_requests.index', ['status' => 'completed']) }}" class="bg-white rounded-2xl border {{ $statusFilter === 'completed' ? 'border-emerald-400 ring-1 ring-emerald-100' : 'border-slate-200/80' }} p-4 shadow-sm hover:border-emerald-300 transition-all block">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600">Completed</span>
                <ion-icon name="shield-checkmark-outline" class="text-emerald-600 text-base"></ion-icon>
            </div>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1.5 tabular-nums">{{ $summary['completed'] }}</div>
            <span class="text-[11px] text-emerald-700">Resolved &amp; BAST sealed</span>
        </a>
    </div>

    <!-- Filter Tabs & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5 text-xs font-semibold">
            <a href="{{ route('admin.client_requests.index', array_filter(['search' => $search])) }}"
                class="px-3 py-1.5 rounded-xl transition-all tabular-nums {{ $statusFilter === '' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All ({{ $summary['total'] }})
            </a>
            <a href="{{ route('admin.client_requests.index', array_filter(['status' => 'pending', 'search' => $search])) }}"
                class="px-3 py-1.5 rounded-xl transition-all tabular-nums {{ $statusFilter === 'pending' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Pending ({{ $summary['pending'] }})
            </a>
            <a href="{{ route('admin.client_requests.index', array_filter(['status' => 'active', 'search' => $search])) }}"
                class="px-3 py-1.5 rounded-xl transition-all tabular-nums {{ $statusFilter === 'active' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Active ({{ $summary['active'] }})
            </a>
            <a href="{{ route('admin.client_requests.index', array_filter(['status' => 'completed', 'search' => $search])) }}"
                class="px-3 py-1.5 rounded-xl transition-all tabular-nums {{ $statusFilter === 'completed' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Completed ({{ $summary['completed'] }})
            </a>
            <a href="{{ route('admin.client_requests.index', array_filter(['status' => 'rejected', 'search' => $search])) }}"
                class="px-3 py-1.5 rounded-xl transition-all tabular-nums {{ $statusFilter === 'rejected' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Rejected ({{ $summary['rejected'] }})
            </a>
        </div>

        <form method="GET" action="{{ route('admin.client_requests.index') }}" class="flex items-center gap-2">
            @if($statusFilter !== '')
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="relative flex-1 md:w-64">
                <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></ion-icon>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search #TSK, name, phone, address..."
                    class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold cursor-pointer">
                Filter
            </button>
        </form>
    </div>

    <!-- Client Requests Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200/80 text-slate-500 text-[11px] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-4">Ticket &amp; Category</th>
                        <th class="py-3.5 px-4">Client Contact</th>
                        <th class="py-3.5 px-4">Location &amp; Photo</th>
                        <th class="py-3.5 px-4">Priority</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Assigned Technician</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    @forelse($requests as $req)
                        @php
                            $modalPayload = $requestModalData[$req->id] ?? [];
                            $badgeMap = [
                                'pending'   => 'bg-amber-50 text-amber-700 border-amber-200',
                                'assigned'  => 'bg-sky-50 text-sky-700 border-sky-200',
                                'accepted'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'on-going'  => 'bg-blue-50 text-blue-700 border-blue-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected'  => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $stClass = $badgeMap[$req->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            $prioMap = [
                                'low'    => 'bg-slate-100 text-slate-600 border-slate-200',
                                'medium' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'high'   => 'bg-red-50 text-red-700 border-red-200',
                            ];
                            $pClass = $prioMap[$req->priority] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                            $techOnline = $req->technician ? ($req->technician->is_active && $req->technician->isEffectivelyOnline()) : false;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-bold text-slate-900">#TSK-{{ str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</span>
                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 text-[9px] font-bold">
                                        {{ $req->service_category_label }}
                                    </span>
                                </div>
                                <div class="text-xs font-semibold text-slate-800 truncate max-w-[220px] mt-0.5" title="{{ $req->title }}">{{ $req->title }}</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">{{ $req->created_at ? $req->created_at->diffForHumans() : '-' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $req->customer_name }}</div>
                                <div class="text-[11px] text-blue-600 font-medium mt-0.5">{{ $req->customer_phone }}</div>
                                @if($req->customer_email)
                                    <div class="text-[10px] text-slate-400 truncate max-w-[160px]">{{ $req->customer_email }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="text-slate-700 truncate max-w-[190px]" title="{{ $req->resolved_location }}">
                                    <ion-icon name="location-outline" class="text-slate-400 align-middle"></ion-icon>
                                    {{ $req->resolved_location }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    @if($req->latitude && $req->longitude)
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-bold">GPS Pinned</span>
                                    @endif
                                    @if($req->client_photo_url)
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200 text-[9px] font-bold inline-flex items-center gap-0.5">
                                            <ion-icon name="image-outline"></ion-icon> Photo
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $pClass }} capitalize inline-flex items-center gap-1">
                                    @if($req->priority === 'high')
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    @endif
                                    {{ $req->priority }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $stClass }}">
                                    {{ $req->status_label }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($req->technician)
                                    <div class="inline-flex items-center gap-2">
                                        <div class="relative flex-shrink-0">
                                            <div class="h-7 w-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-[10px]">
                                                {{ strtoupper(substr($req->technician->name, 0, 1)) }}
                                            </div>
                                            <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-white {{ $techOnline ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $req->technician->name }}</div>
                                            <div class="text-[10px] {{ $techOnline ? 'text-emerald-600 font-semibold' : 'text-slate-400' }}">
                                                {{ $techOnline ? 'Online' : 'Offline' }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                                        Unassigned
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <!-- Eye Icon: Inspect Full Detail & Assign Online / Free Technician -->
                                    <button type="button"
                                        data-request='@json($modalPayload)'
                                        onclick="openClientRequestModal(this)"
                                        class="h-8 px-2.5 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 inline-flex items-center gap-1 text-[11px] font-bold transition-all cursor-pointer"
                                        title="Inspect Client Request & Assign Technician">
                                        <ion-icon name="eye-outline" class="text-sm"></ion-icon>
                                        <span class="hidden sm:inline">Inspect / Assign</span>
                                    </button>

                                    @if(in_array($req->status, ['pending', 'rejected'], true))
                                        <!-- 1-Click Auto-Assign Nearest Online -->
                                        <form action="{{ route('admin.client_requests.auto_assign', $req->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="h-8 w-8 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 inline-flex items-center justify-center transition-all cursor-pointer"
                                                title="1-Click Auto-Assign Nearest Online Technician">
                                                <ion-icon name="flash-outline" class="text-sm"></ion-icon>
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.tasks.show', $req->id) }}"
                                        class="h-8 w-8 rounded-lg bg-slate-100 hover:bg-slate-900 text-slate-600 hover:text-white inline-flex items-center justify-center transition-all"
                                        title="Open Full Task Command Details">
                                        <ion-icon name="open-outline" class="text-sm"></ion-icon>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                                    <ion-icon name="mail-open-outline" class="text-2xl"></ion-icon>
                                </div>
                                <p class="text-sm font-bold text-slate-700">No client service requests found.</p>
                                <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                    Share the direct public portal link (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">{{ $publicFormUrl }}</code>) so customers can submit requests directly without logging in.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Eye-Icon Detail & Smart Dispatch Modal (Outside hot-swap or re-bound cleanly) -->
<div id="client-request-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) closeClientRequestModal()">
    <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
        <!-- Modal Header -->
        <div class="sticky top-0 z-10 bg-slate-900 text-white px-6 py-4 flex items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <span id="crm-code" class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-blue-600 text-white">#TSK-0000</span>
                    <span id="crm-category" class="text-[10px] font-bold uppercase tracking-wider text-blue-300">Category</span>
                    <span id="crm-status" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-white/10 text-white">Pending</span>
                </div>
                <h3 id="crm-title" class="text-sm sm:text-base font-bold mt-1">Work Order Title</h3>
            </div>
            <button type="button" onclick="closeClientRequestModal()" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center cursor-pointer">
                <ion-icon name="close" class="text-lg"></ion-icon>
            </button>
        </div>

        <div class="p-6 space-y-5">
            <!-- Client & Location Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Customer Contact</div>
                    <div id="crm-customer" class="text-sm font-bold text-slate-900">-</div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <a id="crm-phone-link" href="#" class="inline-flex items-center gap-1 text-blue-600 font-semibold hover:underline">
                            <ion-icon name="call-outline"></ion-icon> <span id="crm-phone">-</span>
                        </a>
                        <span id="crm-email" class="text-slate-500"></span>
                    </div>
                    <div id="crm-submitted" class="text-[10px] text-slate-400 pt-1">Submitted: -</div>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Service Address &amp; Coordinates</div>
                    <div id="crm-address" class="text-xs font-medium text-slate-800 leading-relaxed">-</div>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <a id="crm-gmaps-link" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-[11px] font-bold text-blue-600 hover:underline inline-flex items-center gap-1">
                            <ion-icon name="map-outline"></ion-icon> Open in Google Maps
                        </a>
                        <a id="crm-track-link" href="#" target="_blank" rel="noopener noreferrer" class="text-[11px] font-bold text-emerald-600 hover:underline inline-flex items-center gap-1">
                            <ion-icon name="navigate-outline"></ion-icon> Customer Live Radar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Description + Attached Photo -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2 p-4 rounded-xl bg-white border border-slate-200">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Client Problem Description</div>
                    <p id="crm-description" class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">-</p>
                </div>
                <div id="crm-photo-card" class="hidden p-3 rounded-xl bg-slate-50 border border-slate-200 flex flex-col items-center justify-center text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Attached Client Photo</div>
                    <a id="crm-photo-link" href="#" target="_blank" rel="noopener noreferrer">
                        <img id="crm-photo-img" src="" alt="Client Photo" class="max-h-32 rounded-lg border border-slate-200 object-cover hover:opacity-90 transition-opacity">
                    </a>
                </div>
            </div>

            <!-- Smart Technician Dispatch Section (Online Nearest + Free Choice All Active) -->
            <div id="crm-dispatch-panel" class="p-4 sm:p-5 rounded-2xl bg-blue-50/50 border border-blue-200 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-blue-900">Assign Field Technician (Online or Free Choice)</h4>
                        <p class="text-[11px] text-slate-600 mt-0.5">Select any online technician (sorted by GPS proximity) or freely assign any active technician below.</p>
                    </div>
                    <form id="crm-auto-assign-form" method="POST" action="">
                        @csrf
                        <button type="submit"
                            class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <ion-icon name="flash-outline" class="text-sm"></ion-icon>
                            <span>1-Click Auto-Assign Online</span>
                        </button>
                    </form>
                </div>

                <form id="crm-assign-form" method="POST" action="" class="space-y-3">
                    @csrf
                    <input type="hidden" name="technician_id" id="crm-selected-tech-id" value="">

                    <!-- Group 1: Online Now -->
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 mb-2 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Online Right Now (Sorted Nearest GPS)
                        </div>
                        <div id="crm-online-tech-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
                    </div>

                    <!-- Group 2: All Other Active Technicians (Free Choice) -->
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                            Other Active Technicians (Free Choice)
                        </div>
                        <div id="crm-offline-tech-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-2"></div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-blue-200/70">
                        <a id="crm-full-detail-link" href="#" class="text-xs font-bold text-slate-600 hover:text-slate-900 inline-flex items-center gap-1">
                            Open Full Task Command Details &rarr;
                        </a>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="toggleRejectBox()"
                                class="px-3.5 py-2 rounded-xl bg-white hover:bg-red-50 text-red-600 border border-red-200 text-xs font-bold cursor-pointer">
                                Decline Request
                            </button>
                            <button type="submit" id="crm-btn-assign-submit" disabled
                                class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                                <ion-icon name="paper-plane-outline" class="text-sm"></ion-icon>
                                <span>Assign Selected Technician</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Collapsible Reject Form -->
                <form id="crm-reject-form" method="POST" action="" class="hidden pt-3 border-t border-red-200 space-y-2">
                    @csrf
                    <label class="block text-xs font-bold text-red-800">Reason for Declining / Cancelling Request:</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="reason" required placeholder="e.g. Outside fiber coverage area / duplicate report"
                            class="flex-1 rounded-xl border border-red-200 bg-white px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-400">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold cursor-pointer">
                            Confirm Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function copyPublicFormUrl(url, btn) {
        navigator.clipboard.writeText(url).then(function() {
            var span = btn.querySelector('span');
            if (span) {
                var orig = span.textContent;
                span.textContent = 'Copied!';
                setTimeout(function() { span.textContent = orig; }, 2000);
            }
        });
    }

    function haversineKm(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function escModal(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    function selectModalTech(techId) {
        var input = document.getElementById('crm-selected-tech-id');
        var submitBtn = document.getElementById('crm-btn-assign-submit');
        if (input) input.value = String(techId);
        if (submitBtn) submitBtn.disabled = !techId;

        document.querySelectorAll('.crm-tech-option').forEach(function(el) {
            var isMatch = String(el.dataset.techId) === String(techId);
            el.classList.toggle('border-blue-600', isMatch);
            el.classList.toggle('bg-blue-50', isMatch);
            el.classList.toggle('ring-2', isMatch);
            el.classList.toggle('ring-blue-200', isMatch);
            el.classList.toggle('border-slate-200', !isMatch);
            el.classList.toggle('bg-white', !isMatch);
        });
    }

    function toggleRejectBox() {
        var rf = document.getElementById('crm-reject-form');
        if (rf) rf.classList.toggle('hidden');
    }

    function closeClientRequestModal() {
        document.getElementById('client-request-modal')?.classList.add('hidden');
    }

    function openClientRequestModal(btn) {
        var raw = btn.getAttribute('data-request');
        if (!raw) return;
        var req = JSON.parse(raw);

        var rootEl = document.getElementById('client-requests-root');
        var fleet = [];
        try {
            fleet = JSON.parse(rootEl?.getAttribute('data-fleet') || '[]');
        } catch (e) {}

        document.getElementById('crm-code').textContent = req.code;
        document.getElementById('crm-category').textContent = req.service_category_label || 'Client Request';
        document.getElementById('crm-status').textContent = req.status_label || req.status;
        document.getElementById('crm-title').textContent = req.title;
        document.getElementById('crm-customer').textContent = req.customer_name;
        document.getElementById('crm-phone').textContent = req.customer_phone || '-';
        document.getElementById('crm-phone-link').href = req.customer_phone ? ('tel:' + req.customer_phone) : '#';
        document.getElementById('crm-email').textContent = req.customer_email ? ('• ' + req.customer_email) : '';
        document.getElementById('crm-submitted').textContent = 'Submitted: ' + req.created_at_full + ' (' + req.created_at_human + ')';
        document.getElementById('crm-address').textContent = req.address || '-';
        document.getElementById('crm-description').textContent = req.description || 'No additional description provided.';
        document.getElementById('crm-track-link').href = req.tracking_url || '#';
        document.getElementById('crm-full-detail-link').href = req.detail_url || '#';

        var gmaps = document.getElementById('crm-gmaps-link');
        if (req.latitude && req.longitude) {
            gmaps.href = 'https://www.google.com/maps?q=' + req.latitude + ',' + req.longitude;
            gmaps.classList.remove('hidden');
        } else {
            gmaps.classList.add('hidden');
        }

        var photoCard = document.getElementById('crm-photo-card');
        if (req.client_photo_url) {
            document.getElementById('crm-photo-img').src = req.client_photo_url;
            document.getElementById('crm-photo-link').href = req.client_photo_url;
            photoCard.classList.remove('hidden');
        } else {
            photoCard.classList.add('hidden');
        }

        document.getElementById('crm-assign-form').action = req.assign_url;
        document.getElementById('crm-auto-assign-form').action = req.auto_assign_url;
        document.getElementById('crm-reject-form').action = req.reject_url;
        document.getElementById('crm-reject-form').classList.add('hidden');

        var dispatchPanel = document.getElementById('crm-dispatch-panel');
        dispatchPanel.classList.toggle('hidden', req.status === 'completed');

        // Enrich technicians with distance to client pin & split Online vs Free Choice
        var enriched = fleet.map(function(t) {
            var distKm = null;
            if (req.latitude && req.longitude && t.latitude !== null && t.longitude !== null) {
                distKm = haversineKm(req.latitude, req.longitude, t.latitude, t.longitude);
            }
            return Object.assign({}, t, { distKm: distKm });
        });

        var onlineList = enriched.filter(function(t) { return t.is_online; }).sort(function(a, b) {
            var da = a.distKm !== null ? a.distKm : 99999;
            var db = b.distKm !== null ? b.distKm : 99999;
            return (da - db) || (a.active_tasks_count - b.active_tasks_count);
        });
        var offlineList = enriched.filter(function(t) { return !t.is_online; }).sort(function(a, b) {
            return a.active_tasks_count - b.active_tasks_count;
        });

        function renderTechCard(t) {
            var distBadge = t.distKm !== null
                ? ('<span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-[10px] font-bold">' + t.distKm.toFixed(1) + ' km</span>')
                : '<span class="text-[10px] text-slate-400">No GPS</span>';
            var statusDot = t.is_online ? 'bg-emerald-500' : 'bg-slate-300';
            return '<button type="button" data-tech-id="' + t.id + '" onclick="selectModalTech(' + t.id + ')" '
                + 'class="crm-tech-option text-left p-2.5 rounded-xl border border-slate-200 bg-white hover:border-blue-400 transition-all flex items-center justify-between gap-2 cursor-pointer">'
                + '<div class="flex items-center gap-2 min-w-0">'
                + '<span class="w-2.5 h-2.5 rounded-full ' + statusDot + ' flex-shrink-0"></span>'
                + '<div class="min-w-0">'
                + '<div class="text-xs font-bold text-slate-900 truncate">' + escModal(t.name) + '</div>'
                + '<div class="text-[10px] text-slate-500">' + t.active_tasks_count + ' active • ' + escModal(t.last_ping) + '</div>'
                + '</div></div>'
                + distBadge + '</button>';
        }

        var onlineGrid = document.getElementById('crm-online-tech-grid');
        var offlineGrid = document.getElementById('crm-offline-tech-grid');

        onlineGrid.innerHTML = onlineList.length > 0
            ? onlineList.map(renderTechCard).join('')
            : '<div class="sm:col-span-2 text-[11px] text-slate-500 italic p-2 bg-white rounded-xl border border-slate-200">No technicians currently online within 2-min GPS window. Choose any active technician below.</div>';

        offlineGrid.innerHTML = offlineList.length > 0
            ? offlineList.map(renderTechCard).join('')
            : '<div class="sm:col-span-2 text-[11px] text-slate-400 italic p-2">All active technicians are online above.</div>';

        selectModalTech(req.technician_id || '');
        document.getElementById('client-request-modal').classList.remove('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeClientRequestModal();
    });
</script>
@endpush
@endsection
