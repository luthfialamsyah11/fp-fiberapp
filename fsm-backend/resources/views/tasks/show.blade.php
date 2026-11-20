@extends('layouts.admin')

@section('title', 'Task Details #TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT))
@section('header', 'Work Order Command Details')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    #task-map { height: 360px; border-radius: 12px; z-index: 1; border: 1px solid #E2E8F0; }
    .proof-thumb { cursor: zoom-in; transition: transform 0.25s ease; }
    .proof-thumb:hover { transform: scale(1.03); }
</style>
@endpush

@section('content')
@php
    $steps = ['pending', 'assigned', 'accepted', 'on-going', 'completed'];
    $stepLabels = ['Pending', 'Assigned', 'Accepted', 'In Progress', 'Completed'];
    $currentStepIndex = array_search($task->status, $steps, true);
    if ($currentStepIndex === false) {
        $currentStepIndex = -1;
    }
    $statusColors = [
        'pending'   => 'bg-slate-100 text-slate-700 border-slate-200',
        'assigned'  => 'bg-blue-50 text-blue-700 border-blue-200',
        'accepted'  => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'on-going'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected'  => 'bg-red-50 text-red-700 border-red-200',
    ];
    $latestProgress = (int) $task->progress_percent;
@endphp

<!-- Top Navigation & Action Toolbar -->
<div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
    <div>
        <a href="{{ route('admin.tasks.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 inline-flex items-center gap-1.5">
            <ion-icon name="arrow-back-outline"></ion-icon> Back to Task Management
        </a>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if($task->status === 'completed')
            <button type="button" id="btn-verify-seal" onclick="verifyBastSeal()" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-semibold transition-all inline-flex items-center gap-1.5 shadow-sm">
                <ion-icon name="shield-checkmark-outline"></ion-icon>
                <span>Verify SHA-256 Seal</span>
            </button>
        @endif

        @if(in_array($task->status, ['completed', 'on-going'], true))
            <button type="button" onclick="document.getElementById('rework-modal').classList.remove('hidden')" class="bg-amber-50 border border-amber-200 text-amber-800 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-amber-100 transition-all inline-flex items-center gap-1.5 shadow-sm">
                <ion-icon name="refresh-circle-outline"></ion-icon>
                <span>Request Rework</span>
            </button>
        @endif

        <a href="{{ route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->tracking_token]) }}" target="_blank" class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-emerald-100 transition-all inline-flex items-center gap-1.5 shadow-sm">
            <ion-icon name="print-outline"></ion-icon> Print Official BAST (PDF)
        </a>

        <a href="{{ route('admin.tasks.edit', $task->id) }}" class="bg-indigo-50 border border-indigo-200 text-indigo-700 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-indigo-100 transition-all inline-flex items-center gap-1.5">
            <ion-icon name="create-outline"></ion-icon> Edit Task
        </a>
    </div>
</div>

<!-- Flash & Inline Seal Verification Alerts -->
@if(session('success'))
    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
        <ion-icon name="checkmark-circle" class="text-base text-emerald-600 flex-shrink-0"></ion-icon>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
        <ion-icon name="alert-circle" class="text-base text-red-600 flex-shrink-0"></ion-icon>
        <span>{{ session('error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs shadow-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif
<div id="bast-seal-banner" class="hidden mb-5 p-3.5 rounded-xl border text-xs font-semibold flex items-center justify-between gap-3 shadow-sm"></div>

<!-- 5-Step Lifecycle Stepper -->
<div class="bg-white rounded-xl border border-fsmBorder p-4 sm:p-5 shadow-sm mb-6">
    <div class="flex items-center justify-between gap-2 overflow-x-auto">
        @foreach($steps as $idx => $stepKey)
            @php
                $isDone = $currentStepIndex > $idx || ($currentStepIndex === $idx && $stepKey === 'completed');
                $isCurrent = $currentStepIndex === $idx && $stepKey !== 'completed';
            @endphp
            <div class="flex items-center gap-2 flex-1 min-w-[95px]">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                    {{ $isDone ? 'bg-emerald-600 text-white' : ($isCurrent ? 'bg-primary text-white ring-4 ring-blue-100' : 'bg-slate-100 text-slate-400 border border-slate-200') }}">
                    @if($isDone)
                        <ion-icon name="checkmark-outline"></ion-icon>
                    @else
                        {{ $idx + 1 }}
                    @endif
                </div>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold {{ $isDone ? 'text-emerald-700' : ($isCurrent ? 'text-primary' : 'text-slate-400') }} truncate">
                        {{ $stepLabels[$idx] }}
                    </div>
                    <div class="text-[9px] text-slate-400 truncate">
                        @if($isCurrent) Active Stage @elseif($isDone) Completed @else Upcoming @endif
                    </div>
                </div>
                @if(!$loop->last)
                    <div class="hidden sm:block flex-1 h-0.5 {{ $currentStepIndex > $idx ? 'bg-emerald-500' : 'bg-slate-200' }} mx-1"></div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <!-- Left 2 Columns: Metadata, Timeline, Proofs, BOM, Signature & Audit History -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Task Header & Meta -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-bold text-textPrimary leading-snug">{{ $task->title }}</h2>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="text-[10px] font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded uppercase">#TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }}</span>
                        @if(($task->source ?? 'admin') === 'client_portal')
                            <span class="text-[10px] font-bold text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded inline-flex items-center gap-1">
                                <ion-icon name="globe-outline"></ion-icon> Client Portal
                            </span>
                            @if($task->service_category)
                                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded">
                                    {{ $task->service_category_label }}
                                </span>
                            @endif
                        @endif
                        <span class="text-xs text-textSecondary flex items-center gap-1">
                            <ion-icon name="calendar-outline"></ion-icon> Created {{ $task->created_at->format('d M Y H:i') }}
                        </span>
                        @if($task->scheduled_at)
                            <span class="text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded font-semibold flex items-center gap-1">
                                <ion-icon name="alarm-outline"></ion-icon> Due {{ $task->scheduled_at->format('d M Y H:i') }}
                            </span>
                        @endif
                        @if($task->isSlaBreached())
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-200">SLA Overdue</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $task->priority === 'high' ? 'bg-red-50 text-red-700 border border-red-200' : ($task->priority === 'medium' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200') }}">
                        {{ $task->priority }} Priority
                    </span>
                    <span id="live-status-pill" class="px-3.5 py-1 rounded-full text-xs font-bold border {{ $statusColors[$task->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $task->status_label }}
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6">
                <h4 class="text-xs font-semibold text-textSecondary uppercase tracking-wider mb-1.5">Description &amp; Field Instructions</h4>
                <p class="text-xs text-textPrimary whitespace-pre-line leading-relaxed">{{ $task->description ?: 'No specific description provided.' }}</p>
            </div>

            @if($task->client_photo_url)
            <div class="bg-sky-50/50 border border-sky-200/80 rounded-xl p-4 mb-6">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <h4 class="text-xs font-bold text-sky-800 uppercase tracking-wider flex items-center gap-1.5">
                        <ion-icon name="image-outline" class="text-sm"></ion-icon>
                        Client Attached Issue Photo
                    </h4>
                    <a href="{{ $task->client_photo_url }}" target="_blank" rel="noopener" class="text-[11px] font-bold text-sky-700 hover:underline inline-flex items-center gap-1">
                        Open Full Image <ion-icon name="open-outline"></ion-icon>
                    </a>
                </div>
                <a href="{{ $task->client_photo_url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg border border-sky-200 bg-white">
                    <img src="{{ $task->client_photo_url }}" alt="Client attached issue photo" class="w-full max-h-64 object-contain bg-slate-900/5">
                </a>
            </div>
            @endif

            @if($task->customer_rating !== null)
            <div class="bg-amber-50/70 border border-amber-200 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h4 class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                        <ion-icon name="star" class="text-amber-500 text-sm"></ion-icon>
                        Customer CSAT Service Rating
                    </h4>
                    <p class="text-xs text-amber-800 mt-1">
                        {{ $task->customer_feedback ?: 'Customer submitted rating without additional comments.' }}
                    </p>
                </div>
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-amber-200 text-amber-600 font-extrabold text-sm flex-shrink-0 tabular-nums">
                    <span>{{ str_repeat('★', (int) $task->customer_rating) }}{{ str_repeat('☆', 5 - (int) $task->customer_rating) }}</span>
                    <span class="text-xs text-amber-900">{{ $task->customer_rating }}/5</span>
                </div>
            </div>
            @endif

            <!-- Customer & Location Specs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Customer Info</h4>
                    <div class="space-y-1.5">
                        <div class="font-bold text-textPrimary text-sm flex items-center gap-1.5">
                            <ion-icon name="person-outline" class="text-slate-400"></ion-icon>
                            {{ $task->customer_name }}
                        </div>
                        @if($task->customer_phone)
                            <div class="text-xs text-textSecondary flex items-center gap-1.5">
                                <ion-icon name="call-outline" class="text-slate-400"></ion-icon>
                                <a href="tel:{{ $task->customer_phone }}" class="hover:text-primary transition-all">{{ $task->customer_phone }}</a>
                            </div>
                        @endif
                        @if($task->customer_email)
                            <div class="text-xs text-textSecondary flex items-center gap-1.5">
                                <ion-icon name="mail-outline" class="text-slate-400"></ion-icon>
                                <a href="mailto:{{ $task->customer_email }}" class="hover:text-primary transition-all">{{ $task->customer_email }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Service Address</h4>
                    <div class="space-y-1.5 text-xs text-textPrimary leading-relaxed">
                        <p class="flex items-start gap-1.5">
                            <ion-icon name="location-outline" class="text-slate-400 mt-0.5 flex-shrink-0"></ion-icon>
                            <span>{{ $task->resolved_location }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Monitoring & Dispatcher Note Composer -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-fsmBorder">
                <h3 class="text-sm font-bold text-textPrimary">Progress &amp; Operational Timeline</h3>
                <span id="live-progress-label" class="text-xs font-semibold text-primary bg-blue-50 px-2.5 py-0.5 rounded">
                    Status: {{ $latestProgress }}% Completed
                </span>
            </div>

            <!-- Main Progress Bar -->
            <div class="mb-6">
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div id="live-progress-bar" class="bg-primary h-2 rounded-full transition-all duration-500" style="width: {{ $latestProgress }}%"></div>
                </div>
            </div>

            <!-- Inline Dispatcher Note Composer -->
            <form action="{{ route('admin.tasks.notes.store', $task->id) }}" method="POST" class="mb-6 p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl">
                @csrf
                <div class="flex flex-col sm:flex-row gap-2">
                    <input type="text" name="note" required maxlength="1000" placeholder="Post NOC dispatcher note or site instruction to timeline..."
                        class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/25">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition-all inline-flex items-center justify-center gap-1.5 flex-shrink-0">
                        <ion-icon name="send-outline"></ion-icon> Post Note
                    </button>
                </div>
            </form>

            <!-- Progress Updates Logs -->
            @if($task->progressUpdates && $task->progressUpdates->count() > 0)
                <div class="space-y-5">
                    @foreach($task->progressUpdates as $update)
                        <div class="flex gap-4 relative">
                            @if(!$loop->last)
                                <div class="absolute left-[13px] top-7 bottom-[-20px] w-0.5 bg-slate-200"></div>
                            @endif

                            <div class="h-7 w-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0 z-10">
                                <span class="text-[9px] font-bold text-primary">{{ $update->progress_percent }}%</span>
                            </div>

                            <div class="flex-1 pb-2">
                                <div class="flex justify-between items-start">
                                    <span class="text-xs font-semibold text-textPrimary">{{ $update->user->name ?? 'System' }}</span>
                                    <span class="text-[10px] text-textSecondary font-medium">{{ $update->created_at->format('d M Y, H:i') }}</span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 mt-1.5">
                                    <p class="text-xs text-textSecondary leading-relaxed">{{ $update->note }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6 text-textSecondary">
                    <ion-icon name="trending-up-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">No progress logs reported yet by the technician.</p>
                </div>
            @endif
        </div>

        <!-- Proof of Work Gallery with Click-to-Zoom Lightbox -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5 pb-2 border-b border-fsmBorder">
                <h3 class="text-sm font-bold text-textPrimary">Proof of Work Verification</h3>
                <span class="text-xs font-semibold text-slate-500">{{ $task->proofs->count() }} Photos</span>
            </div>

            @if($task->proofs && $task->proofs->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($task->proofs as $index => $proof)
                        @php
                            $resolvedSlot = $proof->resolved_slot;
                            if ($resolvedSlot === 'before') {
                                $label = 'Before Photo';
                                $labelBg = 'bg-amber-50 text-amber-700 border-amber-200';
                            } elseif ($resolvedSlot === 'after') {
                                $label = 'After Photo';
                                $labelBg = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            } else {
                                $label = $index === 0 ? 'Before Photo' : ($index === 1 ? 'After Photo' : 'Additional Photo');
                                $labelBg = $index === 0 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200';
                            }
                        @endphp
                        <div class="border border-slate-200 rounded-xl overflow-hidden flex flex-col group">
                            <div class="relative h-48 bg-slate-100 overflow-hidden"
                                onclick="openProofLightbox({{ json_encode($proof->image_url) }}, {{ json_encode($label) }}, {{ json_encode($proof->description ?: 'No comment attached.') }}, {{ json_encode($proof->created_at->format('d M Y H:i')) }})">
                                <img src="{{ $proof->image_url }}" class="w-full h-full object-cover proof-thumb" alt="{{ $label }}">
                                <span class="absolute top-3 left-3 px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $labelBg }} shadow-sm">
                                    {{ $label }}
                                </span>
                                <span class="absolute bottom-2.5 right-2.5 p-1.5 rounded-lg bg-slate-900/70 text-white opacity-0 group-hover:opacity-100 transition-opacity text-xs inline-flex items-center gap-1">
                                    <ion-icon name="scan-outline"></ion-icon> Zoom
                                </span>
                            </div>

                            <div class="p-3 bg-slate-50 border-t border-slate-100 flex-1 flex flex-col justify-between">
                                <p class="text-xs text-textPrimary leading-snug">{{ $proof->description ?: 'No comment attached.' }}</p>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-[9px] text-textSecondary font-medium">Uploaded: {{ $proof->created_at->format('d M Y H:i') }}</span>
                                    <a href="{{ $proof->image_url }}" target="_blank" download class="text-[10px] font-bold text-primary hover:underline inline-flex items-center gap-0.5">
                                        <ion-icon name="download-outline"></ion-icon> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-textSecondary">
                    <ion-icon name="images-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">No completion proof photos have been uploaded by the technician yet.</p>
                </div>
            @endif
        </div>

        <!-- Material & Equipment (BOM) Tracking Module with Admin Inline Controls -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-fsmBorder">
                <div>
                    <h3 class="text-sm font-bold text-textPrimary">Consumed Materials &amp; Hardware (BOM)</h3>
                    <p class="text-xs text-textSecondary">Network spare parts ledger &amp; automated warehouse stock deduction</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    {{ $task->materials->count() }} Items
                </span>
            </div>

            @if($task->status !== 'completed' && isset($inventoryItems) && $inventoryItems->count() > 0)
                <form action="{{ route('admin.tasks.materials.store', $task->id) }}" method="POST" class="mb-5 p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-end">
                    @csrf
                    <div class="sm:col-span-5">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Warehouse Item</label>
                        <select name="inventory_item_id" required class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800">
                            <option value="">-- Select Material / ONT --</option>
                            @foreach($inventoryItems as $inv)
                                <option value="{{ $inv->id }}">[{{ $inv->code }}] {{ $inv->name }} ({{ $inv->stock }} {{ $inv->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Qty</label>
                        <input type="number" name="quantity" step="any" min="0.01" value="1" required class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Serial / Note</label>
                        <input type="text" name="serial_number" placeholder="Optional SN" class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs text-slate-800">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="w-full py-2 px-3 rounded-lg bg-primary hover:bg-blue-700 text-white text-xs font-bold transition-all">
                            + Add
                        </button>
                    </div>
                </form>
            @elseif($task->status === 'completed')
                <div class="mb-4 p-2.5 rounded-lg bg-emerald-50/70 border border-emerald-200 text-[11px] text-emerald-800 flex items-center gap-2">
                    <ion-icon name="lock-closed-outline" class="text-sm flex-shrink-0"></ion-icon>
                    <span>BOM ledger is locked by the SHA-256 BAST seal. Use <strong>Request Rework</strong> to reopen this ticket before modifying materials.</span>
                </div>
            @endif

            @if($task->materials->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase text-[10px]">
                                <th class="pb-2">Material</th>
                                <th class="pb-2">Qty</th>
                                <th class="pb-2">Serial Number / Note</th>
                                <th class="pb-2 text-right">Est. Cost</th>
                                @if($task->status !== 'completed')
                                    <th class="pb-2 text-right">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php $totalMatCost = 0; @endphp
                            @foreach($task->materials as $mat)
                                @php
                                    $cost = ($mat->inventoryItem->unit_price ?? 0) * $mat->quantity;
                                    $totalMatCost += $cost;
                                @endphp
                                <tr>
                                    <td class="py-2.5">
                                        <div class="font-bold text-textPrimary">{{ $mat->inventoryItem->name ?? 'Material' }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $mat->inventoryItem->code ?? '-' }}</div>
                                    </td>
                                    <td class="py-2.5 font-semibold text-textPrimary">
                                        {{ $mat->quantity }} {{ $mat->inventoryItem->unit ?? 'pcs' }}
                                    </td>
                                    <td class="py-2.5 text-textSecondary">
                                        {{ $mat->serial_number ?: ($mat->notes ?: '-') }}
                                    </td>
                                    <td class="py-2.5 text-right font-semibold text-emerald-600">
                                        {{ \App\Services\SettingService::formatCurrency((float) $cost) }}
                                    </td>
                                    @if($task->status !== 'completed')
                                        <td class="py-2.5 text-right">
                                            <button type="button"
                                                onclick="openRemoveBomModal(this)"
                                                data-remove-url="{{ route('admin.tasks.materials.destroy', ['task' => $task->id, 'material' => $mat->id]) }}"
                                                data-item-name="{{ $mat->inventoryItem->name ?? 'Material' }} ({{ $mat->quantity }} {{ $mat->inventoryItem->unit ?? 'pcs' }})"
                                                class="w-7 h-7 inline-flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition-all"
                                                title="Remove & Restore Stock">
                                                <ion-icon name="trash-outline" class="pointer-events-none"></ion-icon>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200 font-bold">
                                <td colspan="3" class="pt-2 text-textPrimary uppercase text-[11px]">Total Estimated Material Cost</td>
                                <td class="pt-2 text-right text-emerald-700 font-bold">
                                    {{ \App\Services\SettingService::formatCurrency((float) $totalMatCost) }}
                                </td>
                                @if($task->status !== 'completed')
                                    <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-textSecondary">
                    <ion-icon name="cube-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">No materials have been logged for this work order.</p>
                </div>
            @endif
        </div>

        <!-- Customer Digital Signature & Acceptance Module -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-fsmBorder">
                <div>
                    <h3 class="text-sm font-bold text-textPrimary">Customer Electronic Signature (Sign-on-Glass)</h3>
                    <p class="text-xs text-textSecondary">Cryptographic digital acceptance proof (E-Signature)</p>
                </div>
                @if($task->customer_signature)
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Customer Approved
                    </span>
                @else
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-200">
                        Pending Signature
                    </span>
                @endif
            </div>

            @if($task->customer_signature)
                <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-slate-50 border border-slate-100 rounded-xl">
                    <div class="w-48 h-24 bg-white border border-slate-200 rounded-lg p-2 flex items-center justify-center shadow-inner">
                        <img src="{{ $task->customer_signature_url }}" alt="Customer Signature" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="font-bold text-textPrimary text-sm">{{ $task->customer_signer_name ?? $task->customer_name }}</div>
                        <div class="text-textSecondary flex items-center gap-1">
                            <ion-icon name="time-outline" class="text-slate-400"></ion-icon>
                            Signed at: {{ $task->customer_signed_at ? $task->customer_signed_at->format('d M Y H:i') : '-' }} UTC
                        </div>
                        @if($task->bast_hash)
                            <div class="text-[10px] font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded w-fit">
                                SHA-256: {{ substr($task->bast_hash, 0, 18) }}&hellip;
                            </div>
                        @endif
                        <div class="pt-2">
                            <a href="{{ route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->tracking_token]) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                <ion-icon name="document-text-outline"></ion-icon> View Official BAST Certificate
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-6 text-textSecondary bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                    <ion-icon name="create-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs">Customer has not yet signed the acceptance certificate on technician terminal.</p>
                </div>
            @endif
        </div>

        <!-- Status & Immutable Audit History Ledger -->
        <div class="bg-white rounded-xl border border-fsmBorder p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-fsmBorder">
                <div>
                    <h3 class="text-sm font-bold text-textPrimary">State Transition &amp; Audit History</h3>
                    <p class="text-xs text-textSecondary">Deterministic finite state machine transitions and override audit trail</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    {{ $task->statusHistories ? $task->statusHistories->count() : 0 }} Events
                </span>
            </div>

            @if($task->statusHistories && $task->statusHistories->count() > 0)
                <div class="space-y-2.5">
                    @foreach($task->statusHistories as $history)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-slate-50 border border-slate-100 rounded-xl text-xs">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded bg-slate-200/70 text-slate-700 font-mono text-[10px] uppercase font-bold">{{ $history->from_status }}</span>
                                <ion-icon name="arrow-forward-outline" class="text-slate-400"></ion-icon>
                                <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-mono text-[10px] uppercase font-bold">{{ $history->to_status }}</span>
                                <span class="text-textSecondary">by <strong class="text-textPrimary">{{ $history->actor->name ?? ucfirst($history->actor_role) }}</strong> ({{ strtoupper($history->actor_role) }})</span>
                                @if($history->reason)
                                    <span class="text-slate-600 italic">— "{{ $history->reason }}"</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">{{ $history->created_at->format('d M Y H:i:s') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5 text-textSecondary">
                    <p class="text-xs">No state transitions recorded yet.</p>
                </div>
            @endif
        </div>

    </div>

    <!-- Right 1 Column: Customer Tracking, Inline Assign/Reassign & Live Auto-Polling Map -->
    <div class="space-y-6">

        <!-- Customer Live Tracking Link Widget -->
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-xl border border-cyan-500/30 p-5 shadow-sm text-white">
            <div class="flex items-center gap-2 mb-3">
                <div class="h-7 w-7 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-base border border-cyan-500/30">
                    <ion-icon name="navigate-circle-outline"></ion-icon>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-400">Customer Live Tracking</h3>
                    <p class="text-[10px] text-slate-400">Uber-style live tracking URL for customer</p>
                </div>
            </div>

            <div class="bg-slate-950/60 border border-slate-700/60 rounded-lg p-2.5 mb-3 flex items-center justify-between gap-2">
                <input type="text" readonly value="{{ $task->tracking_url }}" id="customer-tracking-url" class="bg-transparent text-[11px] text-slate-300 font-mono w-full outline-none truncate">
                <button type="button" onclick="copyTrackingUrl()" class="p-1.5 rounded-md bg-cyan-500/20 text-cyan-400 hover:bg-cyan-500/30 transition-all flex-shrink-0" title="Copy URL">
                    <ion-icon name="copy-outline" id="copy-icon"></ion-icon>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ $task->tracking_url }}" target="_blank" class="flex-1 py-1.5 px-3 rounded-lg bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-1 transition-all">
                    <ion-icon name="open-outline"></ion-icon> Preview Tracking
                </a>
                @if($task->customer_phone)
                    @php
                        $defaultCountryCode = preg_replace('/[^0-9]/', '', (string) \App\Services\SettingService::get('default_country_code', '62')) ?: '62';
                        $cleanPhone = preg_replace('/^0/', $defaultCountryCode, preg_replace('/[^0-9]/', '', $task->customer_phone));
                        $waMsg = "Hello " . $task->customer_name . ", our certified field technician is en route to your location for work order #TSK-" . $task->id . ". You can track their live location here: " . route('customer.tracking', $task->tracking_token);
                    @endphp
                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener noreferrer" class="py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-1 transition-all" title="Share via WhatsApp">
                        <ion-icon name="logo-whatsapp"></ion-icon> Share
                    </a>
                @endif
            </div>
        </div>

        <!-- Inline Technician Assignment / Reassignment Module -->
        <div class="bg-white rounded-xl border border-fsmBorder p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-fsmBorder pb-1.5">Assigned Field Technician</h3>

            @if($task->technician)
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($task->technician->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden min-w-0">
                        <p class="text-xs font-bold text-textPrimary truncate">{{ $task->technician->name }}</p>
                        <p class="text-[10px] text-textSecondary truncate">{{ $task->technician->email }}</p>
                        <p class="text-[10px] text-textSecondary font-medium truncate mt-0.5">{{ $task->technician->phone ?: 'No phone' }}</p>
                    </div>
                </div>
            @else
                <div class="text-center py-3 mb-3 bg-amber-50/50 rounded-xl border border-amber-200/70">
                    <p class="text-xs font-bold text-amber-800">Unassigned — Pending Dispatch</p>
                    <p class="text-[10px] text-amber-700 mt-0.5">Select an active field technician below</p>
                </div>
            @endif

            @if($task->status !== 'completed' && isset($technicians) && $technicians->count() > 0)
                <form action="{{ route('admin.tasks.assign', $task->id) }}" method="POST" class="space-y-2.5 pt-3 border-t border-slate-100">
                    @csrf
                    <label class="block text-[10px] font-bold uppercase text-slate-500">
                        {{ $task->technician ? 'Reassign / Dispatch Technician' : 'Assign Technician Now' }}
                    </label>
                    <select name="technician_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary/25">
                        <option value="">-- Choose Technician --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" {{ $task->technician_id == $tech->id ? 'selected' : '' }}>
                                {{ $tech->name }} ({{ $tech->active_tasks_count }} active)
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="w-full bg-primary hover:bg-blue-700 text-white py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        {{ $task->technician ? 'Update Dispatch Assignee' : 'Dispatch Technician' }}
                    </button>
                </form>
            @endif
        </div>

        <!-- Live Auto-Polling Location Monitoring Map -->
        <div class="bg-white rounded-xl border border-fsmBorder overflow-hidden shadow-sm flex flex-col">
            <div class="px-5 py-3 border-b border-fsmBorder bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-textPrimary uppercase tracking-wider">Live Location Monitoring</h3>
                    <span id="live-distance-eta" class="text-[10px] text-blue-600 font-semibold"></span>
                </div>
                @if($task->latitude && $task->longitude)
                    <div class="flex items-center gap-1.5">
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $task->latitude }},{{ $task->longitude }}" target="_blank" rel="noopener noreferrer" class="px-2 py-1 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-[10px] font-bold transition-all flex items-center gap-1" title="Open route in Google Maps">
                            <ion-icon name="navigate-outline"></ion-icon> Maps
                        </a>
                        <a href="https://waze.com/ul?ll={{ $task->latitude }},{{ $task->longitude }}&navigate=yes" target="_blank" rel="noopener noreferrer" class="px-2 py-1 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 text-[10px] font-bold transition-all flex items-center gap-1" title="Open route in Waze">
                            <ion-icon name="car-sport-outline"></ion-icon> Waze
                        </a>
                    </div>
                @endif
            </div>

            @if($task->latitude && $task->longitude)
                <div id="task-map"></div>
                <div class="p-3 bg-slate-50 border-t border-fsmBorder text-[10px] text-textSecondary font-medium flex justify-between items-center gap-2">
                    <span class="font-mono">Pin: {{ number_format($task->latitude, 5) }}, {{ number_format($task->longitude, 5) }}</span>
                    <span id="live-tech-presence">
                        @if($task->technician && $task->technician->isEffectivelyOnline())
                            <span class="text-emerald-600 flex items-center gap-1 font-semibold">
                                <span class="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-ping"></span> Tech Online
                            </span>
                        @elseif($task->technician && $task->technician->latestLocation)
                            <span class="text-slate-400">Last ping {{ ($task->technician->latestLocation->captured_at ?? $task->technician->latestLocation->created_at)->diffForHumans() }}</span>
                        @else
                            <span class="text-slate-400">No GPS telemetry</span>
                        @endif
                    </span>
                </div>
            @else
                <div class="p-8 text-center text-textSecondary bg-slate-50/30">
                    <ion-icon name="map-outline" class="text-3xl mb-1 text-slate-300"></ion-icon>
                    <p class="text-xs font-semibold">Location coordinates are missing.</p>
                    <a href="{{ route('admin.tasks.edit', $task->id) }}" class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-primary hover:underline">
                        Pin Location on Map &rarr;
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Proof Lightbox Modal -->
<div id="proof-lightbox" class="fixed inset-0 z-50 hidden bg-slate-950/85 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-4xl w-full overflow-hidden shadow-2xl border border-slate-700/30">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span id="lb-slot" class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">Photo</span>
                <span id="lb-time" class="text-xs text-slate-500 font-mono">-</span>
            </div>
            <div class="flex items-center gap-2">
                <a id="lb-download" href="#" target="_blank" download class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:text-primary inline-flex items-center gap-1">
                    <ion-icon name="download-outline"></ion-icon> Download
                </a>
                <button type="button" onclick="closeProofLightbox()" class="p-1.5 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-red-600">
                    <ion-icon name="close-outline" class="text-lg"></ion-icon>
                </button>
            </div>
        </div>
        <div class="bg-slate-950 flex items-center justify-center p-4">
            <img id="lb-img" src="" alt="Proof High Resolution" class="max-h-[68vh] max-w-full object-contain rounded-lg">
        </div>
        <div class="px-5 py-3.5 bg-white border-t border-slate-100 text-xs text-slate-700" id="lb-desc">-</div>
    </div>
</div>

<!-- Request Rework Modal -->
<div id="rework-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-textPrimary">Request Field Rework</h3>
            <button type="button" onclick="document.getElementById('rework-modal').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700">
                <ion-icon name="close-outline" class="text-xl"></ion-icon>
            </button>
        </div>
        <p class="text-xs text-textSecondary mb-4 leading-relaxed">
            Reopening this work order returns it to <strong>In Progress</strong>, clears the existing SHA-256 BAST seal, and notifies the field technician to resubmit verification evidence.
        </p>
        <form action="{{ route('admin.tasks.rework', $task->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Audit Rework Reason *</label>
                <textarea name="reason" required rows="3" maxlength="500" placeholder="e.g. After photo does not clearly display OPM dBm reading or serial number mismatch..."
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/30"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('rework-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm">Confirm Rework</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
@if($task->latitude && $task->longitude)
@php
    $taskMapMeta = [
        'id'            => $task->id,
        'customer_name' => $task->customer_name,
        'address'       => $task->resolved_location,
        'latitude'      => (float) $task->latitude,
        'longitude'     => (float) $task->longitude,
        'status'        => $task->status,
        'tech_name'     => $task->technician?->name,
        'tech_lat'      => $task->technician?->latestLocation?->latitude ? (float) $task->technician->latestLocation->latitude : null,
        'tech_lng'      => $task->technician?->latestLocation?->longitude ? (float) $task->technician->latestLocation->longitude : null,
        'tech_updated'  => $task->technician?->latestLocation?->created_at?->diffForHumans(),
    ];
@endphp
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const taskMeta = @json($taskMapMeta);
        const mapStyle = @json($mapStyle ?? 'openstreetmap');
        const geofenceRadius = @json($geofenceRadius ?? 100);
        const pollIntervalSec = @json($pollInterval ?? 15);
        const liveStatusUrl = @json(route('admin.tasks.live_status', $task->id));

        function escHtml(s) {
            if (!s) return '';
            return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        const map = L.map('task-map').setView([taskMeta.latitude, taskMeta.longitude], 14);

        let tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        let tileAttr = '&copy; OpenStreetMap contributors';
        if (mapStyle === 'carto') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
            tileAttr = '&copy; OpenStreetMap &copy; CARTO';
        } else if (mapStyle === 'mapbox') {
            tileUrl = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';
            tileAttr = 'Tiles &copy; Esri';
        }
        L.tileLayer(tileUrl, { maxZoom: 19, attribution: tileAttr }).addTo(map);

        const taskIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });
        const techIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
        });

        L.marker([taskMeta.latitude, taskMeta.longitude], { icon: taskIcon }).addTo(map)
            .bindPopup(`<b>Work Order Target:</b><br>${escHtml(taskMeta.customer_name)}<br><small>${escHtml(taskMeta.address)}</small>`)
            .openPopup();

        if (geofenceRadius > 0) {
            L.circle([taskMeta.latitude, taskMeta.longitude], {
                radius: geofenceRadius,
                color: '#2563EB',
                fillColor: '#3B82F6',
                fillOpacity: 0.1,
                weight: 1.5,
                dashArray: '5, 5'
            }).addTo(map);
        }

        let techMarker = null;
        let routeLine = null;

        function upsertTechPosition(lat, lng, techName, updatedText, fitInitial = false) {
            if (lat === null || lng === null) return;
            const popupHtml = `<b>Technician Dispatch:</b><br>${escHtml(techName || 'Technician')}<br><small>Updated: ${escHtml(updatedText || 'Just now')}</small>`;

            if (!techMarker) {
                techMarker = L.marker([lat, lng], { icon: techIcon }).addTo(map).bindPopup(popupHtml);
            } else {
                techMarker.setLatLng([lat, lng]);
                techMarker.setPopupContent(popupHtml);
            }

            if (['assigned', 'accepted', 'on-going'].includes(taskMeta.status)) {
                if (!routeLine) {
                    routeLine = L.polyline([[lat, lng], [taskMeta.latitude, taskMeta.longitude]], {
                        color: '#2563EB', dashArray: '5, 8', weight: 3
                    }).addTo(map);
                } else {
                    routeLine.setLatLngs([[lat, lng], [taskMeta.latitude, taskMeta.longitude]]);
                }
                if (fitInitial) {
                    map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
                }
            }
        }

        if (taskMeta.tech_lat !== null && taskMeta.tech_lng !== null) {
            upsertTechPosition(taskMeta.tech_lat, taskMeta.tech_lng, taskMeta.tech_name, taskMeta.tech_updated, true);
        }

        window.fsmUpdateTaskMapTechPosition = upsertTechPosition;
    });
</script>
@endif

@php
    $liveStatusEndpoint = route('admin.tasks.live_status', $task->id);
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const liveStatusUrl = @json($liveStatusEndpoint);
        let initialSignature = null;
        let isCheckingLive = false;

        function escHtml(s) {
            if (!s) return '';
            return String(s).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
        }

        function isUserBusyOnTaskShow() {
            const active = document.activeElement;
            if (active && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName)) {
                return true;
            }
            const openModals = document.querySelectorAll('#proof-lightbox:not(.hidden), #rework-modal:not(.hidden), #remove-bom-modal:not(.hidden)');
            return openModals.length > 0;
        }

        async function pollTaskLiveStatus() {
            if (document.hidden || isCheckingLive) return;
            isCheckingLive = true;
            try {
                const resp = await fetch(liveStatusUrl + '?_ts=' + Date.now(), {
                    headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
                });
                if (!resp.ok) return;
                const data = await resp.json();

                const pill = document.getElementById('live-status-pill');
                if (pill && data.status_label) pill.textContent = data.status_label;
                const progLabel = document.getElementById('live-progress-label');
                if (progLabel) progLabel.textContent = `Status: ${data.progress_percent}% Completed`;
                const progBar = document.getElementById('live-progress-bar');
                if (progBar) progBar.style.width = `${data.progress_percent}%`;

                if (data.technician) {
                    if (typeof window.fsmUpdateTaskMapTechPosition === 'function') {
                        window.fsmUpdateTaskMapTechPosition(
                            data.technician.latitude,
                            data.technician.longitude,
                            data.technician.name,
                            data.technician.last_seen,
                            false
                        );
                    }
                    const etaEl = document.getElementById('live-distance-eta');
                    if (etaEl && data.technician.distance_km !== null) {
                        etaEl.textContent = `${data.technician.distance_km} km away • ETA ~${data.technician.eta_minutes} min`;
                    }
                    const presEl = document.getElementById('live-tech-presence');
                    if (presEl) {
                        presEl.innerHTML = data.technician.is_online
                            ? '<span class="text-emerald-600 flex items-center gap-1 font-semibold"><span class="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-ping"></span> Tech Online</span>'
                            : `<span class="text-slate-400">Last ping ${escHtml(data.technician.last_seen)}</span>`;
                    }
                }

                if (initialSignature === null) {
                    initialSignature = data.state_signature || '';
                } else if (data.state_signature && data.state_signature !== initialSignature) {
                    if (!isUserBusyOnTaskShow()) {
                        initialSignature = data.state_signature;
                        window.location.reload();
                    }
                }
            } catch (e) {
            } finally {
                isCheckingLive = false;
            }
        }

        pollTaskLiveStatus();
        const liveTimer = setInterval(pollTaskLiveStatus, 4000);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) pollTaskLiveStatus();
        });
        window.addEventListener('focus', pollTaskLiveStatus);
        window.addEventListener('beforeunload', () => clearInterval(liveTimer));
    });
</script>

<script>
    function copyTrackingUrl() {
        var input = document.getElementById('customer-tracking-url');
        if (!input) return;
        input.select();
        navigator.clipboard.writeText(input.value).then(function() {
            var icon = document.getElementById('copy-icon');
            if (icon) {
                icon.setAttribute('name', 'checkmark-outline');
                setTimeout(function() { icon.setAttribute('name', 'copy-outline'); }, 2000);
            }
        });
    }

    function openProofLightbox(imgUrl, slotLabel, desc, timeStr) {
        document.getElementById('lb-img').src = imgUrl;
        document.getElementById('lb-download').href = imgUrl;
        document.getElementById('lb-slot').textContent = slotLabel;
        document.getElementById('lb-desc').textContent = desc;
        document.getElementById('lb-time').textContent = timeStr;
        document.getElementById('proof-lightbox').classList.remove('hidden');
    }

    function closeProofLightbox() {
        document.getElementById('proof-lightbox').classList.add('hidden');
    }

    document.getElementById('proof-lightbox')?.addEventListener('click', function(e) {
        if (e.target === this) closeProofLightbox();
    });

    async function verifyBastSeal() {
        const banner = document.getElementById('bast-seal-banner');
        const btn = document.getElementById('btn-verify-seal');
        if (btn) btn.disabled = true;
        try {
            const url = @json(route('admin.tasks.bast.verify', ['task' => $task->id, 'token' => $task->tracking_token]));
            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();
            banner.classList.remove('hidden');
            if (data.verified) {
                banner.className = 'mb-5 p-3.5 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between gap-3 shadow-sm';
                banner.innerHTML = `<div class="flex items-center gap-2"><ion-icon name="shield-checkmark" class="text-base text-emerald-600"></ion-icon><span>Cryptographic SHA-256 BAST Seal Verified (${data.certificate})</span></div><span class="font-mono text-[10px]">${String(data.stored_hash).substring(0, 20)}...</span>`;
            } else {
                banner.className = 'mb-5 p-3.5 rounded-xl border bg-red-50 border-red-200 text-red-800 text-xs font-semibold flex items-center justify-between gap-3 shadow-sm';
                banner.innerHTML = `<div class="flex items-center gap-2"><ion-icon name="warning" class="text-base text-red-600"></ion-icon><span>Seal Verification Mismatch! Reopen via Request Rework or inspect BAST audit trail.</span></div>`;
            }
        } catch (err) {
            banner.classList.remove('hidden');
            banner.className = 'mb-5 p-3.5 rounded-xl border bg-red-50 border-red-200 text-red-800 text-xs font-semibold flex items-center gap-2 shadow-sm';
            banner.textContent = 'Unable to verify cryptographic seal right now.';
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProofLightbox();
            document.getElementById('rework-modal')?.classList.add('hidden');
            document.getElementById('remove-bom-modal')?.classList.add('hidden');
        }
    });

    function openRemoveBomModal(btn) {
        document.getElementById('remove-bom-form').action = btn.getAttribute('data-remove-url');
        document.getElementById('remove-bom-label').textContent = btn.getAttribute('data-item-name') || 'Selected BOM item';
        document.getElementById('remove-bom-modal').classList.remove('hidden');
    }
</script>
@endpush

<!-- Remove BOM Material Confirmation Modal -->
<div id="remove-bom-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
    <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
        <h3 class="text-sm font-bold text-textPrimary mb-1">Remove BOM Material</h3>
        <p id="remove-bom-label" class="text-xs font-semibold text-slate-700 mb-2"></p>
        <p class="text-xs text-textSecondary mb-5">This material record will be removed from the work order and its quantity restored to warehouse stock.</p>
        <form id="remove-bom-form" method="POST" action="" class="flex justify-end gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="document.getElementById('remove-bom-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm">Remove &amp; Restore</button>
        </form>
    </div>
</div>
@endsection
