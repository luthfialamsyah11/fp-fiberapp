@extends('layouts.admin')

@section('title', 'Admin Operations Manual')
@section('header', 'Admin Operations Manual')

@push('styles')
<style>
    .guide-nav-item.active {
        background-color: #EFF6FF;
        border-color: #3B82F6;
        color: #1D4ED8;
        font-weight: 700;
    }
</style>
@endpush

@section('content')
@php
    $companyName       = $settings['company_name'] ?? 'FiberOps Global Carrier';
    $supportPhone      = $settings['support_phone'] ?? '+1-800-555-0199';
    $supportEmail      = $settings['support_email'] ?? 'noc@bentengsiber.com';
    $slaHours          = (int) ($settings['sla_warning_hours'] ?? 2);
    $pollInterval      = (int) ($settings['gps_refresh_interval'] ?? 15);
    $geofenceMeters    = (int) ($settings['geofence_check_meters'] ?? 0);
    $retentionDays     = (int) ($settings['telemetry_retention_days'] ?? 30);
    $requirePhoto      = ($settings['require_proof_photo'] ?? '1') == '1';
    $requireSignature  = ($settings['require_customer_signature'] ?? '1') == '1';
    $autoAssignEnabled = ($settings['auto_assign'] ?? '1') == '1';
    $onlineWindowSec   = (int) ($onlineWindowSeconds ?? 120);
    $onlineWindowMin   = max(1, (int) round($onlineWindowSec / 60));
@endphp

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Sticky Sidebar: Table of Contents -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-fsmBorder p-5 shadow-sm sticky top-6 space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold flex-shrink-0">
                    <ion-icon name="book-outline" class="text-lg"></ion-icon>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider truncate">Operations Manual</h3>
                    <span class="text-[10px] text-slate-400">FiberOps Standard v2.0</span>
                </div>
            </div>

            <!-- Quick Filter with Clear Button -->
            <div class="relative">
                <input type="text" id="guide-search" placeholder="Search SOP topics, routes..."
                    class="w-full text-xs rounded-xl border border-slate-200 pl-3 pr-8 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                <button type="button" id="guide-search-clear" title="Clear search"
                    class="hidden absolute right-2 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center cursor-pointer">
                    <ion-icon name="close" class="text-xs"></ion-icon>
                </button>
            </div>
            <div id="guide-search-meta" class="hidden text-[10px] font-semibold text-blue-600 px-1"></div>

            <!-- Navigation Links -->
            <nav id="guide-toc" class="space-y-1 text-xs">
                <a href="#quickstart" data-section="quickstart" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="rocket-outline" class="text-sm flex-shrink-0 text-blue-600"></ion-icon>
                    <span>1. Quickstart &amp; FSM Workflow</span>
                </a>
                <a href="#dashboard" data-section="dashboard" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="grid-outline" class="text-sm flex-shrink-0 text-indigo-600"></ion-icon>
                    <span>2. Dashboard &amp; KPI Metrics</span>
                </a>
                <a href="#tasks" data-section="tasks" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="clipboard-outline" class="text-sm flex-shrink-0 text-emerald-600"></ion-icon>
                    <span>3. Task Management &amp; Lifecycle</span>
                </a>
                <a href="#assignments" data-section="assignments" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="calendar-clear-outline" class="text-sm flex-shrink-0 text-cyan-600"></ion-icon>
                    <span>4. Assignment Monitoring</span>
                </a>
                <a href="#inventory" data-section="inventory" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="cube-outline" class="text-sm flex-shrink-0 text-violet-600"></ion-icon>
                    <span>5. Inventory &amp; Materials Ledger</span>
                </a>
                <a href="#tracking" data-section="tracking" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="navigate-outline" class="text-sm flex-shrink-0 text-sky-600"></ion-icon>
                    <span>6. Live GPS Telemetry Map</span>
                </a>
                <a href="#proof" data-section="proof" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="camera-outline" class="text-sm flex-shrink-0 text-amber-600"></ion-icon>
                    <span>7. Optical Proof &amp; BAST Audit</span>
                </a>
                <a href="#technicians" data-section="technicians" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="people-outline" class="text-sm flex-shrink-0 text-purple-600"></ion-icon>
                    <span>8. Technician Fleet &amp; APK Ops</span>
                </a>
                <a href="#reports-settings" data-section="reports-settings" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="bar-chart-outline" class="text-sm flex-shrink-0 text-teal-600"></ion-icon>
                    <span>9. History, Reports &amp; Settings</span>
                </a>
                <a href="#faq" data-section="faq" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="help-circle-outline" class="text-sm flex-shrink-0 text-red-600"></ion-icon>
                    <span>10. Troubleshooting &amp; FAQ</span>
                </a>
            </nav>

            <!-- Dynamic NOC Contact Footer synced with SettingService -->
            <div class="pt-3 border-t border-slate-100 space-y-1.5 text-[11px] text-slate-500">
                <div class="font-semibold text-slate-700 truncate" title="{{ $companyName }}">{{ $companyName }}</div>
                <div class="flex items-center gap-1.5 truncate">
                    <ion-icon name="call-outline" class="text-xs text-slate-400 flex-shrink-0"></ion-icon>
                    <span class="truncate">NOC Desk: {{ $supportPhone }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[10px] text-slate-400">{{ $supportEmail }}</span>
                    <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:underline font-semibold flex-shrink-0">Settings &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Guide Content -->
    <div class="lg:col-span-3 space-y-6">

        <!-- Header Hero + Live System Configuration Snapshot -->
        <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-5">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold mb-3">
                    <ion-icon name="shield-checkmark"></ion-icon>
                    <span>Standard Operating Procedures (SOP) &bull; v2.0 Live Synced</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $companyName }} &mdash; Admin Operations Manual
                </h1>
                <p class="text-sm text-slate-600 mt-2 max-w-2xl leading-relaxed">
                    Operational handbook for NOC Dispatchers, Field Supervisors, and System Administrators covering work-order dispatch, 1-click GPS auto-assignment, atomic inventory control, live fleet telemetry, cryptographic SHA-256 BAST auditing, and Android APK field operations.
                </p>
            </div>

            <!-- Active Platform Guardrails Snapshot (Reads Live SettingService) -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div class="flex items-center justify-between gap-2 mb-2.5">
                    <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <ion-icon name="pulse-outline" class="text-blue-600 text-sm"></ion-icon>
                        Active Platform Guardrails (Live from Settings)
                    </span>
                    <a href="{{ route('admin.settings') }}" class="text-[11px] font-semibold text-blue-600 hover:underline">Configure &rarr;</a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-[11px]">
                    <div class="bg-white rounded-lg border border-slate-200 px-3 py-2">
                        <div class="text-[10px] text-slate-400 font-semibold uppercase">SLA Target</div>
                        <div class="font-bold text-slate-800 mt-0.5">{{ $slaHours }} {{ $slaHours === 1 ? 'Hour' : 'Hours' }}</div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-3 py-2">
                        <div class="text-[10px] text-slate-400 font-semibold uppercase">GPS Poll / Online</div>
                        <div class="font-bold text-slate-800 mt-0.5">{{ $pollInterval }}s / {{ $onlineWindowMin }} min</div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-3 py-2">
                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Completion Gates</div>
                        <div class="font-bold text-slate-800 mt-0.5">
                            Photo: {{ $requirePhoto ? 'Required' : 'Optional' }} &bull; Sig: {{ $requireSignature ? 'Required' : 'Optional' }}
                        </div>
                    </div>
                    <div class="bg-white rounded-lg border border-slate-200 px-3 py-2">
                        <div class="text-[10px] text-slate-400 font-semibold uppercase">Geofence / Auto-Assign</div>
                        <div class="font-bold text-slate-800 mt-0.5">
                            {{ $geofenceMeters > 0 ? $geofenceMeters . 'm' : 'Off' }} &bull; {{ $autoAssignEnabled ? 'Auto ON' : 'Manual' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty Search State -->
        <div id="guide-search-empty" class="hidden bg-white rounded-2xl border border-slate-200 p-8 text-center shadow-sm space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                <ion-icon name="search-outline" class="text-2xl"></ion-icon>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No matching manual topics found</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Try searching for keywords like <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">BAST</code>, <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">inventory</code>, <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">geofence</code>, <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">auto-dispatch</code>, or <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">APK</code>.
            </p>
            <button type="button" id="guide-search-reset-btn" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold cursor-pointer">
                Reset Search
            </button>
        </div>

        <!-- 1. Quick Start Section -->
        <div id="quickstart" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold flex-shrink-0">1</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">End-to-End Closed-Loop FSM Workflow</h2>
                    <span class="text-xs text-slate-500">Real-time coordination between Web NOC Console and Technician Android APK / PWA</span>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                FiberOps FSM links the Admin Portal with the field Technician Android APK (and web PWA fallback) through authenticated REST APIs, transactional state guards, and an offline-first SQLite/local queue. Field updates, BOM deductions, photographic proofs, and chronological GPS pings (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">captured_at</code>) sync automatically once network connectivity is available.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 my-4">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">1</div>
                    <div class="text-xs font-bold text-slate-800">Order Ingestion</div>
                    <div class="text-[10px] text-slate-500 mt-1">NOC creates ticket with interactive map pin or Google Maps URL paste.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">2</div>
                    <div class="text-xs font-bold text-slate-800">Proximity Dispatch</div>
                    <div class="text-[10px] text-slate-500 mt-1">Manual assign, Auto-Assign on creation, or 1-Click Auto-Dispatch Nearest (GPS).</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">3</div>
                    <div class="text-xs font-bold text-slate-800">Field Execution</div>
                    <div class="text-[10px] text-slate-500 mt-1">Technician accepts, navigates via Route Map, logs progress % &amp; BOM materials.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-amber-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">4</div>
                    <div class="text-xs font-bold text-slate-800">Proof &amp; SHA-256 BAST</div>
                    <div class="text-[10px] text-slate-500 mt-1">OPM photo proof, Sign-on-Glass e-signature, and tamper-evident BAST certificate.</div>
                </div>
            </div>
        </div>

        <!-- 2. Dashboard & KPI Overview -->
        <div id="dashboard" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold flex-shrink-0">2</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Dashboard &amp; Operational KPI Metrics</h2>
                    <span class="text-xs text-slate-500">Instant situational awareness across dispatch queues and live fleet heartbeat</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The executive dashboard (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/dashboard</code>) tracks live fleet health and ticket throughput across four primary indicators:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Pending Tasks</b> (Amber): Unassigned work orders waiting in the dispatch queue. Tickets unresolved beyond the configured SLA warning threshold (currently <b>{{ $slaHours }}h</b>) trigger red SLA breach badges.</li>
                    <li><b>Active Tasks / On-Going</b> (Blue): Assigned, accepted, or on-going field dispatches currently executed by technicians.</li>
                    <li><b>Completed Tasks</b> (Green): Work orders sealed with photographic proof, customer sign-off (when required), and SHA-256 BAST integrity hash.</li>
                    <li><b>Online Fleet</b> (Emerald): Technicians with active duty enabled AND a fresh GPS/heartbeat ping received within the last <b>{{ $onlineWindowMin }} minutes ({{ $onlineWindowSec }} seconds)</b>. If a device stops transmitting past {{ $onlineWindowMin }} minutes, its status transitions automatically to stale/offline.</li>
                </ul>
            </div>
        </div>

        <!-- 3. Task Management & Lifecycle -->
        <div id="tasks" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold flex-shrink-0">3</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Task Management, Map Picker &amp; Lifecycle SOP</h2>
                    <span class="text-xs text-slate-500">Ticket creation, interactive geocoding, Command Details, and Rework workflow</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <h4 class="font-bold text-slate-800">A. Creating or Editing a Work Order (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tasks/create</code>):</h4>
                <ol class="list-decimal list-inside space-y-1.5 ml-2">
                    <li>Navigate to <b>Task Management</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tasks</code>) &rarr; click <b>+ Create Work Order</b>.</li>
                    <li>Enter subscriber details (Customer Name, Contact Phone, Address) and fault description.</li>
                    <li>Pin the exact premise location using any of the 4 built-in geospatial tools: <b>Interactive Leaflet Map Click/Drag</b>, <b>Nominatim Address Search</b>, <b>Paste Google Maps URL or Decimal Coordinates</b> (e.g., <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">-6.2088, 106.8456</code>), or <b>Use My Current GPS</b>. Toggle Street / Minimal / Satellite layers as needed.</li>
                    <li>Select Priority: <b>High</b> (outage/fiber cut &mdash; triggers emergency webhook alert when enabled), <b>Medium</b> (signal degradation), or <b>Low</b> (scheduled maintenance).</li>
                    <li>Pick a technician from the live <b>Nearest Technician Proximity Cards</b> (sorted by distance to pin), or leave unassigned (if <i>Proximity-Based Auto-Dispatch</i> is enabled in Settings, the nearest available technician is automatically assigned on save).</li>
                </ol>

                <h4 class="font-bold text-slate-800 mt-4">B. Finite State Machine (FSM) Status Transitions:</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-2">
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">pending</span>
                        <p class="text-[11px] text-slate-600 mt-1">Created and queued; awaiting technician assignment.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">assigned</span>
                        <p class="text-[11px] text-slate-600 mt-1">Dispatched to a technician; awaiting accept or decline in APK.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">accepted</span>
                        <p class="text-[11px] text-slate-600 mt-1">Technician confirmed dispatch and is routing to site.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">on-going</span>
                        <p class="text-[11px] text-slate-600 mt-1">Work in progress on-site; progress %, BOM, and proofs are recorded.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">completed</span>
                        <p class="text-[11px] text-slate-600 mt-1">All completion gates satisfied; BOM locked and SHA-256 BAST sealed.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700">rejected / rework</span>
                        <p class="text-[11px] text-slate-600 mt-1">Declined tickets return to queue with reason logged; completed tickets can be reopened via <b>Request Rework</b>.</p>
                    </div>
                </div>

                <h4 class="font-bold text-slate-800 mt-4">C. Task Command Details (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tasks/{id}</code>):</h4>
                <p>From the detail console, NOC dispatchers can post inline progress notes, add or remove BOM materials (automatically synced with Inventory stock while not completed), share the public <b>Customer Live Tracking</b> link (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/track/{token}</code>) via WhatsApp, view the printable BAST PDF, or issue <b>Request Rework</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">completed &rarr; on-going</code>) which clears the old BAST seal until re-verified.</p>
            </div>
        </div>

        <!-- 4. Assignment Monitoring -->
        <div id="assignments" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold flex-shrink-0">4</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Assignment Monitoring &amp; 1-Click Auto-Dispatch</h2>
                    <span class="text-xs text-slate-500">Priority queue triage, nearest-GPS routing, and technician decline audit</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The <b>Assignment Monitoring</b> console (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/assignments</code>) provides a dedicated dispatch board for rapid queue clearing:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Strict Priority Ordering</b>: Unassigned tickets are automatically ordered <b>High &rarr; Medium &rarr; Low</b> and oldest-first so SLA-critical outages stay at the top.</li>
                    <li><b>1-Click Auto-Dispatch Nearest (GPS)</b>: Uses Haversine distance against each active technician's latest live GPS coordinates and current active workload to dispatch the closest eligible technician immediately.</li>
                    <li><b>Inline Reassignment &amp; Workload Panel</b>: Monitor active ticket counts per technician and reassign stalled tickets without leaving the board.</li>
                    <li><b>Recent Technician Declines Feed</b>: Displays every rejected assignment with the technician's submitted decline reason and timestamp for supervisor review.</li>
                </ul>
            </div>
        </div>

        <!-- 5. Inventory & Materials Ledger -->
        <div id="inventory" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-violet-100 text-violet-700 flex items-center justify-center font-bold flex-shrink-0">5</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Inventory &amp; Materials Ledger</h2>
                    <span class="text-xs text-slate-500">Atomic stock ledger synchronized with Task BOM &amp; Android APK catalog</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Manage optical cables, ONT units, splitters, and splice consumables via <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/inventory</code>:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Atomic Stock Adjustments</b>: Use <b>Restock (+)</b>, <b>Deduct (&minus;)</b>, or <b>Set Exact</b> modal actions backed by database row locks (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">lockForUpdate</code>) to prevent race conditions during concurrent field BOM logging.</li>
                    <li><b>Active / Inactive APK Visibility</b>: Deactivating an item hides it from the Technician APK material selector while preserving historical BAST records.</li>
                    <li><b>BAST Deletion Protection</b>: Inventory items referenced in any completed work order BOM cannot be hard-deleted so financial and audit history remains intact.</li>
                    <li><b>Valuation &amp; CSV Export</b>: Unit prices and total stock valuation automatically honor the <b>Display Currency</b> configured in Settings, with 1-click CSV export (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/inventory/export</code>).</li>
                </ul>
            </div>
        </div>

        <!-- 6. Live Tracking GPS Map -->
        <div id="tracking" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold flex-shrink-0">6</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Live GPS Fleet Telemetry Map</h2>
                    <span class="text-xs text-slate-500">Real-time geospatial positioning, accuracy circles, and breadcrumb playback</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The <b>Live Tracking</b> console (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tracking</code>) uses Leaflet 1.9.4 with <b>Adaptive Delta Polling</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tracking/live-data?since_id=</code>) refreshing every <b>{{ $pollInterval }} seconds</b> (configurable in Settings) without full-page reloads:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Green Marker</b>: Online technician actively executing an assigned / accepted / on-going work order.</li>
                    <li><b>Blue Marker</b>: Online technician on standby (available for immediate dispatch).</li>
                    <li><b>Grey Marker</b>: Offline technician or stale GPS fix (no ping received within the {{ $onlineWindowMin }}-minute online window).</li>
                    <li><b>Red Destination Marker &amp; Route Trail</b>: Selecting a technician isolates their chronological GPS breadcrumb trail, translucent GPS accuracy radius circle (meters), and direct vector to their active customer site.</li>
                </ul>
            </div>
        </div>

        <!-- 7. Proof of Work & Cryptographic BAST Audit -->
        <div id="proof" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold flex-shrink-0">7</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Optical Proof of Work &amp; Cryptographic BAST Audit</h2>
                    <span class="text-xs text-slate-500">Quality assurance standards, configurable gates, and SHA-256 seal verification</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Review field evidence via <b>Proof of Work</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/proofs</code>) or inside any work order's printable BAST certificate (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tasks/{id}/bast</code>). Completion gates are enforced server-side by <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">TaskStateMachine</code> according to Settings (Photo Proof Gate, Customer Sign-on-Glass E-Signature Gate, and optional Geofence Check).</p>

                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 space-y-2">
                    <div class="font-bold text-amber-900">Optical Power Meter (OPM) Attenuation Standards:</div>
                    <ul class="list-disc list-inside space-y-1 text-amber-800">
                        <li><b>Optimal:</b> &minus;15.0 dBm to &minus;20.0 dBm (Minimal splice loss, peak ONT operational stability).</li>
                        <li><b>Standard Compliant:</b> &minus;20.1 dBm to &minus;24.0 dBm (Acceptable carrier baseline with stable link budget).</li>
                        <li><b>Critical / Degraded:</b> Worse than &minus;25.0 dBm (Risk of Red LOS or packet loss &mdash; trigger <b>Request Rework</b> for re-splicing).</li>
                    </ul>
                </div>

                <p><b>SHA-256 BAST Tamper-Evident Seal:</b> Upon completion, the system hashes the work order metadata, BOM materials, proofs, and customer signature into an immutable SHA-256 digest verifiable via <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/tasks/{id}/bast/verify</code>. Completed BOMs are locked against tampering unless formally reopened via Request Rework.</p>
            </div>
        </div>

        <!-- 8. Technician Fleet & Android APK Operations -->
        <div id="technicians" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold flex-shrink-0">8</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Technician Fleet &amp; Android APK Field Operations</h2>
                    <span class="text-xs text-slate-500">Account lifecycle, duty telemetry, and Android native background GPS setup</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Via the <b>Technicians</b> console (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/technicians</code>), administrators manage field accounts:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Account Provisioning &amp; RBAC</b>: Create technician accounts, reset passwords, or toggle account access between <b>Active</b> and <b>Disabled</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">is_active</code>). Disabled accounts are immediately blocked from API &amp; APK access.</li>
                    <li><b>Online / Offline Duty Control</b>: Live availability is controlled by the technician inside the Android APK header toggle. When Online, the APK runs a native <b>Android Background Geolocation Foreground Service</b> that continues transmitting GPS coordinates even when the screen is locked or minimized.</li>
                    <li><b>Offline GPS Buffer Replay</b>: In dead-zone basements or rural areas, the APK queues GPS coordinates locally with exact capture timestamps (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">captured_at</code>) and replays them chronologically upon reconnection.</li>
                    <li><b>Required Android Device Permissions</b>: On technician handsets, grant Location = <b>Allow all the time</b> and set Battery Optimization = <b>Unrestricted</b> (accessible directly from the APK Profile &rarr; GPS Diagnostics card).</li>
                </ul>
            </div>
        </div>

        <!-- 9. Job History, Analytics Reports & Platform Settings -->
        <div id="reports-settings" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold flex-shrink-0">9</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Job History, Analytics Reports &amp; Platform Settings</h2>
                    <span class="text-xs text-slate-500">Audit archives, SLA reporting, SSRF-safe webhooks, and telemetry retention</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Job History</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/history</code>): Searchable archive of completed and rejected work orders with quick access to printable BAST certificates and optical proofs.</li>
                    <li><b>Reports &amp; CSV Export</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/reports</code>): Track resolution velocity, SLA compliance rates, technician completion leaderboards, and export datasets via <code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/reports/export</code>.</li>
                    <li><b>Platform Settings</b> (<code class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]">/settings</code>): Configure branding, currency, default dial code, map tile layer, SLA guardrails, and SSRF-protected outbound webhooks. Daily scheduled pruning and manual <b>Purge Stale Logs</b> remove GPS points older than the retention window (currently <b>{{ $retentionDays }} days</b>) while always safeguarding the <b>latest 50 breadcrumbs per technician</b>.</li>
                </ul>
            </div>
        </div>

        <!-- 10. FAQ & Troubleshooting -->
        <div id="faq" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold flex-shrink-0">10</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Troubleshooting &amp; Frequently Asked Questions (FAQ)</h2>
                    <span class="text-xs text-slate-500">Rapid resolution for field, GPS, webhook, and BAST issues</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600">
                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Why can't a field technician complete a work order in the APK?</h4>
                    <p class="mt-1 text-slate-600">A: Check active guardrails in <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:underline font-semibold">Platform Settings</a>: (1) Mandatory Photo Proof Gate requires at least 1 uploaded proof photo, (2) Mandatory Customer E-Signature Gate requires a Sign-on-Glass signature, and (3) Geofencing Radius Tolerance blocks completion if the technician's latest GPS fix is outside the configured distance from the customer pin.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Technician marker shows Grey (Offline/Stale) on Live Tracking even when the phone is powered on?</h4>
                    <p class="mt-1 text-slate-600">A: Live Online status requires a GPS heartbeat within the last <b>{{ $onlineWindowMin }} minutes</b>. Have the technician open the APK <b>Profile &rarr; GPS Diagnostics</b> card, verify they are toggled <b>Online</b>, tap <b>Open Android Location Settings</b> to enable <b>Allow all the time</b> + <b>Unrestricted Battery</b>, and tap <b>Force Sync</b> on the Route Map tab.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Why did Settings reject my Webhook Endpoint URL?</h4>
                    <p class="mt-1 text-slate-600">A: Both Save and Test Webhook enforce an SSRF security guard (<code class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[11px]">SettingService::isSafeExternalUrl</code>). Only public external HTTP/HTTPS endpoints (e.g., Telegram Bot API, Slack Incoming Webhooks, or public HTTPS receivers) are permitted; <code class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[11px]">localhost</code>, loopback, and private RFC1918 IP ranges are automatically blocked.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: How do we correct a completed work order if the OPM photo is blurry or materials were mislogged?</h4>
                    <p class="mt-1 text-slate-600">A: Open the work order (<code class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[11px]">/tasks/{id}</code>) and click <b>Request Rework</b>. This transitions the ticket back to <code class="px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-mono text-[11px]">on-going</code>, unlocks BOM edits, clears the old SHA-256 BAST seal, and notifies the technician to upload updated evidence before resealing.</p>
                </div>
            </div>
        </div>

        <!-- Footer Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-semibold flex items-center gap-1">
                &larr; Back to Admin Dashboard
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.assignments') }}" class="text-slate-600 hover:text-slate-900 font-semibold">Assignment Monitoring</a>
                <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                    Open Platform Settings &rarr;
                </a>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('guide-search');
        const searchClear = document.getElementById('guide-search-clear');
        const searchMeta = document.getElementById('guide-search-meta');
        const searchEmpty = document.getElementById('guide-search-empty');
        const resetBtn = document.getElementById('guide-search-reset-btn');
        const sections = Array.from(document.querySelectorAll('.guide-section'));
        const navItems = Array.from(document.querySelectorAll('.guide-nav-item'));

        // Smooth scrolling for TOC items
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    const el = document.querySelector(targetId);
                    if (el && el.style.display !== 'none') {
                        e.preventDefault();
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        function applySearchFilter(rawQuery) {
            const query = (rawQuery || '').toLowerCase().trim();
            let visibleCount = 0;

            sections.forEach(sec => {
                const secId = sec.getAttribute('id');
                const text = sec.innerText.toLowerCase();
                const matched = !query || text.includes(query);
                sec.style.display = matched ? '' : 'none';
                if (matched) visibleCount++;

                const tocLink = navItems.find(n => n.dataset.section === secId);
                if (tocLink) {
                    tocLink.style.display = matched ? '' : 'none';
                }
            });

            if (searchClear) {
                searchClear.classList.toggle('hidden', !query);
            }
            if (searchMeta) {
                if (query) {
                    searchMeta.classList.remove('hidden');
                    searchMeta.textContent = visibleCount + ' of ' + sections.length + ' sections matched';
                } else {
                    searchMeta.classList.add('hidden');
                    searchMeta.textContent = '';
                }
            }
            if (searchEmpty) {
                searchEmpty.classList.toggle('hidden', visibleCount > 0);
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                applySearchFilter(e.target.value);
            });
        }

        function resetSearch() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            applySearchFilter('');
        }

        searchClear?.addEventListener('click', resetSearch);
        resetBtn?.addEventListener('click', resetSearch);

        // Active link highlighting on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute('id');
                    navItems.forEach(item => {
                        if (item.getAttribute('href') === '#' + id) {
                            item.classList.add('active');
                        } else {
                            item.classList.remove('active');
                        }
                    });
                }
            });
        }, { rootMargin: '-20% 0px -70% 0px' });

        sections.forEach(sec => observer.observe(sec));
    });
</script>
@endpush
@endsection
