@extends('layouts.admin')

@section('title', 'Platform & System Settings')
@section('header', 'Platform Settings')

@section('content')
@php
    $hasErrors = $errors->any();
    $val = fn (string $key, mixed $fallback = '') => old($key, $settings[$key] ?? $fallback);
    $checked = fn (string $key, string $default = '0') => $hasErrors
        ? (old($key) ? 'checked' : '')
        : ((($settings[$key] ?? $default) == '1') ? 'checked' : '');
@endphp

<div class="max-w-4xl mx-auto space-y-6">

    <!-- Top Banner: Shortcut to Admin Operations Manual -->
    <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-5 sm:p-6 text-white shadow-lg border border-blue-700/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-xl bg-amber-400/20 border border-amber-400/30 flex items-center justify-center text-amber-300 flex-shrink-0">
                <ion-icon name="book" class="text-2xl"></ion-icon>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300 bg-amber-400/10 px-2.5 py-0.5 rounded-full border border-amber-400/20">
                        Complete Documentation
                    </span>
                    <span class="text-[10px] text-slate-300">v2.0 Operations Manual</span>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-white mt-1">FiberOps Admin Operations Manual</h3>
                <p class="text-xs text-slate-300 mt-0.5 max-w-xl">
                    Learn work-order dispatching SOPs, 1-click GPS auto-dispatch, live fleet telemetry, atomic inventory ledger, SHA-256 BAST audit, and Android APK background tracking.
                </p>
            </div>
        </div>
        <a href="{{ route('admin.guide') }}" class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs transition-all shadow-md active:scale-95 flex items-center gap-2 flex-shrink-0 relative z-10">
            <span>Open Operations Manual</span>
            <ion-icon name="arrow-forward-outline" class="text-sm"></ion-icon>
        </a>
    </div>

    <!-- Validation Error Alert (Success banner is rendered globally in layouts/admin.blade.php) -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs shadow-sm space-y-1.5" role="alert">
            <div class="flex items-center gap-2 font-bold text-red-900">
                <ion-icon name="alert-circle" class="text-lg text-red-600 flex-shrink-0"></ion-icon>
                <span>Please correct the following configuration errors before saving:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pl-6 text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Settings Form -->
    <div class="bg-white rounded-2xl border border-fsmBorder p-6 sm:p-8 shadow-sm">
        <div class="mb-6 border-b border-fsmBorder pb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-textPrimary">System Configuration &amp; Parameters</h2>
                <p class="text-xs text-textSecondary">Configure platform identity, field SLA guardrails, webhook notification channels, and database retention policies.</p>
            </div>
            <span class="text-[11px] font-mono text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-lg flex-shrink-0">
                FSM Core v2.0
            </span>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            <div class="space-y-8">

                <!-- 1. Organization Details -->
                <div>
                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <ion-icon name="business-outline" class="text-sm"></ion-icon>
                        1. Organization &amp; Carrier Identity
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Company / Carrier Name</label>
                            <input id="company_name" type="text" name="company_name" value="{{ $val('company_name') }}" required
                                class="w-full rounded-xl border @error('company_name') border-red-400 bg-red-50/30 @else border-slate-200 @enderror px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                            <span class="text-[10px] text-slate-400 mt-1 block">Displayed on BAST certificates, Operations Manual, and Technician APK profile.</span>
                        </div>
                        <div>
                            <label for="support_email" class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Support Email</label>
                            <input id="support_email" type="email" name="support_email" value="{{ $val('support_email') }}" required
                                class="w-full rounded-xl border @error('support_email') border-red-400 bg-red-50/30 @else border-slate-200 @enderror px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        </div>
                        <div>
                            <label for="support_phone" class="block text-xs font-semibold text-slate-700 mb-1.5">Field Operations Hotline (NOC Desk)</label>
                            <input id="support_phone" type="text" name="support_phone" value="{{ $val('support_phone') }}" required
                                class="w-full rounded-xl border @error('support_phone') border-red-400 bg-red-50/30 @else border-slate-200 @enderror px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="currency" class="block text-xs font-semibold text-slate-700 mb-1.5">Display Currency</label>
                                <select id="currency" name="currency" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                    <option value="USD" {{ $val('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ $val('currency', 'USD') == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="GBP" {{ $val('currency', 'USD') == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="IDR" {{ $val('currency', 'USD') == 'IDR' ? 'selected' : '' }}>IDR (Rp)</option>
                                </select>
                            </div>
                            <div>
                                <label for="default_country_code" class="block text-xs font-semibold text-slate-700 mb-1.5">Default Country Dial Code</label>
                                <input id="default_country_code" type="text" name="default_country_code" value="{{ $val('default_country_code', '62') }}" maxlength="6" pattern="[+]?[0-9]{1,5}" placeholder="62"
                                    class="w-full rounded-xl border @error('default_country_code') border-red-400 bg-red-50/30 @else border-slate-200 @enderror px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary transition-all">
                                <span class="text-[10px] text-slate-400 mt-1 block">Digits only (e.g., 62 for Indonesia, 1 for US).</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Dispatch & Tracking Engine -->
                <div class="border-t border-fsmBorder pt-6">
                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <ion-icon name="navigate-outline" class="text-sm"></ion-icon>
                        2. Dispatching Engine &amp; Live GPS Telemetry
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="gps_refresh_interval" class="block text-xs font-semibold text-slate-700 mb-1.5">Map GPS Polling Interval (Seconds)</label>
                            <select id="gps_refresh_interval" name="gps_refresh_interval"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="5" {{ $val('gps_refresh_interval', '15') == '5' ? 'selected' : '' }}>5 Seconds (Ultra Real-Time)</option>
                                <option value="15" {{ $val('gps_refresh_interval', '15') == '15' ? 'selected' : '' }}>15 Seconds (Recommended Standard)</option>
                                <option value="30" {{ $val('gps_refresh_interval', '15') == '30' ? 'selected' : '' }}>30 Seconds (Bandwidth Saver)</option>
                                <option value="60" {{ $val('gps_refresh_interval', '15') == '60' ? 'selected' : '' }}>60 Seconds (1 Minute)</option>
                                <option value="300" {{ $val('gps_refresh_interval', '15') == '300' ? 'selected' : '' }}>300 Seconds (5 Minutes)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Applies to Admin Live Tracking adaptive delta polling and Technician APK GPS sync cadence.</span>
                        </div>

                        <div>
                            <label for="map_style" class="block text-xs font-semibold text-slate-700 mb-1.5">Leaflet Map Tile Layer Style</label>
                            <select id="map_style" name="map_style"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="openstreetmap" {{ $val('map_style', 'openstreetmap') == 'openstreetmap' ? 'selected' : '' }}>OpenStreetMap Standard</option>
                                <option value="carto" {{ $val('map_style', 'openstreetmap') == 'carto' ? 'selected' : '' }}>CartoDB Positron (Minimalist)</option>
                                <option value="mapbox" {{ $val('map_style', 'openstreetmap') == 'mapbox' ? 'selected' : '' }}>Satellite Hybrid Layer (Esri World Imagery)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Default basemap for Admin Dashboard, Live Tracking, Task Map Picker, and APK Route Map.</span>
                        </div>

                        <div class="sm:col-span-2 mt-1">
                            <label class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer select-none hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="auto_assign" value="1"
                                    class="w-4 h-4 mt-0.5 text-blue-600 border-slate-300 rounded focus:ring-primary cursor-pointer"
                                    {{ $checked('auto_assign', '1') }}>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Enable Proximity-Based Auto-Dispatch</span>
                                    <span class="text-[11px] text-slate-500">Automatically assigns the nearest available technician when a new work order with GPS coordinates is created without an assignee.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. Operational SLA & Task Guardrails -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="shield-checkmark-outline" class="text-sm"></ion-icon>
                            3. Field SLA Guardrails &amp; Operational Integrity
                        </h3>
                        <span class="text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded font-semibold">Enforced by FSM</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sla_warning_hours" class="block text-xs font-semibold text-slate-700 mb-1.5">Ticket SLA Warning Threshold (Target Resolution Time)</label>
                            <select id="sla_warning_hours" name="sla_warning_hours"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="1" {{ $val('sla_warning_hours', '2') == '1' ? 'selected' : '' }}>1 Hour (Critical / Enterprise Mission-Critical SLA)</option>
                                <option value="2" {{ $val('sla_warning_hours', '2') == '2' ? 'selected' : '' }}>2 Hours (Standard Fiber Degradation)</option>
                                <option value="4" {{ $val('sla_warning_hours', '2') == '4' ? 'selected' : '' }}>4 Hours (Standard Provisioning)</option>
                                <option value="8" {{ $val('sla_warning_hours', '2') == '8' ? 'selected' : '' }}>8 Hours (Next-Business-Day Resolution)</option>
                                <option value="24" {{ $val('sla_warning_hours', '2') == '24' ? 'selected' : '' }}>24 Hours (Scheduled Maintenance)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Open work orders are flagged with SLA breach alerts when unresolved past this window.</span>
                        </div>

                        <div>
                            <label for="geofence_check_meters" class="block text-xs font-semibold text-slate-700 mb-1.5">Customer Location Geofencing Radius Tolerance</label>
                            <select id="geofence_check_meters" name="geofence_check_meters"
                                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-white">
                                <option value="0" {{ $val('geofence_check_meters', '0') == '0' ? 'selected' : '' }}>Disabled (Unrestricted Check-in)</option>
                                <option value="50" {{ $val('geofence_check_meters', '0') == '50' ? 'selected' : '' }}>50 Meters (Strict Urban)</option>
                                <option value="100" {{ $val('geofence_check_meters', '0') == '100' ? 'selected' : '' }}>100 Meters (Standard Recommended)</option>
                                <option value="250" {{ $val('geofence_check_meters', '0') == '250' ? 'selected' : '' }}>250 Meters (Campus / Rural)</option>
                                <option value="500" {{ $val('geofence_check_meters', '0') == '500' ? 'selected' : '' }}>500 Meters (Wide Area Backbone)</option>
                            </select>
                            <span class="text-[10px] text-slate-400 mt-1 block">Blocks technician completion if latest GPS fix is outside the subscriber premise radius.</span>
                        </div>

                        <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/60 border border-emerald-200 cursor-pointer select-none hover:bg-emerald-50 transition-colors">
                                <input type="checkbox" name="require_proof_photo" value="1"
                                    class="w-4 h-4 mt-0.5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 cursor-pointer"
                                    {{ $checked('require_proof_photo', '1') }}>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Mandatory Photo Proof Gate</span>
                                    <span class="text-[11px] text-slate-500">Prevents technicians from closing work orders without photographic proof of work.</span>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 rounded-xl bg-emerald-50/60 border border-emerald-200 cursor-pointer select-none hover:bg-emerald-50 transition-colors">
                                <input type="checkbox" name="require_customer_signature" value="1"
                                    class="w-4 h-4 mt-0.5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 cursor-pointer"
                                    {{ $checked('require_customer_signature', '1') }}>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Mandatory Customer E-Signature Gate</span>
                                    <span class="text-[11px] text-slate-500">Requires customer Sign-on-Glass digital signature before sealing BAST completion.</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 4. Webhook Alert Dispatcher -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="send-outline" class="text-sm"></ion-icon>
                            4. Webhook Alerts &amp; Dispatch Notification Channels
                        </h3>
                        <span class="text-[10px] bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded font-semibold">SSRF Guarded</span>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="webhook_url_input" class="text-xs font-semibold text-slate-700">Webhook Endpoint URL (Bot Notification Hook)</label>
                                <span class="text-[10px] text-slate-400">Public HTTPS/HTTP POST endpoint only</span>
                            </div>
                            <div class="flex gap-2">
                                <input type="url" id="webhook_url_input" name="webhook_url" value="{{ $val('webhook_url') }}" placeholder="https://hooks.slack.com/services/... or https://api.telegram.org/bot.../sendMessage"
                                    class="flex-1 rounded-xl border @error('webhook_url') border-red-400 bg-red-50/30 @else border-slate-200 @enderror px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all">
                                <button type="button" id="btn-test-webhook" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition-all active:scale-95 flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                                    <ion-icon name="flash-outline" class="text-sm"></ion-icon>
                                    Test Webhook
                                </button>
                            </div>
                            <span id="webhook-feedback" class="text-[11px] mt-1.5 hidden block"></span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_emergency_tickets" value="1" class="rounded text-indigo-600" {{ $checked('notify_emergency_tickets', '1') }}>
                                <span>Dispatch Alerts for High/Emergency Priority Tickets</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_task_completed" value="1" class="rounded text-indigo-600" {{ $checked('notify_task_completed', '1') }}>
                                <span>Notify NOC when Technicians Complete Work Orders</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_customer_whatsapp" value="1" class="rounded text-emerald-600" {{ $checked('notify_customer_whatsapp', '0') }}>
                                <span>Auto-Send Customer Live Tracking via WhatsApp Gateway</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="notify_customer_email" value="1" class="rounded text-blue-600" {{ $checked('notify_customer_email', '0') }}>
                                <span>Auto-Send Customer Receipt &amp; BAST via SMTP Email</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">WhatsApp Gateway Endpoint URL</label>
                                <input type="url" name="whatsapp_gateway_url" value="{{ $val('whatsapp_gateway_url') }}" placeholder="https://api.fonnte.com/send"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">WhatsApp Gateway Token</label>
                                <input type="password" name="whatsapp_gateway_token" value="{{ $val('whatsapp_gateway_token') }}" placeholder="Bearer / API Token"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Firebase FCM Server Key (Native Push)</label>
                                <input type="password" name="fcm_server_key" value="{{ $val('fcm_server_key') }}" placeholder="FCM Push Key (Optional)"
                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary">
                            </div>
                        </div>

                        @if(isset($recentNotificationLogs) && $recentNotificationLogs->count() > 0)
                        <div class="mt-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">Recent Async Notification Delivery Log (Last {{ $recentNotificationLogs->count() }})</div>
                            <div class="space-y-1.5 max-h-40 overflow-y-auto text-[11px]">
                                @foreach($recentNotificationLogs as $nlog)
                                    <div class="flex items-center justify-between gap-2 bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <div class="truncate">
                                            <span class="font-mono font-bold uppercase text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700">{{ $nlog->channel }}</span>
                                            <span class="font-semibold text-slate-800 ml-1">{{ $nlog->event }}</span>
                                            <span class="text-slate-400 ml-1">{{ $nlog->recipient }}</span>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $nlog->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : ($nlog->status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                            {{ strtoupper($nlog->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- 5. System Optimization & Telemetry Retention -->
                <div class="border-t border-fsmBorder pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-amber-600 uppercase tracking-wider flex items-center gap-2">
                            <ion-icon name="server-outline" class="text-sm"></ion-icon>
                            5. Database Maintenance &amp; GPS Telemetry Retention
                        </h3>
                        <span class="text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded font-semibold">Daily Scheduled Prune</span>
                    </div>

                    <div class="bg-amber-50/50 border border-amber-200/80 rounded-xl p-4 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase">Total GPS Telemetry Logs</div>
                                <div id="gps-total-points" class="text-base font-bold text-slate-800 mt-0.5">{{ number_format($totalGpsPoints) }} Points</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <div class="text-[10px] font-semibold text-slate-400 uppercase">Oldest Telemetry Ping</div>
                                <div id="gps-oldest-ping" class="text-sm font-bold text-slate-800 mt-1">{{ $databaseSize }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-amber-100">
                                <label for="telemetry_retention_days_select" class="text-[10px] font-semibold text-slate-400 uppercase block">Automated Retention Window</label>
                                <select id="telemetry_retention_days_select" name="telemetry_retention_days" class="text-xs font-bold text-slate-800 bg-transparent outline-none w-full mt-1">
                                    <option value="7" {{ $val('telemetry_retention_days', '30') == '7' ? 'selected' : '' }}>7 Days</option>
                                    <option value="14" {{ $val('telemetry_retention_days', '30') == '14' ? 'selected' : '' }}>14 Days</option>
                                    <option value="30" {{ $val('telemetry_retention_days', '30') == '30' ? 'selected' : '' }}>30 Days (Default)</option>
                                    <option value="90" {{ $val('telemetry_retention_days', '30') == '90' ? 'selected' : '' }}>90 Days</option>
                                    <option value="365" {{ $val('telemetry_retention_days', '30') == '365' ? 'selected' : '' }}>365 Days (1 Year)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                            <span class="text-[11px] text-slate-600">
                                Purge GPS breadcrumbs older than the selected retention window. The <strong>latest 50 points per technician</strong> are always safeguarded.
                            </span>
                            <button type="button" id="btn-purge-cache" class="px-3.5 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition-all active:scale-95 flex items-center justify-center gap-1.5 flex-shrink-0 cursor-pointer">
                                <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                                Purge Stale Logs
                            </button>
                        </div>
                        <span id="purge-feedback" class="text-[11px] hidden block"></span>
                    </div>
                </div>

            </div>

            <!-- Submit Button Bar -->
            <div class="mt-8 pt-5 border-t border-fsmBorder flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs text-slate-400">
                    Changes take effect immediately across all dispatcher consoles and sync to field APK terminals on next refresh.
                </span>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-500/20 transition-all active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
                    <ion-icon name="save-outline" class="text-sm"></ion-icon>
                    Save All Platform Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- In-Page Confirmation Modal for GPS Telemetry Purge (Replaces blocked window.confirm) -->
<div id="purge-confirm-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                <ion-icon name="warning-outline" class="text-xl"></ion-icon>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Confirm GPS Telemetry Purge</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Delete GPS telemetry breadcrumbs older than <strong id="purge-modal-days" class="text-slate-900">30 days</strong>?
                    The <strong>latest 50 GPS breadcrumbs per technician</strong> will remain safeguarded so active route trails stay intact.
                </p>
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" id="btn-purge-cancel" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold cursor-pointer">
                Cancel
            </button>
            <button type="button" id="btn-purge-confirm" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                <ion-icon name="trash-outline" class="text-sm"></ion-icon>
                Yes, Purge Stale Logs
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // 1. AJAX Test Webhook
        const btnTestWebhook = document.getElementById('btn-test-webhook');
        const webhookInput = document.getElementById('webhook_url_input');
        const webhookFeedback = document.getElementById('webhook-feedback');

        if (btnTestWebhook) {
            btnTestWebhook.addEventListener('click', async function() {
                btnTestWebhook.disabled = true;
                btnTestWebhook.innerHTML = '<ion-icon name="sync" class="animate-spin"></ion-icon> Testing...';

                try {
                    const resp = await fetch("{{ route('admin.settings.test_webhook') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ webhook_url: webhookInput?.value || '' })
                    });
                    const res = await resp.json().catch(() => ({}));
                    if (!resp.ok || res.status === 'error') {
                        webhookFeedback.className = 'text-[11px] mt-1.5 block text-red-600 font-semibold';
                        webhookFeedback.textContent = '❌ ' + (res.message || ('Webhook test failed (HTTP ' + resp.status + ').'));
                    } else {
                        webhookFeedback.className = 'text-[11px] mt-1.5 block text-emerald-600 font-semibold';
                        webhookFeedback.textContent = '✅ ' + res.message;
                    }
                } catch (e) {
                    webhookFeedback.className = 'text-[11px] mt-1.5 block text-red-600 font-semibold';
                    webhookFeedback.textContent = '❌ Failed to dispatch test webhook: ' + e.message;
                } finally {
                    btnTestWebhook.disabled = false;
                    btnTestWebhook.innerHTML = '<ion-icon name="flash-outline" class="text-sm"></ion-icon> Test Webhook';
                }
            });
        }

        // 2. AJAX Purge Telemetry Cache with In-Page Modal Confirmation
        const btnPurge = document.getElementById('btn-purge-cache');
        const purgeFeedback = document.getElementById('purge-feedback');
        const retentionSelect = document.getElementById('telemetry_retention_days_select');
        const totalPointsEl = document.getElementById('gps-total-points');
        const oldestPingEl = document.getElementById('gps-oldest-ping');
        const purgeModal = document.getElementById('purge-confirm-modal');
        const purgeModalDays = document.getElementById('purge-modal-days');
        const btnPurgeCancel = document.getElementById('btn-purge-cancel');
        const btnPurgeConfirm = document.getElementById('btn-purge-confirm');

        function closePurgeModal() {
            if (purgeModal) purgeModal.classList.add('hidden');
        }

        if (btnPurge && purgeModal) {
            btnPurge.addEventListener('click', function() {
                const days = retentionSelect?.value || '30';
                if (purgeModalDays) {
                    purgeModalDays.textContent = days + ' days';
                }
                purgeModal.classList.remove('hidden');
            });

            btnPurgeCancel?.addEventListener('click', closePurgeModal);
            purgeModal.addEventListener('click', function(e) {
                if (e.target === purgeModal) closePurgeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !purgeModal.classList.contains('hidden')) {
                    closePurgeModal();
                }
            });

            btnPurgeConfirm?.addEventListener('click', async function() {
                const retentionDays = retentionSelect?.value || '30';
                closePurgeModal();

                btnPurge.disabled = true;
                btnPurge.innerHTML = '<ion-icon name="sync" class="animate-spin"></ion-icon> Purging...';

                try {
                    const resp = await fetch("{{ route('admin.settings.purge_cache') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ telemetry_retention_days: retentionDays })
                    });
                    const res = await resp.json().catch(() => ({}));

                    if (!resp.ok || res.status === 'error') {
                        purgeFeedback.className = 'text-[11px] block text-red-600 font-semibold mt-2';
                        purgeFeedback.textContent = '❌ ' + (res.message || ('Telemetry purge failed (HTTP ' + resp.status + ').'));
                    } else {
                        purgeFeedback.className = 'text-[11px] block text-emerald-700 font-semibold mt-2';
                        purgeFeedback.textContent = '✅ ' + res.message;

                        if (totalPointsEl && typeof res.total_remaining === 'number') {
                            totalPointsEl.textContent = res.total_remaining.toLocaleString() + ' Points';
                        }
                        if (oldestPingEl && res.oldest_ping_label) {
                            oldestPingEl.textContent = res.oldest_ping_label;
                        }
                    }
                } catch (e) {
                    purgeFeedback.className = 'text-[11px] block text-red-600 font-semibold mt-2';
                    purgeFeedback.textContent = '❌ Failed to purge telemetry logs: ' + e.message;
                } finally {
                    btnPurge.disabled = false;
                    btnPurge.innerHTML = '<ion-icon name="trash-outline" class="text-sm"></ion-icon> Purge Stale Logs';
                }
            });
        }
    });
</script>
@endpush
@endsection
