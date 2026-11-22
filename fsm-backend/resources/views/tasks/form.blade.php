@extends('layouts.admin')

@section('title', $task->exists ? 'Edit Task' : 'Create New Task')
@section('header', $task->exists ? 'Edit Task #'.str_pad($task->id, 4, '0', STR_PAD_LEFT) : 'Geo-Dispatcher: Create New Task')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #location-picker-map {
        height: 410px;
        width: 100%;
        border-radius: 14px;
        z-index: 1;
        border: 1px solid #CBD5E1;
        cursor: crosshair;
    }
    .map-fullscreen-active {
        position: fixed !important;
        inset: 1rem !important;
        z-index: 60 !important;
        height: calc(100vh - 2rem) !important;
        background: #ffffff;
        padding: 1rem;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.45);
        display: flex;
        flex-direction: column;
    }
    .map-fullscreen-active #location-picker-map {
        flex: 1;
        height: 100% !important;
    }
    .priority-card {
        transition: all 0.18s ease;
    }
    .priority-card.is-selected-low {
        border-color: #64748B;
        background-color: #F8FAFC;
        box-shadow: 0 0 0 2px rgba(100, 116, 139, 0.25);
    }
    .priority-card.is-selected-medium {
        border-color: #D97706;
        background-color: #FFFBEB;
        box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.22);
    }
    .priority-card.is-selected-high {
        border-color: #DC2626;
        background-color: #FEF2F2;
        box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.22);
    }
    .tech-card {
        transition: all 0.18s ease;
    }
    .tech-card.is-selected {
        border-color: #2563EB;
        background-color: #EFF6FF;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.22);
    }
    .map-search-results {
        max-height: 220px;
        overflow-y: auto;
    }
    .layer-pill.active {
        background-color: #0F172A;
        color: #FFFFFF;
        border-color: #0F172A;
    }
    .leaflet-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
</style>
@endpush

@section('content')
<div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <a href="{{ route('admin.tasks.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 inline-flex items-center gap-1.5">
        <ion-icon name="arrow-back-outline"></ion-icon> Back to Task List
    </a>
    <div class="flex items-center gap-2 text-[11px] text-slate-500">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-semibold">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            Live Geo-Dispatcher Ready
        </span>
        @if($geofenceRadius > 0)
            <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
                <ion-icon name="radio-outline"></ion-icon>
                Geofence: {{ $geofenceRadius }}m
            </span>
        @endif
    </div>
</div>

@if($errors->any())
    <div class="max-w-7xl mx-auto bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs mb-6 flex items-start gap-2.5 shadow-sm">
        <ion-icon name="alert-circle" class="text-base text-red-500 flex-shrink-0 mt-0.5"></ion-icon>
        <div>
            <span class="font-semibold block mb-0.5">Please correct the following validation issues:</span>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<form id="task-workbench-form" action="{{ $task->exists ? route('admin.tasks.update', $task->id) : route('admin.tasks.store') }}" method="POST" class="max-w-7xl mx-auto">
    @csrf
    @if($task->exists)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ===================================================================== -->
        <!-- LEFT COLUMN (7 cols): Task Details, Customer Info & Technician Cards  -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-7 space-y-6">

            <!-- SECTION 1: TASK DETAILS & 1-CLICK TEMPLATES -->
            <div class="bg-white rounded-2xl border border-fsmBorder p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-fsmBorder">
                    <div>
                        <h2 class="text-sm font-bold text-textPrimary flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">1</span>
                            {{ $task->exists ? 'Modify Work Order Details' : 'Work Order Specification' }}
                        </h2>
                        <p class="text-[11px] text-textSecondary mt-0.5">Use a 1-click ISP template or enter custom dispatch parameters.</p>
                    </div>
                    @if($task->exists)
                        <span class="text-[11px] font-mono font-bold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                            #TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}
                        </span>
                    @endif
                </div>

                @if(!$task->exists)
                <!-- 1-Click ISP Work Order Templates -->
                <div class="mb-5">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                        Quick ISP Field Templates (1-Click Auto-Fill)
                    </label>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="applyTaskTemplate('ftth_install')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200/80 transition-all inline-flex items-center gap-1">
                            <ion-icon name="flash-outline"></ion-icon> FTTH New Install
                        </button>
                        <button type="button" onclick="applyTaskTemplate('los_red')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-red-50 hover:bg-red-100 text-red-700 border border-red-200/80 transition-all inline-flex items-center gap-1">
                            <ion-icon name="warning-outline"></ion-icon> LOS Red Light / Cut
                        </button>
                        <button type="button" onclick="applyTaskTemplate('high_loss')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200/80 transition-all inline-flex items-center gap-1">
                            <ion-icon name="pulse-outline"></ion-icon> High Optical Loss
                        </button>
                        <button type="button" onclick="applyTaskTemplate('ont_replace')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200/80 transition-all inline-flex items-center gap-1">
                            <ion-icon name="hardware-chip-outline"></ion-icon> ONT Replacement
                        </button>
                        <button type="button" onclick="applyTaskTemplate('sla_audit')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-semibold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200/80 transition-all inline-flex items-center gap-1">
                            <ion-icon name="shield-checkmark-outline"></ion-icon> Corporate SLA Audit
                        </button>
                    </div>
                </div>
                @endif

                <div class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label for="task-title-input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Work Order Title / Subject <span class="text-red-500">*</span>
                        </label>
                        <input id="task-title-input" type="text" name="title" value="{{ old('title', $task->title) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                            placeholder="e.g. FTTH Loss of Signal (LOS Red) - Splice Restoration">
                    </div>

                    <!-- Service Category -->
                    <div>
                        <label for="service-category-select" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            ISP Service Category <span class="text-slate-400 font-normal">(FTTH / FTTB Classification)</span>
                        </label>
                        @php $currCat = old('service_category', $task->service_category ?? 'repair'); @endphp
                        <select id="service-category-select" name="service_category"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all bg-white">
                            <option value="new_install" {{ $currCat === 'new_install' ? 'selected' : '' }}>New Subscriber Installation (FTTH Provisioning)</option>
                            <option value="repair" {{ $currCat === 'repair' ? 'selected' : '' }}>Incident Repair / Fiber Splicing</option>
                            <option value="maintenance" {{ $currCat === 'maintenance' ? 'selected' : '' }}>Preventative Maintenance &amp; Optical Audit</option>
                            <option value="relocation" {{ $currCat === 'relocation' ? 'selected' : '' }}>Premise Relocation / Drop-Wire Re-route</option>
                            <option value="dismantle" {{ $currCat === 'dismantle' ? 'selected' : '' }}>Service Dismantle &amp; Hardware Recovery</option>
                        </select>
                    </div>

                    <!-- On-Site Billing Amount (Dynamic QRIS) -->
                    <div>
                        <label for="task-billing-input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            On-Site Billing Amount <span class="text-slate-400 font-normal">(Optional — enables Technician Dynamic QRIS collection)</span>
                        </label>
                        <input id="task-billing-input" type="number" step="1000" min="0" name="billing_amount" value="{{ old('billing_amount', $task->billing_amount ?? 0) }}"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all font-mono"
                            placeholder="0 (Free / Warranty) or e.g. 250000">
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="task-desc-input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Field Troubleshooting Instructions &amp; Checklist
                        </label>
                        <textarea id="task-desc-input" name="description" rows="3"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all leading-relaxed"
                            placeholder="Describe ODP port, expected optical power target (-15 to -22 dBm), required hardware, or site access notes...">{{ old('description', $task->description) }}</textarea>
                    </div>

                    <!-- Visual Priority Selector -->
                    @php $currentPriority = old('priority', $task->priority ?? 'medium'); @endphp
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Priority &amp; SLA Urgency <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="priority" id="priority-input" value="{{ $currentPriority }}">
                        <div class="grid grid-cols-3 gap-2.5">
                            <button type="button" data-priority="low" onclick="selectPriority('low')"
                                class="priority-card text-left p-3 rounded-xl border border-slate-200 hover:border-slate-300 {{ $currentPriority === 'low' ? 'is-selected-low' : '' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-700">Low</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                                </div>
                                <span class="text-[10px] text-slate-500 block mt-0.5">Routine / Scheduled</span>
                            </button>

                            <button type="button" data-priority="medium" onclick="selectPriority('medium')"
                                class="priority-card text-left p-3 rounded-xl border border-slate-200 hover:border-amber-300 {{ $currentPriority === 'medium' ? 'is-selected-medium' : '' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-amber-800">Medium</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                </div>
                                <span class="text-[10px] text-amber-700/80 block mt-0.5">Standard SLA</span>
                            </button>

                            <button type="button" data-priority="high" onclick="selectPriority('high')"
                                class="priority-card text-left p-3 rounded-xl border border-slate-200 hover:border-red-300 {{ $currentPriority === 'high' ? 'is-selected-high' : '' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-red-700">High / Urgent</span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-pulse"></span>
                                </div>
                                <span class="text-[10px] text-red-600/90 block mt-0.5">Triggers NOC Alert</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scheduled Due Date + Quick SLA Chips -->
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                            <label for="scheduled-at-input" class="text-xs font-semibold text-slate-700">
                                Scheduled Due Date &amp; SLA Window
                            </label>
                            <div class="flex flex-wrap items-center gap-1">
                                <button type="button" onclick="applyQuickSchedule('plus_2h')" class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 transition-all">+{{ max(1, $slaHours) }}h SLA</button>
                                <button type="button" onclick="applyQuickSchedule('plus_4h')" class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 transition-all">+4h</button>
                                <button type="button" onclick="applyQuickSchedule('today_17')" class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 transition-all">Today 17:00</button>
                                <button type="button" onclick="applyQuickSchedule('tomorrow_09')" class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 transition-all">Tomorrow 09:00</button>
                            </div>
                        </div>
                        <input id="scheduled-at-input" type="datetime-local" name="scheduled_at"
                            value="{{ old('scheduled_at', $task->scheduled_at ? $task->scheduled_at->format('Y-m-d\TH:i') : '') }}"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all bg-white">
                    </div>

                    @if($task->exists)
                    <!-- Edit Mode: Status & Audit Override -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Work Order Status <span class="text-red-500">*</span></label>
                            <select name="status" required
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all bg-white">
                                <option value="pending" {{ old('status', $task->status) === 'pending' ? 'selected' : '' }}>Pending (Unassigned/Waiting)</option>
                                <option value="assigned" {{ old('status', $task->status) === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                <option value="accepted" {{ old('status', $task->status) === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="on-going" {{ old('status', $task->status) === 'on-going' ? 'selected' : '' }}>On-Going</option>
                                <option value="completed" {{ old('status', $task->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ old('status', $task->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Override / Audit Reason</label>
                            <input type="text" name="override_reason" value="{{ old('override_reason') }}"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                                placeholder="Required when overriding state or modifying completed BAST">
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- SECTION 2: CUSTOMER INFO & ADDRESS -->
            <div class="bg-white rounded-2xl border border-fsmBorder p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-fsmBorder">
                    <div>
                        <h2 class="text-sm font-bold text-textPrimary flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">2</span>
                            Customer &amp; Premise Address
                        </h2>
                        <p class="text-[11px] text-textSecondary mt-0.5">Select a returning subscriber or enter new contact &amp; premise details.</p>
                    </div>
                </div>

                @if(isset($registeredCustomers) && $registeredCustomers->count() > 0)
                <!-- Registered Subscriber CRM Quick-Fill -->
                <div class="mb-3 p-3 rounded-xl bg-blue-50/70 border border-blue-200">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="customer-crm-lookup" class="text-[11px] font-bold text-blue-900 flex items-center gap-1.5">
                            <ion-icon name="business-outline" class="text-blue-700 text-sm"></ion-icon>
                            Registered Subscriber CRM Database
                        </label>
                        <span class="text-[10px] text-blue-600 font-medium">Auto-fills info, coordinates &amp; linked ODP</span>
                    </div>
                    <select id="customer-crm-lookup" name="customer_id" onchange="applyRegisteredCustomer(this.value)"
                        class="w-full rounded-lg border border-blue-200 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
                        <option value="">-- Choose registered customer (or leave blank for custom/new lead) --</option>
                        @foreach($registeredCustomers as $rc)
                            <option value="{{ $rc->id }}" 
                                data-name="{{ $rc->name }}"
                                data-phone="{{ $rc->phone }}"
                                data-address="{{ $rc->address }}"
                                data-lat="{{ $rc->latitude }}"
                                data-lng="{{ $rc->longitude }}"
                                data-fiber-node-id="{{ $rc->fiber_node_id }}"
                                data-pppoe="{{ $rc->pppoe_username }}"
                                data-ont="{{ $rc->ont_serial_number }}"
                                {{ (string) old('customer_id', $task->customer_id) === (string) $rc->id ? 'selected' : '' }}>
                                [{{ $rc->customer_code }}] {{ $rc->name }} • {{ $rc->service_plan ?: 'Standard' }} ({{ $rc->fiberNode ? 'ODP: '.$rc->fiberNode->code : 'No ODP' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                @if(isset($recentCustomers) && $recentCustomers->count() > 0)
                <!-- Existing Customer Quick-Lookup Autocomplete -->
                <div class="mb-4 p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="customer-quick-lookup" class="text-[11px] font-bold text-slate-700 flex items-center gap-1.5">
                            <ion-icon name="people-circle-outline" class="text-blue-600 text-sm"></ion-icon>
                            Returning Subscriber Quick-Fill (Past Tickets)
                        </label>
                        <span class="text-[10px] text-slate-400">Auto-fills name, phone, address &amp; map pin</span>
                    </div>
                    <select id="customer-quick-lookup" onchange="applyExistingCustomer(this.value)"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
                        <option value="">-- Choose an existing customer from past work orders --</option>
                        @foreach($recentCustomers as $idx => $cust)
                            <option value="{{ $idx }}">
                                {{ $cust['name'] }}{{ $cust['phone'] ? ' • ' . $cust['phone'] : '' }}{{ $cust['address'] ? ' — ' . \Illuminate\Support\Str::limit($cust['address'], 48) : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer-name-input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Customer / Subscriber Name <span class="text-red-500">*</span>
                        </label>
                        <input id="customer-name-input" type="text" name="customer_name" value="{{ old('customer_name', $task->customer_name) }}" required
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                            placeholder="Enter contact person or enterprise name">
                    </div>

                    <div>
                        <label for="customer-phone-input" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Customer Phone / WhatsApp
                        </label>
                        <input id="customer-phone-input" type="text" name="customer_phone" value="{{ old('customer_phone', $task->customer_phone) }}"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                            placeholder="e.g. +62812XXXXXXXX or +1-555-0199">
                    </div>

                    <!-- Linked Fiber Node (ODP / ODC) -->
                    <div class="md:col-span-2">
                        <label for="fiber-node-select" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Linked Fiber Node (ODP / ODC Asset)
                        </label>
                        <select id="fiber-node-select" name="fiber_node_id" onchange="onFiberNodeChanged(this.value)"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all bg-white">
                            <option value="">-- No Linked Fiber Node / Direct Uplink --</option>
                            @if(isset($fiberNodes))
                                @foreach($fiberNodes as $fn)
                                    <option value="{{ $fn->id }}" data-lat="{{ $fn->latitude }}" data-lng="{{ $fn->longitude }}" {{ (string) old('fiber_node_id', $task->fiber_node_id) === (string) $fn->id ? 'selected' : '' }}>
                                        [{{ $fn->code }}] {{ $fn->name }} ({{ strtoupper($fn->node_type) }} • Ports: {{ $fn->used_ports }}/{{ $fn->total_ports }} used • Status: {{ $fn->status }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="text-[10px] text-slate-400 mt-1 block">When new installation tasks are completed, 1 port on the selected ODP is automatically allocated.</span>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                            <label for="address-input" class="text-xs font-semibold text-slate-700">
                                Installation / Work Address <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <label class="inline-flex items-center gap-1.5 text-[11px] font-medium text-slate-600 cursor-pointer select-none" title="When enabled, moving the map pin automatically resolves the street address">
                                    <input type="checkbox" id="toggle-auto-address" checked class="w-3.5 h-3.5 rounded text-blue-600 border-slate-300 focus:ring-primary">
                                    <span>Auto-sync from map pin</span>
                                </label>
                                <button type="button" id="btn-fetch-address" onclick="manualReverseGeocode()" class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                                    <ion-icon name="refresh-outline"></ion-icon> Fetch Address
                                </button>
                            </div>
                        </div>
                        <textarea id="address-input" name="address" required rows="2"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all leading-relaxed"
                            placeholder="Click on the map or search an address on the right panel to auto-fill, then add floor/suite/landmark details...">{{ old('address', $task->address ?? $task->location) }}</textarea>
                        <div id="address-sync-status" class="text-[10px] text-slate-400 mt-1 flex items-center justify-between">
                            <span>Tip: Editing this field manually locks it from accidental overwrite unless you click "Fetch Address".</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: SMART TECHNICIAN DISPATCH CARDS -->
            <div class="bg-white rounded-2xl border border-fsmBorder p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4 pb-3 border-b border-fsmBorder">
                    <div>
                        <h2 class="text-sm font-bold text-textPrimary flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center justify-center">3</span>
                            Assign Field Technician
                        </h2>
                        <p class="text-[11px] text-textSecondary mt-0.5">Live distance is computed automatically from the pinned location.</p>
                    </div>
                    <button type="button" id="btn-auto-nearest" onclick="selectRecommendedTechnician()"
                        class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold shadow-sm transition-all inline-flex items-center gap-1.5 self-start sm:self-auto">
                        <ion-icon name="sparkles-outline"></ion-icon>
                        Auto-Select Nearest Available
                    </button>
                </div>

                <!-- Hidden/Synced Select for native form submission & accessibility -->
                <select name="technician_id" id="technician_id_select" class="sr-only" aria-label="Select Dispatch Technician">
                    <option value="">-- Leave Unassigned --</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ old('technician_id', $task->technician_id) == $tech->id ? 'selected' : '' }}>
                            {{ $tech->name }} ({{ $tech->email }})
                        </option>
                    @endforeach
                </select>

                <!-- Unassigned Queue Option Card -->
                @php $selectedTechId = (string) old('technician_id', $task->technician_id ?? ''); @endphp
                <div id="tech-cards-container" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div id="tech-card-unassigned" onclick="selectTechnicianCard('')"
                        class="tech-card cursor-pointer rounded-xl border border-slate-200 p-3.5 bg-slate-50/70 hover:border-blue-300 {{ $selectedTechId === '' ? 'is-selected' : '' }}">
                        <div class="flex items-center gap-2.5">
                            <div class="h-9 w-9 rounded-xl bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-base flex-shrink-0">
                                <ion-icon name="layers-outline"></ion-icon>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-textPrimary truncate">
                                    {{ $autoAssignEnabled ? 'Auto-Assign / Dispatch Pool' : 'Leave Unassigned (Pending)' }}
                                </div>
                                <div class="text-[10px] text-textSecondary leading-snug mt-0.5">
                                    {{ $autoAssignEnabled ? 'Server auto-picks closest idle tech on save' : 'Queue ticket in Pending Dispatch pool' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @foreach($technicianFleet as $tech)
                        @php
                            $isSelectedCard = $selectedTechId === (string) $tech['id'];
                            $loadPct = min(($tech['active_tasks_count'] / 5) * 100, 100);
                            $loadBarColor = $tech['active_tasks_count'] >= 4 ? 'bg-red-500' : ($tech['active_tasks_count'] >= 2 ? 'bg-amber-500' : 'bg-emerald-500');
                        @endphp
                        <div id="tech-card-{{ $tech['id'] }}" data-tech-id="{{ $tech['id'] }}" onclick="selectTechnicianCard('{{ $tech['id'] }}')"
                            class="tech-card cursor-pointer rounded-xl border border-slate-200 p-3.5 bg-white hover:border-blue-300 relative {{ $isSelectedCard ? 'is-selected' : '' }}">
                            <span id="tech-rec-badge-{{ $tech['id'] }}" class="hidden absolute -top-2 right-2.5 px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-600 text-white shadow-sm">
                                Recommended
                            </span>

                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="relative flex-shrink-0">
                                        <div class="h-9 w-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($tech['name'], 0, 1)) }}
                                        </div>
                                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white {{ $tech['is_online'] ? 'bg-emerald-500' : 'bg-slate-300' }}"
                                            title="{{ $tech['is_online'] ? 'Online in Field' : 'Offline' }}"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-textPrimary truncate">{{ $tech['name'] }}</div>
                                        <div class="text-[10px] text-textSecondary truncate">{{ $tech['is_online'] ? 'Online • ' : 'Offline • ' }}{{ $tech['last_seen'] }}</div>
                                    </div>
                                </div>
                                <span id="tech-dist-badge-{{ $tech['id'] }}" class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 flex-shrink-0">
                                    No pin
                                </span>
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                                <div class="flex-1">
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="{{ $loadBarColor }} h-1.5 rounded-full" style="width: {{ $loadPct }}%"></div>
                                    </div>
                                </div>
                                <span class="text-[10px] font-semibold text-slate-500 flex-shrink-0">
                                    {{ $tech['active_tasks_count'] }} / 5 active
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- RIGHT COLUMN (5 cols): INTERACTIVE GOOGLE-MAPS-STYLE LOCATION PICKER  -->
        <!-- ===================================================================== -->
        <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-4">
            <div id="map-picker-wrapper" class="bg-white rounded-2xl border border-fsmBorder p-4 sm:p-5 shadow-sm">
                <!-- Map Header -->
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center flex-shrink-0">
                            <ion-icon name="location" class="text-base"></ion-icon>
                        </div>
                        <div>
                            <h3 class="text-xs sm:text-sm font-bold text-textPrimary leading-tight">Interactive Map Location Picker</h3>
                            <p class="text-[10px] text-textSecondary">Search address, paste Google Maps URL, or drag red pin</p>
                        </div>
                    </div>
                    <button type="button" id="btn-toggle-fullscreen" onclick="toggleMapFullscreen()"
                        class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm transition-all" title="Expand / Fullscreen Map">
                        <ion-icon id="fullscreen-icon" name="expand-outline"></ion-icon>
                    </button>
                </div>

                <!-- 1. Floating Address Search Autocomplete (Google Maps Style) -->
                <div class="relative mb-2.5">
                    <div class="flex items-center gap-1.5">
                        <div class="relative flex-1">
                            <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></ion-icon>
                            <input type="text" id="map-address-search" autocomplete="off"
                                class="w-full pl-9 pr-8 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                                placeholder="Search street, building, district, or city...">
                            <span id="map-search-spinner" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-blue-500">
                                <ion-icon name="sync-outline" class="animate-spin text-sm"></ion-icon>
                            </span>
                        </div>
                        <button type="button" onclick="executeAddressSearch()" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-all flex-shrink-0">
                            Find
                        </button>
                    </div>
                    <div id="map-search-results" class="hidden map-search-results absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-40 divide-y divide-slate-100"></div>
                </div>

                <!-- 2. Google Maps Link & Coordinate Smart Parser -->
                <div class="mb-3 p-2.5 rounded-xl bg-blue-50/60 border border-blue-200/70">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider flex items-center gap-1">
                            <ion-icon name="link-outline"></ion-icon> Paste Google Maps Link or Raw Coords
                        </span>
                        <button type="button" onclick="useMyBrowserLocation()" class="text-[10px] font-bold text-blue-700 hover:text-blue-900 inline-flex items-center gap-1">
                            <ion-icon name="locate-outline"></ion-icon> My GPS
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <input type="text" id="gmaps-paste-input"
                            class="flex-1 px-2.5 py-1.5 rounded-lg border border-blue-200 bg-white text-[11px] font-mono text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/25"
                            placeholder="https://maps.google.com/?q=-6.2088,106.8456 or -6.2088, 106.8456">
                        <button type="button" onclick="parsePastedMapLink()" class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-bold transition-all flex-shrink-0">
                            Pin
                        </button>
                    </div>
                    <div id="gmaps-paste-feedback" class="hidden text-[10px] mt-1 font-semibold"></div>
                </div>

                <!-- 3. Map Layer Switcher & Quick Controls -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                    <div class="inline-flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                        <button type="button" id="layer-btn-street" onclick="switchMapLayer('street')" class="layer-pill px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-600 border border-transparent transition-all">Street</button>
                        <button type="button" id="layer-btn-light" onclick="switchMapLayer('light')" class="layer-pill px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-600 border border-transparent transition-all">Clean</button>
                        <button type="button" id="layer-btn-satellite" onclick="switchMapLayer('satellite')" class="layer-pill px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-600 border border-transparent transition-all">Satellite</button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="centerOnTargetPin()" class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold inline-flex items-center gap-1">
                            <ion-icon name="pin-outline"></ion-icon> Center Pin
                        </button>
                        <button type="button" onclick="clearTargetPin()" class="px-2 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 text-[10px] font-semibold inline-flex items-center gap-1">
                            <ion-icon name="trash-outline"></ion-icon> Clear
                        </button>
                    </div>
                </div>

                <!-- 4. Live Interactive Map Canvas -->
                <div class="relative">
                    <div id="location-picker-map"></div>
                    <div id="map-pin-hint" class="pointer-events-none absolute bottom-3 left-3 right-3 z-[400] flex justify-center">
                        <span class="px-3 py-1 rounded-full bg-slate-900/80 backdrop-blur text-white text-[10px] font-semibold shadow-md">
                            Click anywhere on map or drag red pin to lock exact installation coordinates
                        </span>
                    </div>
                </div>

                <!-- 5. Precision Coordinate Inputs & External Preview Links -->
                <div class="grid grid-cols-2 gap-3 mt-3.5">
                    <div>
                        <label for="latitude-input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">GPS Latitude</label>
                        <input id="latitude-input" type="text" name="latitude" value="{{ old('latitude', $task->latitude) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                            placeholder="-6.208800">
                    </div>
                    <div>
                        <label for="longitude-input" class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">GPS Longitude</label>
                        <input id="longitude-input" type="text" name="longitude" value="{{ old('longitude', $task->longitude) }}"
                            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-mono font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                            placeholder="106.845600">
                    </div>
                </div>

                <!-- External Google Maps & Waze Verification Strip -->
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[11px]">
                    <div class="flex items-center gap-3 text-[10px] text-slate-500">
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block"></span> Customer Pin</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Technicians ({{ $technicianFleet->count() }})</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <a id="preview-gmaps-link" href="#" target="_blank" rel="noopener noreferrer"
                            class="hidden px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-[10px] inline-flex items-center gap-1 transition-all">
                            <ion-icon name="map-outline"></ion-icon> Verify in Google Maps
                        </a>
                        <a id="preview-waze-link" href="#" target="_blank" rel="noopener noreferrer"
                            class="hidden px-2.5 py-1 rounded-lg bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold text-[10px] inline-flex items-center gap-1 transition-all">
                            <ion-icon name="navigate-outline"></ion-icon> Waze
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sticky Submit Action Bar -->
            <div class="bg-white rounded-2xl border border-fsmBorder p-4 shadow-sm flex items-center justify-between gap-3">
                <a href="{{ route('admin.tasks.index') }}" class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold rounded-xl text-xs hover:bg-slate-50 transition-all">
                    Cancel
                </a>
                <button type="submit" class="flex-1 sm:flex-initial px-6 py-2.5 bg-primary hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2">
                    <ion-icon name="paper-plane-outline" class="text-sm"></ion-icon>
                    <span>{{ $task->exists ? 'Save Work Order Changes' : 'Create & Dispatch Work Order' }}</span>
                </button>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const technicianFleet = @json($technicianFleet ?? []);
    const recentCustomers = @json($recentCustomers ?? []);
    const defaultMapStyle = @json($mapStyle ?? 'openstreetmap');
    const geofenceRadiusMeters = @json($geofenceRadius ?? 100);
    const configuredSlaHours = @json($slaHours ?? 2);

    let pickerMap = null;
    let activeTileLayer = null;
    let targetMarker = null;
    let geofenceCircle = null;
    let dispatchRouteLine = null;
    const techMapMarkers = {};
    let addressManuallyEdited = @json($task->exists && !empty($task->address));
    let searchDebounceTimer = null;
    let currentRecommendedTechId = null;

    const tileLayersConfig = {
        street: {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attr: '&copy; OpenStreetMap contributors'
        },
        light: {
            url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            attr: '&copy; OpenStreetMap &copy; CARTO'
        },
        satellite: {
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            attr: 'Tiles &copy; Esri &mdash; World Imagery'
        }
    };

    const redPinIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const blueTechIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [22, 36],
        iconAnchor: [11, 36],
        popupAnchor: [1, -30],
        shadowSize: [36, 36]
    });

    const greyTechIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [22, 36],
        iconAnchor: [11, 36],
        popupAnchor: [1, -30],
        shadowSize: [36, 36]
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }

    function haversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371000;
        const toRad = d => d * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(Math.max(0, 1 - a)));
    }

    // ─── 1. TASK TEMPLATES, PRIORITY & QUICK SLA DUE-DATE ─────────────────────
    const taskTemplates = {
        ftth_install: {
            title: 'FTTH New Subscriber Installation & ONT Activation',
            description: '1. Pull dropcore fiber cable from nearest ODP port.\n2. Fusion splice & measure optical attenuation (Target: -15 dBm to -22 dBm).\n3. Provision Dual-Band XPON ONT modem & verify WiFi throughput.\n4. Capture Before/After OPM photos and customer Sign-on-Glass BAST.',
            priority: 'medium'
        },
        los_red: {
            title: 'Emergency LOS Red Alarm - Fiber Break Restoration',
            description: '1. Inspect customer indoor rosette, patchcord, and dropcore cable span.\n2. Locate optical break using VFL / OTDR and perform fusion re-splice.\n3. Confirm solid green PON indicator on ONT and stable ping < 10ms.\n4. Upload before/after OPM readings and collect customer sign-off.',
            priority: 'high'
        },
        high_loss: {
            title: 'High Optical Attenuation (> -25 dBm) Signal Re-Splicing',
            description: '1. Clean SC/APC connectors at ODP and subscriber rosette.\n2. Check macro-bending along drop cable route and re-splice high-loss joints.\n3. Verify final optical power within carrier standard (-16 to -22 dBm).',
            priority: 'medium'
        },
        ont_replace: {
            title: 'ONT Modem Hardware Replacement & PPPoE Re-Provisioning',
            description: '1. Replace faulty ONT hardware and record new Serial Number in BOM ledger.\n2. Verify VLAN & PPPoE / IPoE session registration with NOC.\n3. Confirm WiFi SSID/credentials match subscriber profile and test speed.',
            priority: 'medium'
        },
        sla_audit: {
            title: 'Enterprise Dedicated Fiber SLA Preventive Audit',
            description: '1. Measure optical power margin on primary and backup dark-fiber cores.\n2. Inspect rack patch panel labeling and power redundancy.\n3. Generate cryptographic SHA-256 BAST compliance report.',
            priority: 'low'
        }
    };

    function applyTaskTemplate(key) {
        const tpl = taskTemplates[key];
        if (!tpl) return;
        const titleEl = document.getElementById('task-title-input');
        const descEl = document.getElementById('task-desc-input');
        if (titleEl) titleEl.value = tpl.title;
        if (descEl) descEl.value = tpl.description;
        selectPriority(tpl.priority);
        if (tpl.priority === 'high') {
            applyQuickSchedule('plus_2h');
        }
    }

    function selectPriority(level) {
        const input = document.getElementById('priority-input');
        if (input) input.value = level;
        document.querySelectorAll('.priority-card').forEach(card => {
            card.classList.remove('is-selected-low', 'is-selected-medium', 'is-selected-high');
            if (card.getAttribute('data-priority') === level) {
                card.classList.add('is-selected-' + level);
            }
        });
    }

    function formatDateTimeLocal(date) {
        const pad = n => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function applyQuickSchedule(preset) {
        const now = new Date();
        let target = new Date(now.getTime());
        if (preset === 'plus_2h') {
            target.setHours(target.getHours() + Math.max(1, Number(configuredSlaHours) || 2));
        } else if (preset === 'plus_4h') {
            target.setHours(target.getHours() + 4);
        } else if (preset === 'today_17') {
            target.setHours(17, 0, 0, 0);
            if (target <= now) target.setDate(target.getDate() + 1);
        } else if (preset === 'tomorrow_09') {
            target.setDate(target.getDate() + 1);
            target.setHours(9, 0, 0, 0);
        }
        const input = document.getElementById('scheduled-at-input');
        if (input) input.value = formatDateTimeLocal(target);
    }

    // ─── 2. RETURNING SUBSCRIBER QUICK-FILL ───────────────────────────────────
    function applyExistingCustomer(indexStr) {
        if (indexStr === '') return;
        const cust = recentCustomers[Number(indexStr)];
        if (!cust) return;

        document.getElementById('customer-name-input').value = cust.name || '';
        document.getElementById('customer-phone-input').value = cust.phone || '';
        if (cust.address) {
            document.getElementById('address-input').value = cust.address;
            addressManuallyEdited = true;
            document.getElementById('toggle-auto-address').checked = false;
        }
        if (cust.latitude !== null && cust.longitude !== null) {
            setTargetLocation(cust.latitude, cust.longitude, { fly: true, skipReverseGeocode: !!cust.address });
        }
    }

    function applyRegisteredCustomer(customerId) {
        if (!customerId) return;
        const select = document.getElementById('customer-crm-lookup');
        if (!select) return;
        const opt = select.options[select.selectedIndex];
        if (!opt) return;

        const name = opt.getAttribute('data-name');
        const phone = opt.getAttribute('data-phone');
        const address = opt.getAttribute('data-address');
        const lat = opt.getAttribute('data-lat');
        const lng = opt.getAttribute('data-lng');
        const fiberNodeId = opt.getAttribute('data-fiber-node-id');

        if (name) document.getElementById('customer-name-input').value = name;
        if (phone) document.getElementById('customer-phone-input').value = phone;
        if (address) {
            document.getElementById('address-input').value = address;
            addressManuallyEdited = true;
            const toggle = document.getElementById('toggle-auto-address');
            if (toggle) toggle.checked = false;
        }
        if (fiberNodeId) {
            const fnSelect = document.getElementById('fiber-node-select');
            if (fnSelect) fnSelect.value = fiberNodeId;
        }
        if (lat && lng && !isNaN(parseFloat(lat)) && !isNaN(parseFloat(lng))) {
            setTargetLocation(parseFloat(lat), parseFloat(lng), { fly: true, skipReverseGeocode: !!address });
        }
    }

    function onFiberNodeChanged(fiberNodeId) {
        if (!fiberNodeId) return;
        const select = document.getElementById('fiber-node-select');
        const opt = select ? select.options[select.selectedIndex] : null;
        if (!opt) return;
        const lat = opt.getAttribute('data-lat');
        const lng = opt.getAttribute('data-lng');
        const latInput = document.getElementById('latitude-input');
        const lngInput = document.getElementById('longitude-input');
        if ((!latInput.value || !lngInput.value) && lat && lng) {
            setTargetLocation(parseFloat(lat), parseFloat(lng), { fly: true, skipReverseGeocode: false });
        }
    }

    // ─── 3. MAP LAYER SWITCHER, FULLSCREEN & PIN MANAGEMENT ───────────────────
    function switchMapLayer(layerKey) {
        const cfg = tileLayersConfig[layerKey] || tileLayersConfig.street;
        if (activeTileLayer) {
            pickerMap.removeLayer(activeTileLayer);
        }
        activeTileLayer = L.tileLayer(cfg.url, { maxZoom: 19, attribution: cfg.attr }).addTo(pickerMap);

        ['street', 'light', 'satellite'].forEach(k => {
            const btn = document.getElementById('layer-btn-' + k);
            if (btn) btn.classList.toggle('active', k === layerKey);
        });
    }

    function toggleMapFullscreen() {
        const wrapper = document.getElementById('map-picker-wrapper');
        const icon = document.getElementById('fullscreen-icon');
        if (!wrapper) return;
        const isFull = wrapper.classList.toggle('map-fullscreen-active');
        if (icon) icon.setAttribute('name', isFull ? 'contract-outline' : 'expand-outline');
        setTimeout(() => pickerMap && pickerMap.invalidateSize(), 150);
    }

    function updateExternalPreviewLinks(lat, lng) {
        const gmapsLink = document.getElementById('preview-gmaps-link');
        const wazeLink = document.getElementById('preview-waze-link');
        const hint = document.getElementById('map-pin-hint');

        if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
            if (gmapsLink) {
                gmapsLink.href = `https://www.google.com/maps/search/?api=1&query=${lat.toFixed(6)},${lng.toFixed(6)}`;
                gmapsLink.classList.remove('hidden');
            }
            if (wazeLink) {
                wazeLink.href = `https://waze.com/ul?ll=${lat.toFixed(6)},${lng.toFixed(6)}&navigate=yes`;
                wazeLink.classList.remove('hidden');
            }
            if (hint) hint.classList.add('hidden');
        } else {
            if (gmapsLink) gmapsLink.classList.add('hidden');
            if (wazeLink) wazeLink.classList.add('hidden');
            if (hint) hint.classList.remove('hidden');
        }
    }

    function setTargetLocation(lat, lng, options = {}) {
        const parsedLat = Number(lat);
        const parsedLng = Number(lng);
        if (isNaN(parsedLat) || isNaN(parsedLng) || parsedLat < -90 || parsedLat > 90 || parsedLng < -180 || parsedLng > 180) {
            return;
        }

        const latInput = document.getElementById('latitude-input');
        const lngInput = document.getElementById('longitude-input');
        latInput.value = parsedLat.toFixed(6);
        lngInput.value = parsedLng.toFixed(6);

        if (targetMarker) {
            targetMarker.setLatLng([parsedLat, parsedLng]);
        } else {
            targetMarker = L.marker([parsedLat, parsedLng], { icon: redPinIcon, draggable: true }).addTo(pickerMap);
            targetMarker.on('dragend', function(ev) {
                const pos = ev.target.getLatLng();
                setTargetLocation(pos.lat, pos.lng, { fly: false });
            });
        }
        targetMarker.bindPopup(`<b>Installation Target Pin</b><br><span style="font-family:monospace;font-size:11px;">${parsedLat.toFixed(6)}, ${parsedLng.toFixed(6)}</span>`);

        // Geofence tolerance ring
        if (geofenceRadiusMeters > 0) {
            if (geofenceCircle) {
                geofenceCircle.setLatLng([parsedLat, parsedLng]);
            } else {
                geofenceCircle = L.circle([parsedLat, parsedLng], {
                    radius: geofenceRadiusMeters,
                    color: '#2563EB',
                    fillColor: '#3B82F6',
                    fillOpacity: 0.12,
                    weight: 1.5,
                    dashArray: '5, 5'
                }).addTo(pickerMap);
            }
        }

        if (options.fly !== false) {
            pickerMap.flyTo([parsedLat, parsedLng], Math.max(pickerMap.getZoom(), 16), { duration: 0.8 });
        }

        updateExternalPreviewLinks(parsedLat, parsedLng);
        recalculateTechnicianDistances(parsedLat, parsedLng);
        redrawDispatchRouteLine();

        const autoSyncToggle = document.getElementById('toggle-auto-address');
        if (!options.skipReverseGeocode && autoSyncToggle && autoSyncToggle.checked && !addressManuallyEdited) {
            reverseGeocode(parsedLat, parsedLng);
        }
    }

    function centerOnTargetPin() {
        if (targetMarker) {
            pickerMap.flyTo(targetMarker.getLatLng(), 16, { duration: 0.7 });
            targetMarker.openPopup();
        }
    }

    function clearTargetPin() {
        if (targetMarker) { pickerMap.removeLayer(targetMarker); targetMarker = null; }
        if (geofenceCircle) { pickerMap.removeLayer(geofenceCircle); geofenceCircle = null; }
        if (dispatchRouteLine) { pickerMap.removeLayer(dispatchRouteLine); dispatchRouteLine = null; }
        document.getElementById('latitude-input').value = '';
        document.getElementById('longitude-input').value = '';
        updateExternalPreviewLinks(null, null);
        recalculateTechnicianDistances(null, null);
    }

    // ─── 4. REVERSE & FORWARD GEOCODING (OPENSTREETMAP NOMINATIM) ──────────────
    async function reverseGeocode(lat, lng) {
        const statusEl = document.getElementById('address-sync-status');
        if (statusEl) statusEl.innerHTML = '<span class="text-blue-600 font-semibold">Resolving street address from map coordinates...</span>';
        try {
            const resp = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!resp.ok) throw new Error('Geocoder unavailable');
            const data = await resp.json();
            if (data && data.display_name) {
                document.getElementById('address-input').value = data.display_name;
                if (statusEl) statusEl.innerHTML = '<span class="text-emerald-600 font-semibold">Street address synced from map pin. You can append floor/unit details freely.</span>';
            }
        } catch (err) {
            if (statusEl) statusEl.innerHTML = '<span class="text-amber-600">Coordinates pinned. Enter street address manually if needed.</span>';
        }
    }

    function manualReverseGeocode() {
        const lat = parseFloat(document.getElementById('latitude-input').value);
        const lng = parseFloat(document.getElementById('longitude-input').value);
        if (isNaN(lat) || isNaN(lng)) {
            alert('Please click on the map to drop a pin first.');
            return;
        }
        addressManuallyEdited = false;
        document.getElementById('toggle-auto-address').checked = true;
        reverseGeocode(lat, lng);
    }

    async function executeAddressSearch() {
        const query = document.getElementById('map-address-search').value.trim();
        const resultsBox = document.getElementById('map-search-results');
        const spinner = document.getElementById('map-search-spinner');

        if (query.length < 3) {
            resultsBox.classList.add('hidden');
            return;
        }

        if (spinner) spinner.classList.remove('hidden');
        try {
            const resp = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&q=${encodeURIComponent(query)}&accept-language=en`, {
                headers: { 'Accept': 'application/json' }
            });
            const places = await resp.json();
            resultsBox.innerHTML = '';

            if (!Array.isArray(places) || places.length === 0) {
                resultsBox.innerHTML = '<div class="p-3 text-xs text-slate-500 text-center">No matching places found. Try a broader street or city name.</div>';
                resultsBox.classList.remove('hidden');
                return;
            }

            places.forEach(place => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'w-full text-left px-3.5 py-2.5 hover:bg-blue-50 transition-colors flex items-start gap-2';
                item.innerHTML = `
                    <ion-icon name="location-outline" class="text-blue-600 mt-0.5 flex-shrink-0"></ion-icon>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-800 truncate">${escapeHtml(place.display_name.split(',')[0])}</div>
                        <div class="text-[10px] text-slate-500 truncate">${escapeHtml(place.display_name)}</div>
                    </div>
                `;
                item.onclick = function() {
                    const lat = parseFloat(place.lat);
                    const lng = parseFloat(place.lon);
                    resultsBox.classList.add('hidden');
                    document.getElementById('map-address-search').value = place.display_name;
                    const autoSync = document.getElementById('toggle-auto-address');
                    if (autoSync && autoSync.checked && !addressManuallyEdited) {
                        document.getElementById('address-input').value = place.display_name;
                    }
                    setTargetLocation(lat, lng, { fly: true, skipReverseGeocode: true });
                };
                resultsBox.appendChild(item);
            });
            resultsBox.classList.remove('hidden');
        } catch (err) {
            resultsBox.innerHTML = '<div class="p-3 text-xs text-red-500 text-center">Address search temporarily unavailable. Click the map directly to place a pin.</div>';
            resultsBox.classList.remove('hidden');
        } finally {
            if (spinner) spinner.classList.add('hidden');
        }
    }

    // ─── 5. GOOGLE MAPS URL & COORDINATE PARSER + MY GPS ──────────────────────
    function parsePastedMapLink() {
        const raw = (document.getElementById('gmaps-paste-input').value || '').trim();
        const fb = document.getElementById('gmaps-paste-feedback');
        if (!raw) return;

        let lat = null, lng = null;

        // Pattern 1: Highest-precision Google Maps exact pin !3dLAT!4dLNG
        let m = raw.match(/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/);
        if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }

        // Pattern 2: Google Maps @LAT,LNG
        if (lat === null) {
            m = raw.match(/@(-?\d+\.\d+),(-?\d+\.\d+)/);
            if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        }

        // Pattern 3: Query params ?q=LAT,LNG / ll=LAT,LNG / query=LAT,LNG / destination=LAT,LNG
        if (lat === null) {
            m = raw.match(/[?&](?:q|ll|query|destination)=(-?\d+\.\d+)[,%2C+\s]+(-?\d+\.\d+)/i);
            if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        }

        // Pattern 4: Raw decimal coordinate pair (-6.2088, 106.8456)
        if (lat === null) {
            m = raw.match(/^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/);
            if (m) { lat = parseFloat(m[1]); lng = parseFloat(m[2]); }
        }

        if (lat !== null && lng !== null && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            setTargetLocation(lat, lng, { fly: true });
            fb.className = 'text-[10px] mt-1 font-semibold text-emerald-700 block';
            fb.textContent = `Pinned accurately at ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        } else if (/maps\.app\.goo\.gl|goo\.gl\/maps/i.test(raw)) {
            fb.className = 'text-[10px] mt-1 font-semibold text-amber-700 block';
            fb.textContent = 'Shortened Google Maps links hide coordinates. Open the link first and paste the full URL (containing @lat,lng) or search the address above.';
        } else {
            fb.className = 'text-[10px] mt-1 font-semibold text-red-600 block';
            fb.textContent = 'Could not extract coordinates. Example format: -6.2088, 106.8456 or a full Google Maps URL.';
        }
    }

    function useMyBrowserLocation() {
        const fb = document.getElementById('gmaps-paste-feedback');
        if (!navigator.geolocation) {
            fb.className = 'text-[10px] mt-1 font-semibold text-red-600 block';
            fb.textContent = 'Geolocation is not supported by this browser.';
            return;
        }
        fb.className = 'text-[10px] mt-1 font-semibold text-blue-700 block';
        fb.textContent = 'Acquiring your current GPS coordinates...';

        navigator.geolocation.getCurrentPosition(
            pos => {
                setTargetLocation(pos.coords.latitude, pos.coords.longitude, { fly: true });
                fb.className = 'text-[10px] mt-1 font-semibold text-emerald-700 block';
                fb.textContent = `Locked to your GPS (±${Math.round(pos.coords.accuracy)}m accuracy)`;
            },
            err => {
                fb.className = 'text-[10px] mt-1 font-semibold text-red-600 block';
                fb.textContent = 'GPS permission denied or unavailable. Click the map to place a pin.';
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // ─── 6. LIVE TECHNICIAN DISTANCE & VISUAL SELECTION CARDS ─────────────────
    function recalculateTechnicianDistances(targetLat, targetLng) {
        let bestTechId = null;
        let bestScore = Infinity;

        technicianFleet.forEach(tech => {
            const distBadge = document.getElementById(`tech-dist-badge-${tech.id}`);
            const recBadge = document.getElementById(`tech-rec-badge-${tech.id}`);
            if (recBadge) recBadge.classList.add('hidden');

            if (targetLat !== null && targetLng !== null && tech.latitude !== null && tech.longitude !== null) {
                const distM = haversineMeters(targetLat, targetLng, tech.latitude, tech.longitude);
                const distKm = distM / 1000;
                const estMins = Math.max(2, Math.round((distKm / 30) * 60));
                const label = distM < 1000 ? `${Math.round(distM)}m • ~${estMins}m` : `${distKm.toFixed(1)} km • ~${estMins}m`;

                if (distBadge) {
                    distBadge.textContent = label;
                    distBadge.className = 'text-[10px] font-mono font-bold px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 flex-shrink-0';
                }

                // Match backend findRecommendedTechnician score (+ online bonus)
                const score = distM + (tech.active_tasks_count * 2000) + (tech.is_online ? 0 : 15000);
                if (score < bestScore) {
                    bestScore = score;
                    bestTechId = tech.id;
                }
            } else {
                if (distBadge) {
                    distBadge.textContent = tech.latitude !== null ? 'Pin to measure' : 'No GPS';
                    distBadge.className = 'text-[10px] font-mono font-semibold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-500 flex-shrink-0';
                }
                if (bestTechId === null) {
                    const fallbackScore = 100000 + (tech.active_tasks_count * 2000);
                    if (fallbackScore < bestScore) {
                        bestScore = fallbackScore;
                        bestTechId = tech.id;
                    }
                }
            }
        });

        currentRecommendedTechId = bestTechId;
        if (bestTechId !== null) {
            const recBadge = document.getElementById(`tech-rec-badge-${bestTechId}`);
            if (recBadge) recBadge.classList.remove('hidden');
        }
    }

    function selectTechnicianCard(techIdStr) {
        const select = document.getElementById('technician_id_select');
        if (select) select.value = techIdStr;

        document.querySelectorAll('.tech-card').forEach(el => el.classList.remove('is-selected'));
        const activeCard = techIdStr === ''
            ? document.getElementById('tech-card-unassigned')
            : document.getElementById(`tech-card-${techIdStr}`);
        if (activeCard) activeCard.classList.add('is-selected');

        redrawDispatchRouteLine();
    }

    function selectRecommendedTechnician() {
        if (currentRecommendedTechId !== null) {
            selectTechnicianCard(String(currentRecommendedTechId));
            const tech = technicianFleet.find(t => t.id === currentRecommendedTechId);
            if (tech && tech.latitude !== null && tech.longitude !== null && targetMarker) {
                const bounds = L.latLngBounds([targetMarker.getLatLng(), [tech.latitude, tech.longitude]]);
                pickerMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        } else if (technicianFleet.length > 0) {
            const leastBusy = [...technicianFleet].sort((a, b) => a.active_tasks_count - b.active_tasks_count)[0];
            selectTechnicianCard(String(leastBusy.id));
        }
    }

    function redrawDispatchRouteLine() {
        if (dispatchRouteLine) {
            pickerMap.removeLayer(dispatchRouteLine);
            dispatchRouteLine = null;
        }
        const selectedId = document.getElementById('technician_id_select')?.value;
        if (!selectedId || !targetMarker) return;

        const tech = technicianFleet.find(t => String(t.id) === String(selectedId));
        if (!tech || tech.latitude === null || tech.longitude === null) return;

        const dest = targetMarker.getLatLng();
        dispatchRouteLine = L.polyline([
            [tech.latitude, tech.longitude],
            [dest.lat, dest.lng]
        ], {
            color: '#2563EB',
            weight: 3,
            dashArray: '6, 8',
            opacity: 0.85
        }).addTo(pickerMap);
    }

    // ─── INITIALIZE MAP & BIND INPUT EVENTS ───────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        const initLat = parseFloat(document.getElementById('latitude-input').value);
        const initLng = parseFloat(document.getElementById('longitude-input').value);
        const hasInitCoords = !isNaN(initLat) && !isNaN(initLng);

        // Determine initial center: existing task pin -> first online technician -> Jakarta default
        let startCenter = [-6.2088, 106.8456];
        let startZoom = 12;
        if (hasInitCoords) {
            startCenter = [initLat, initLng];
            startZoom = 16;
        } else {
            const firstWithGps = technicianFleet.find(t => t.latitude !== null && t.longitude !== null);
            if (firstWithGps) {
                startCenter = [firstWithGps.latitude, firstWithGps.longitude];
                startZoom = 13;
            }
        }

        pickerMap = L.map('location-picker-map', { zoomControl: true, scrollWheelZoom: true }).setView(startCenter, startZoom);

        const initialLayerKey = defaultMapStyle === 'carto' ? 'light' : (defaultMapStyle === 'mapbox' ? 'satellite' : 'street');
        switchMapLayer(initialLayerKey);

        // Plot live field technicians on the picker map
        technicianFleet.forEach(tech => {
            if (tech.latitude === null || tech.longitude === null) return;
            const marker = L.marker([tech.latitude, tech.longitude], {
                icon: tech.is_online ? blueTechIcon : greyTechIcon
            }).addTo(pickerMap);
            marker.bindPopup(`
                <div style="font-size:11px;min-width:160px;">
                    <b>${escapeHtml(tech.name)}</b><br>
                    <span style="color:${tech.is_online ? '#059669' : '#64748B'};font-weight:700;">${tech.is_online ? '● Online' : 'Offline'}</span> • ${tech.active_tasks_count} active tasks<br>
                    <span style="color:#64748B;font-size:10px;">Last GPS: ${escapeHtml(tech.last_seen)}</span><br>
                    <button type="button" onclick="selectTechnicianCard('${tech.id}')" style="margin-top:6px;width:100%;padding:4px 8px;background:#2563EB;color:#fff;border:none;border-radius:6px;font-size:10px;font-weight:700;cursor:pointer;">Assign ${escapeHtml(tech.name)}</button>
                </div>
            `);
            techMapMarkers[tech.id] = marker;
        });

        // Map click places/moves the red target pin
        pickerMap.on('click', function(e) {
            setTargetLocation(e.latlng.lat, e.latlng.lng, { fly: false });
        });

        if (hasInitCoords) {
            setTargetLocation(initLat, initLng, { fly: false, skipReverseGeocode: true });
        } else {
            recalculateTechnicianDistances(null, null);
        }

        // Sync manual latitude/longitude edits back to map
        const syncManualCoords = () => {
            const lat = parseFloat(document.getElementById('latitude-input').value);
            const lng = parseFloat(document.getElementById('longitude-input').value);
            if (!isNaN(lat) && !isNaN(lng)) {
                setTargetLocation(lat, lng, { fly: true, skipReverseGeocode: true });
            }
        };
        document.getElementById('latitude-input').addEventListener('change', syncManualCoords);
        document.getElementById('longitude-input').addEventListener('change', syncManualCoords);

        // Lock address auto-sync if dispatcher manually edits the address textarea
        document.getElementById('address-input').addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                addressManuallyEdited = true;
            }
        });
        document.getElementById('toggle-auto-address').addEventListener('change', function() {
            if (this.checked) {
                addressManuallyEdited = false;
            }
        });

        // Debounced live address search input
        const searchInput = document.getElementById('map-address-search');
        searchInput.addEventListener('input', function() {
            clearTimeout(searchDebounceTimer);
            if (this.value.trim().length < 3) {
                document.getElementById('map-search-results').classList.add('hidden');
                return;
            }
            searchDebounceTimer = setTimeout(executeAddressSearch, 450);
        });
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchDebounceTimer);
                executeAddressSearch();
            }
        });

        // Allow pressing Enter inside Google Maps link paste input
        const pasteInput = document.getElementById('gmaps-paste-input');
        pasteInput.addEventListener('paste', () => setTimeout(parsePastedMapLink, 60));
        pasteInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                parsePastedMapLink();
            }
        });

        // Close search dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const results = document.getElementById('map-search-results');
            if (results && !results.contains(e.target) && e.target !== searchInput) {
                results.classList.add('hidden');
            }
        });
    });
</script>
@endpush
@endsection
