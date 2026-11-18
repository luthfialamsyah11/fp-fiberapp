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
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

    <!-- Left Sticky Sidebar: Table of Contents -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl border border-fsmBorder p-5 shadow-sm sticky top-6 space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold">
                    <ion-icon name="book-outline" class="text-lg"></ion-icon>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Manual Table of Contents</h3>
                    <span class="text-[10px] text-slate-400">FiberOps Standard v1.2</span>
                </div>
            </div>

            <!-- Quick Filter -->
            <div>
                <input type="text" id="guide-search" placeholder="Search manual topics..."
                    class="w-full text-xs rounded-xl border border-slate-200 px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
            </div>

            <!-- Navigation Links -->
            <nav id="guide-toc" class="space-y-1 text-xs">
                <a href="#quickstart" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="rocket-outline" class="text-sm flex-shrink-0 text-blue-600"></ion-icon>
                    <span>1. Quickstart & FSM Workflow</span>
                </a>
                <a href="#dashboard" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="grid-outline" class="text-sm flex-shrink-0 text-indigo-600"></ion-icon>
                    <span>2. Dashboard & KPI Metrics</span>
                </a>
                <a href="#tasks" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="clipboard-outline" class="text-sm flex-shrink-0 text-emerald-600"></ion-icon>
                    <span>3. Ticket Lifecycle Management</span>
                </a>
                <a href="#tracking" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="navigate-outline" class="text-sm flex-shrink-0 text-sky-600"></ion-icon>
                    <span>4. Live GPS Telemetry Map</span>
                </a>
                <a href="#proof" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="camera-outline" class="text-sm flex-shrink-0 text-amber-600"></ion-icon>
                    <span>5. Optical Proof & BAST Audit</span>
                </a>
                <a href="#technicians" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="people-outline" class="text-sm flex-shrink-0 text-purple-600"></ion-icon>
                    <span>6. Technician Fleet Operations</span>
                </a>
                <a href="#faq" class="guide-nav-item flex items-center gap-2 p-2 rounded-xl text-slate-600 hover:bg-slate-50 transition-colors border-l-2 border-transparent">
                    <ion-icon name="help-circle-outline" class="text-sm flex-shrink-0 text-red-600"></ion-icon>
                    <span>7. Troubleshooting & FAQ</span>
                </a>
            </nav>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>NOC Desk: +1 (800) 555-0199</span>
                <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:underline font-semibold">Settings &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Main Guide Content -->
    <div class="lg:col-span-3 space-y-8">

        <!-- Header Hero -->
        <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold mb-3">
                <ion-icon name="shield-checkmark"></ion-icon>
                <span>Standard Operating Procedures (SOP) Documentation</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                FiberOps Admin Operations Manual
            </h1>
            <p class="text-sm text-slate-600 mt-2 max-w-2xl leading-relaxed">
                Comprehensive operational handbook designed for NOC Dispatchers, Field Supervisors, and System Administrators to coordinate work orders, manage dispatch queues, monitor live telemetry, and audit photographic proof of work.
            </p>
        </div>

        <!-- 1. Quick Start Section -->
        <div id="quickstart" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">1</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">End-to-End System Workflow</h2>
                    <span class="text-xs text-slate-500">Bi-directional integration between Web Dispatcher and Mobile Field PWA</span>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                FiberOps FSM operates as a closed-loop reactive engine. Every dispatch or status update issued by the NOC console instantly reflects on field technician terminals, and field telemetry syncs seamlessly back to dispatchers:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 my-4">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">1</div>
                    <div class="text-xs font-bold text-slate-800">Order Ingestion</div>
                    <div class="text-[10px] text-slate-500 mt-1">NOC creates ticket or subscriber reports outage.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">2</div>
                    <div class="text-xs font-bold text-slate-800">Field Dispatch</div>
                    <div class="text-[10px] text-slate-500 mt-1">Smart proximity routes task to field specialist.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">3</div>
                    <div class="text-xs font-bold text-slate-800">Live Execution</div>
                    <div class="text-[10px] text-slate-500 mt-1">Technician accepts, travels, and logs work progress %.</div>
                </div>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                    <div class="w-8 h-8 rounded-lg bg-amber-600 text-white mx-auto flex items-center justify-center text-xs font-bold mb-2">4</div>
                    <div class="text-xs font-bold text-slate-800">Proof & Sign-Off</div>
                    <div class="text-[10px] text-slate-500 mt-1">OPM photo verification and customer sign-on-glass BAST.</div>
                </div>
            </div>
        </div>

        <!-- 2. Dashboard & KPI Overview -->
        <div id="dashboard" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">2</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Dashboard & Operational KPI Metrics</h2>
                    <span class="text-xs text-slate-500">Instant situational awareness across all operational clusters</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The executive dashboard delivers real-time telemetry across 4 operational quadrants:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Pending Tasks</b> (Amber): New work orders awaiting technician assignment. Must be dispatched within SLA bounds to prevent breaches.</li>
                    <li><b>Active Tasks / On-Going</b> (Blue): Tasks currently in progress by field technicians at subscriber premises or splice enclosures.</li>
                    <li><b>Completed Tasks</b> (Green): Cumulative work orders successfully closed with verified photographic proof and signed acceptance certificates.</li>
                    <li><b>Online Fleet</b> (Emerald): Active technicians transmitting live GPS telemetry pings within the last 30 minutes.</li>
                </ul>
            </div>
        </div>

        <!-- 3. Task Management & Lifecycle -->
        <div id="tasks" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">3</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Ticket Lifecycle & Workflow SOP</h2>
                    <span class="text-xs text-slate-500">Standard operating procedure for ticket creation, routing, and closure</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <h4 class="font-bold text-slate-800">A. Creating a New Work Order:</h4>
                <ol class="list-decimal list-inside space-y-1 ml-2">
                    <li>Navigate to <b>Task Management</b> &rarr; click <b>+ Create Work Order</b>.</li>
                    <li>Input subscriber details (Full Name, Contact Phone, Physical Premise Address).</li>
                    <li>Set precise GPS coordinates (Latitude and Longitude in decimal format, e.g., `-6.2088, 106.8456`).</li>
                    <li>Assign priority level: <b>High</b> (Total service outage / fiber cut), <b>Medium</b> (Packet loss / degradation), or <b>Low</b> (Routine preventative inspection).</li>
                    <li>Select designated field technician (or leave unassigned for <i>pending</i> queue).</li>
                    <li>Click <b>Save Work Order</b>.</li>
                </ol>

                <h4 class="font-bold text-slate-800 mt-4">B. Work Order Status Definitions:</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 mt-2">
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">pending</span>
                        <p class="text-[11px] text-slate-600 mt-1">Ticket created; awaiting technician allocation.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">assigned</span>
                        <p class="text-[11px] text-slate-600 mt-1">Technician designated; awaiting acceptance in mobile terminal.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">accepted</span>
                        <p class="text-[11px] text-slate-600 mt-1">Technician confirmed readiness and initiated routing.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">on-going</span>
                        <p class="text-[11px] text-slate-600 mt-1">Technician checked in at subscriber location; repairs active.</p>
                    </div>
                    <div class="p-2.5 rounded-lg border border-slate-200 bg-slate-50 sm:col-span-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700">completed</span>
                        <p class="text-[11px] text-slate-600 mt-1">Repairs certified, optical proof uploaded, customer signature captured.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Live Tracking GPS Peta -->
        <div id="tracking" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-bold">4</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Live GPS Fleet Telemetry Map</h2>
                    <span class="text-xs text-slate-500">Real-time geospatial positioning and breadcrumb routes</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The <b>Live Tracking</b> view (`/tracking`) employs a high-performance Leaflet Geospatial Engine streaming updates dynamically without full-page refreshes:</p>
                <ul class="list-disc list-inside space-y-1.5 ml-2">
                    <li><b>Blue Marker</b>: Designates field technician on standby or idle status.</li>
                    <li><b>Gold Marker</b>: Designates technician actively managing an on-going dispatch.</li>
                    <li><b>Red Route Trail</b>: Displays chronological GPS breadcrumbs and routing history. Click technician in roster to isolate trail.</li>
                    <li><b>Telemetry Socket Feed</b>: Live event log records millisecond-precision coordinate pings and speed measurements.</li>
                </ul>
            </div>
        </div>

        <!-- 5. Audit Bukti Foto Redaman -->
        <div id="proof" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">5</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Proof of Work & Optical Audit Standards</h2>
                    <span class="text-xs text-slate-500">Quality assurance metrics for fiber splicing and physical termination</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>The platform enforces strict cryptographic proof requirements. Work orders cannot transition to completed without verifiable photographic evidence:</p>
                <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 space-y-2">
                    <div class="font-bold text-amber-900">Optical Power Meter (OPM) Loss Standards:</div>
                    <ul class="list-disc list-inside space-y-1 text-amber-800">
                        <li><b>Optimal:</b> -15.0 dBm to -20.0 dBm (Negligible attenuation, peak ONT operational stability).</li>
                        <li><b>Standard Compliant:</b> -20.1 dBm to -24.0 dBm (Acceptable carrier baseline with zero packet loss).</li>
                        <li><b>Critical / Degraded:</b> Exceeding -25.0 dBm (Risk of Red LOS, laser modulation failure, requeue required).</li>
                    </ul>
                </div>
                <p>If photographic evidence shows blurred OPM LCD screens or unverified readings, dispatchers must reject ticket closure until calibrated re-measurement is uploaded.</p>
            </div>
        </div>

        <!-- 6. Technician Fleet Management -->
        <div id="technicians" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold">6</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Technician Fleet & Shift Management</h2>
                    <span class="text-xs text-slate-500">Account provisioning, credential rotation, and shift supervision</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600 leading-relaxed">
                <p>Via the <b>Technicians</b> console (`/technicians`), administrators can:</p>
                <ul class="list-disc list-inside space-y-1 ml-2">
                    <li>Provision field technician credentials with role-based access control (RBAC).</li>
                    <li>Toggle operational status (Active / Standby / On Leave) for dynamic schedule planning.</li>
                    <li>Review historical completion velocity and photo audit compliance scores.</li>
                </ul>
            </div>
        </div>

        <!-- 7. FAQ & Troubleshooting -->
        <div id="faq" class="guide-section bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold">7</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Troubleshooting & Frequently Asked Questions (FAQ)</h2>
                    <span class="text-xs text-slate-500">Rapid resolution for common operational queries</span>
                </div>
            </div>

            <div class="space-y-3 text-xs text-slate-600">
                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Field technician cannot click "Complete Work Order" on terminal?</h4>
                    <p class="mt-1 text-slate-600">A: Verify that the technician has attached at least 1 verified photographic proof and obtained subscriber e-signature. The security guardrail strictly blocks completion to prevent unverified dispatch fraud.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: Technician marker is static or not updating on Live Tracking?</h4>
                    <p class="mt-1 text-slate-600">A: Ensure GPS location permissions are granted in the technician's mobile browser. Underground basements or dense shielding may attenuate GNSS satellite signals.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-3.5 bg-slate-50">
                    <h4 class="font-bold text-slate-800">Q: How do we configure map refresh intervals or dispatch alert webhooks?</h4>
                    <p class="mt-1 text-slate-600">A: Open <b>Settings</b> &rarr; configure polling intervals to ultra-realtime mode and input alert webhook endpoints for Slack, WhatsApp, or Telegram integration.</p>
                </div>
            </div>
        </div>

        <!-- Back to Top / Footer Navigation -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200 text-xs">
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 font-semibold flex items-center gap-1">
                &larr; Back to Admin Dashboard
            </a>
            <a href="{{ route('admin.settings') }}" class="text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1">
                Open Platform Settings &rarr;
            </a>
        </div>

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('guide-search');
        const sections = document.querySelectorAll('.guide-section');
        const navItems = document.querySelectorAll('.guide-nav-item');

        // Smooth scrolling for TOC items
        navItems.forEach(item => {
            item.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId.startsWith('#')) {
                    const el = document.querySelector(targetId);
                    if (el) {
                        e.preventDefault();
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });

        // Quick client-side search in guide
        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                sections.forEach(sec => {
                    const text = sec.innerText.toLowerCase();
                    if (!query || text.includes(query)) {
                        sec.style.display = '';
                    } else {
                        sec.style.display = 'none';
                    }
                });
            });
        }

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
