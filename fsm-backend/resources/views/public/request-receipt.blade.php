<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Confirmed #TSK-{{ str_pad($task->id, 4, '0', STR_PAD_LEFT) }} — {{ $settings['company_name'] ?? 'FiberOps' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background-color: #F8FAFC; color: #0F172A; }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 antialiased">
@php
    $companyName  = $settings['company_name'] ?? 'FiberOps Global Carrier';
    $supportPhone = $settings['support_phone'] ?? '+1-800-555-0199';
    $ticketCode   = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
    $trackingUrl  = route('customer.tracking', $task->tracking_token);
    $pollApiUrl   = url('/api/public/track/' . $task->tracking_token);
@endphp

<div class="max-w-xl w-full bg-white rounded-3xl border border-slate-200/80 shadow-xl overflow-hidden">
    <!-- Top Banner -->
    <div class="bg-slate-900 text-white p-6 sm:p-7 text-center space-y-2">
        <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-400 mx-auto flex items-center justify-center">
            <ion-icon name="checkmark-circle" class="text-3xl"></ion-icon>
        </div>
        <span class="inline-block text-[10px] font-bold uppercase tracking-widest text-emerald-300">Request Received by NOC</span>
        <h1 class="text-2xl font-extrabold tracking-tight">Ticket {{ $ticketCode }} Confirmed</h1>
        <p class="text-xs text-slate-400 max-w-md mx-auto">
            Your service request has entered our live NOC dispatch queue. Save your tracking link below to follow technician assignment &amp; arrival in real time.
        </p>
    </div>

    <!-- Body -->
    <div class="p-6 sm:p-7 space-y-5">
        <!-- Live Status Box -->
        <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 flex items-center justify-between gap-3">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-blue-600">Current Dispatch Status (Auto-Live)</div>
                <div id="receipt-status-label" class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $task->status_label }}</div>
                <div id="receipt-tech-label" class="text-xs text-slate-600 mt-0.5">
                    Technician: {{ $task->technician ? \Illuminate\Support\Str::before($task->technician->name, ' ') : 'Awaiting NOC Dispatch' }}
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white border border-blue-200 text-blue-700 text-[11px] font-bold">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                Live
            </span>
        </div>

        <!-- Ticket Metadata Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Service Category</span>
                <span class="font-bold text-slate-800 mt-0.5 block">{{ $task->service_category_label }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Customer</span>
                <span class="font-bold text-slate-800 mt-0.5 block">{{ $task->customer_name }} ({{ $task->customer_phone }})</span>
            </div>
            <div class="sm:col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-200/70">
                <span class="text-[10px] font-bold uppercase text-slate-400 block">Location</span>
                <span class="font-medium text-slate-700 mt-0.5 block">{{ $task->resolved_location }}</span>
            </div>
        </div>

        @if($task->client_photo_url)
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center gap-3">
                <img src="{{ $task->client_photo_url }}" alt="Attached Issue Photo" class="w-16 h-16 rounded-xl object-cover border border-slate-200 flex-shrink-0">
                <div class="text-xs">
                    <div class="font-bold text-slate-800">Attached Issue Photo Verified</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Visible immediately to the NOC dispatcher and assigned field engineer.</div>
                </div>
            </div>
        @endif

        <!-- Shareable / Copyable Live Tracking Link -->
        <div class="space-y-2">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">Your Live Technician Tracking Link</label>
            <div class="flex items-center gap-2">
                <input id="live-track-url" type="text" readonly value="{{ $trackingUrl }}"
                    class="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-mono text-slate-700">
                <button type="button" id="btn-copy-link"
                    class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold flex items-center gap-1 cursor-pointer">
                    <ion-icon name="copy-outline" class="text-sm"></ion-icon>
                    <span>Copy</span>
                </button>
            </div>
        </div>

        <!-- Primary Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            <a href="{{ $trackingUrl }}"
                class="min-h-[44px] py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md shadow-blue-600/20 transition-all">
                <ion-icon name="navigate-outline" class="text-base"></ion-icon>
                <span>Open Live Tracking</span>
            </a>
            <a href="{{ route('public.request.create') }}"
                class="min-h-[44px] py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs flex items-center justify-center gap-1.5 transition-all">
                <ion-icon name="add-circle-outline" class="text-base"></ion-icon>
                <span>Submit Another Request</span>
            </a>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
            <span>{{ $companyName }}</span>
            <span>Need urgent help? <a href="tel:{{ $supportPhone }}" class="text-blue-600 font-bold hover:underline">{{ $supportPhone }}</a></span>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var copyBtn = document.getElementById('btn-copy-link');
        var urlInput = document.getElementById('live-track-url');

        function copyTextSafe(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                return navigator.clipboard.writeText(text).catch(function() {
                    return fallbackCopy(text);
                });
            }
            return fallbackCopy(text);
        }

        function fallbackCopy(text) {
            return new Promise(function(resolve) {
                try {
                    var ta = document.createElement('textarea');
                    ta.value = text;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                } catch (e) {}
                resolve();
            });
        }

        copyBtn?.addEventListener('click', function() {
            urlInput.select();
            copyTextSafe(urlInput.value).then(function() {
                copyBtn.innerHTML = '<ion-icon name="checkmark-outline" class="text-sm"></ion-icon><span>Copied!</span>';
                setTimeout(function() {
                    copyBtn.innerHTML = '<ion-icon name="copy-outline" class="text-sm"></ion-icon><span>Copy</span>';
                }, 2000);
            });
        });

        // Live-poll public status every 5 seconds until terminal state (completed/rejected/expired)
        var pollUrl = @json($pollApiUrl);
        var statusEl = document.getElementById('receipt-status-label');
        var techEl = document.getElementById('receipt-tech-label');
        var isPolling = false;
        var pollTimer = null;

        async function pollReceiptStatus() {
            if (document.hidden || isPolling) return;
            isPolling = true;
            try {
                var resp = await fetch(pollUrl + '?_ts=' + Date.now(), { headers: { 'Accept': 'application/json' } });
                if (resp.status === 404 || resp.status === 410) {
                    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                    return;
                }
                if (!resp.ok) return;
                var data = await resp.json();
                if (data.task && statusEl) {
                    statusEl.textContent = data.task.status_label || data.task.status;
                }
                if (data.technician && techEl) {
                    techEl.textContent = 'Technician: ' + (data.technician.name || 'Awaiting NOC Dispatch');
                }
                if (data.task && (data.task.status === 'completed' || data.task.status === 'rejected')) {
                    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
                }
            } catch (e) {
            } finally {
                isPolling = false;
            }
        }

        pollTimer = setInterval(pollReceiptStatus, 5000);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && pollTimer) pollReceiptStatus();
        });
    });
</script>
</body>
</html>
