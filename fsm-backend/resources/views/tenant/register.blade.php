<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Start Your 14-Day ISP Cloud Trial or Book $2,500 Turnkey Deployment | Benteng Siber</title>
    <meta name="description" content="Launch an isolated Zero-Trust ISP Field Service Management tenant in 60 seconds ($250–$500/mo with 14-day free trial) or book a $2,500 Dedicated White-Label Project delivered in 7 days.">
    <meta name="theme-color" content="#0f3559">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('build/app.css') }}?v=20261123v5">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f6f7f9;
            color: #1e293b;
        }
        .font-display { font-family: 'Plus Jakarta Sans', sans-serif; }
        .auth-dot-bg {
            background-image: radial-gradient(rgba(15, 53, 89, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
        }
    </style>
</head>
<body class="min-h-screen auth-dot-bg flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    @php
        $selectedPlan = in_array(request()->query('plan'), ['starter', 'growth'], true)
            ? request()->query('plan')
            : old('plan', 'growth');
        $autoOpenProjectModal = request()->query('inquiry') === 'project' || $errors->has('enterprise_company') || $errors->has('enterprise_email');
        $lead = session('enterprise_lead');
        $waText = $lead
            ? "Hi Benteng Siber Architect! We just submitted a \$2,500 Dedicated Turnkey Project booking for {$lead['company']} ({$lead['techs']} techs, {$lead['routers']} routers, target: {$lead['deployment']}). Let's schedule our kickoff!"
            : "Hi Benteng Siber Architect! I'd like to discuss the \$2,500 Dedicated Turnkey FiberOps deployment (7-day delivery) for my ISP.";
    @endphp

    <!-- Top Navigation Header -->
    <header class="max-w-6xl w-full mx-auto flex items-center justify-between pb-6 border-b border-slate-200">
        <a href="https://bentengsiber.com/" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0f3559] to-[#011835] flex items-center justify-center text-white shadow-md">
                <svg class="w-5 h-5 text-[#e8913a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <span class="font-display font-extrabold text-xl text-[#0f3559] tracking-tight">Benteng Siber</span>
                <span class="block text-xs font-semibold text-[#e8913a] tracking-wider uppercase">Global FiberOps Cloud</span>
            </div>
        </a>

        <div class="flex items-center gap-3">
            <a href="https://bentengsiber.com/proposal.html" target="_blank" rel="noopener noreferrer" class="hidden md:inline-flex items-center px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-600 hover:text-[#0f3559] hover:bg-white border border-transparent hover:border-slate-200 transition-colors">
                Executive Proposal (PDF)
            </a>
            <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg text-xs font-bold text-[#0f3559] bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Sign In Console &rarr;
            </a>
        </div>
    </header>

    <!-- Main Registration Box -->
    <main class="max-w-5xl w-full mx-auto my-8 bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="p-6 sm:p-10 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Instant 14-Day Free Cloud Trial &bull; No Credit Card Required
                </div>
                <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-[#0f3559] tracking-tight">
                    Provision Your Zero-Trust ISP Command Center
                </h1>
                <p class="text-sm text-slate-600 mt-2 max-w-2xl">
                    Deploy multi-router MikroTik RouterOS automation, OLT/GenieACS optical gates, Stripe/QRIS 3-second auto-unblock, offline-first Technician PWA, and SHA-256 public BAST certificates in under 60 seconds.
                </p>
            </div>
            <div class="shrink-0 bg-[#011835] text-white rounded-xl p-4 border border-[#e8913a]/40 max-w-xs">
                <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-[#f4b168]">Want 100% White-Label Ownership?</div>
                <div class="font-display font-extrabold text-lg mt-0.5">$2,500 Dedicated Project</div>
                <p class="text-[11px] text-slate-300 mt-1">Your own server, custom domain, signed Android APK &amp; 7-day architect handover.</p>
                <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.remove('hidden')"
                    class="mt-3 w-full py-2 px-3 rounded-lg bg-[#e8913a] hover:bg-[#d07e2e] text-[#011835] text-xs font-extrabold transition-all cursor-pointer">
                    Book $2,500 Turnkey Deployment &rarr;
                </button>
            </div>
        </div>

        @if (session('enterprise_success'))
            <div class="m-6 sm:m-10 mb-0 p-5 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-950 text-sm flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                <div>
                    <strong class="font-display font-extrabold text-base block text-emerald-900">Dedicated $2,500 Project Slot Reserved!</strong>
                    <span class="text-xs text-emerald-800 block mt-1">{{ session('enterprise_success') }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                    <a href="https://wa.me/6281223554539?text={{ urlencode($waText) }}" target="_blank" rel="noopener noreferrer"
                        class="px-4 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold whitespace-nowrap shadow-sm">
                        Fast-Track on WhatsApp &rarr;
                    </a>
                    <a href="mailto:leoallogne@gmail.com?subject={{ urlencode('[Benteng Siber] $2,500 Dedicated ISP Project Kickoff') }}&body={{ urlencode($waText) }}"
                        class="px-3.5 py-2.5 rounded-lg bg-white hover:bg-emerald-100 text-emerald-900 border border-emerald-300 text-xs font-bold whitespace-nowrap">
                        Email Architect
                    </a>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="m-6 sm:m-10 mb-0 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <strong class="font-bold flex items-center gap-1.5 mb-1">Please check the registration details:</strong>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tenant.register.post') }}" method="POST" class="p-6 sm:p-10 space-y-8">
            @csrf

            <!-- Section 1: ISP Organization Identity -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">1</span>
                    ISP Organization &amp; Workspace Currency
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="company_name">
                            Company / ISP Name *
                        </label>
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required
                            placeholder="e.g. Apex Fiber Networks LLC"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="slug">
                            Workspace Subdomain *
                        </label>
                        <div class="flex items-center">
                            <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required
                                placeholder="apex-fiber"
                                class="w-full px-3.5 py-2.5 rounded-l-lg border border-r-0 border-slate-300 text-sm lowercase focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all font-mono">
                            <span class="px-2.5 py-2.5 bg-slate-100 border border-slate-300 rounded-r-lg text-[11px] font-medium text-slate-500 whitespace-nowrap">
                                .bentengsiber.com
                            </span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="currency">
                            Billing &amp; BOM Currency *
                        </label>
                        <select id="currency" name="currency" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm bg-white focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                            <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD ($) — Global Default</option>
                            <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (&euro;) — Europe</option>
                            <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (&pound;) — United Kingdom</option>
                            <option value="AUD" {{ old('currency') === 'AUD' ? 'selected' : '' }}>AUD (A$) — Australia</option>
                            <option value="IDR" {{ old('currency') === 'IDR' ? 'selected' : '' }}>IDR (Rp) — Indonesia</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Select Cloud SaaS Plan or Book $2,500 Dedicated Project -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">2</span>
                    Select Deployment Plan (Instant 14-Day Cloud Trial or $2,500 Turnkey Project)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Starter Cloud ($250/mo) -->
                    <label class="relative border-2 rounded-xl p-5 cursor-pointer hover:border-[#0f3559] transition-all has-[:checked]:border-[#0f3559] has-[:checked]:bg-blue-50/40 flex flex-col justify-between">
                        <div>
                            <input type="radio" name="plan" value="starter" class="sr-only" {{ $selectedPlan === 'starter' ? 'checked' : '' }}>
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="font-display font-bold text-sm text-[#0f3559] block">Starter ISP Cloud</span>
                                    <span class="text-[11px] text-slate-500">Local ISPs &amp; Growing WISPs</span>
                                </div>
                                <span class="text-sm font-extrabold text-[#e8913a] whitespace-nowrap">$250<span class="text-[10px] font-semibold text-slate-500">/mo</span></span>
                            </div>
                            <ul class="mt-3.5 space-y-1.5 text-xs text-slate-600 border-t border-slate-200/70 pt-3">
                                <li class="font-semibold text-slate-800">&#10003; Up to 10 Field Technicians</li>
                                <li>&#10003; Up to 100 GIS ODP/ODC Nodes</li>
                                <li>&#10003; 5-Role RBAC &amp; AES-256 Vault</li>
                                <li>&#10003; Stripe/QRIS &amp; SHA-256 Public BAST</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-2 border-t border-slate-100 text-[11px] font-bold text-emerald-700">14-Day Instant Free Trial</div>
                    </label>

                    <!-- Growth Cloud ($500/mo) -->
                    <label class="relative border-2 rounded-xl p-5 cursor-pointer hover:border-[#0f3559] transition-all has-[:checked]:border-[#e8913a] has-[:checked]:bg-amber-50/35 flex flex-col justify-between">
                        <div class="absolute -top-2.5 right-3 bg-[#e8913a] text-[#011835] text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                            Most Popular
                        </div>
                        <div>
                            <input type="radio" name="plan" value="growth" class="sr-only" {{ $selectedPlan === 'growth' ? 'checked' : '' }}>
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="font-display font-bold text-sm text-[#0f3559] block">Growth Regional Cloud</span>
                                    <span class="text-[11px] text-slate-500">Full Hardware Orchestration</span>
                                </div>
                                <span class="text-sm font-extrabold text-[#e8913a] whitespace-nowrap">$500<span class="text-[10px] font-semibold text-slate-500">/mo</span></span>
                            </div>
                            <ul class="mt-3.5 space-y-1.5 text-xs text-slate-600 border-t border-slate-200/70 pt-3">
                                <li class="font-semibold text-slate-800">&#10003; Up to 40 Field Technicians</li>
                                <li>&#10003; Up to 500 GIS ODP/ODC Nodes</li>
                                <li>&#10003; Multi-Router MikroTik &amp; OLT Gate</li>
                                <li>&#10003; 1-Click MikroTik/CSV Bulk Import</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-2 border-t border-slate-100 text-[11px] font-bold text-emerald-700">14-Day Instant Free Trial</div>
                    </label>

                    <!-- Dedicated Full Project ($2,500 one-time) -->
                    <div class="relative border-2 border-[#0f3559] bg-gradient-to-b from-slate-50 to-white rounded-xl p-5 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start gap-2">
                                <div>
                                    <span class="font-display font-bold text-sm text-[#0f3559] block">Dedicated Full Project</span>
                                    <span class="text-[11px] text-slate-500">100% White-Label Ownership</span>
                                </div>
                                <span class="text-sm font-extrabold text-[#0f3559] whitespace-nowrap">$2,500<span class="text-[10px] font-semibold text-slate-500">/proj</span></span>
                            </div>
                            <ul class="mt-3.5 space-y-1.5 text-xs text-slate-600 border-t border-slate-200/70 pt-3">
                                <li class="font-semibold text-slate-800">&#10003; Unlimited Techs, ODP &amp; Routers</li>
                                <li>&#10003; Custom Domain &amp; Signed APK Build</li>
                                <li>&#10003; Dedicated VPS/On-Premise Setup</li>
                                <li>&#10003; 7-Day Turnkey Architect Handover</li>
                            </ul>
                        </div>
                        <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.remove('hidden')"
                            class="mt-4 w-full py-2.5 px-3 rounded-lg bg-[#0f3559] hover:bg-[#011835] text-white text-xs font-extrabold transition-all cursor-pointer shadow-sm">
                            Book $2,500 Project Slot &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section 3: Super Admin Account -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">3</span>
                    Primary Super Admin Credentials
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_name">Full Name *</label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required
                            placeholder="Alex Morgan, CTO"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_phone">WhatsApp / Direct Phone (with Country Code) *</label>
                        <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone') }}" required
                            placeholder="+1 415 555 0199"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_email">Business Email (Console Login) *</label>
                        <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}" required
                            placeholder="cto@apexfiber.net"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                    </div>

                    <div class="hidden sm:block"></div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="password">Password * (Min 8 chars)</label>
                        <input id="password" name="password" type="password" required placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="password_confirmation">Confirm Password *</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="••••••••"
                            class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20">
                    </div>
                </div>
            </div>

            <!-- Submit Bar -->
            <div class="pt-4 border-t border-slate-200">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-[#0f3559] to-[#011835] text-white font-display font-bold text-base hover:shadow-lg hover:shadow-[#0f3559]/25 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    Launch Isolated ISP Workspace &amp; Start 14-Day Free Trial &rarr;
                </button>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500 font-medium">
                    <span>&#10003; AES-256 Encrypted Secrets</span>
                    <span>&#10003; 1-Click MikroTik &amp; CSV Import</span>
                    <span>&#10003; Stripe USD &amp; Midtrans QRIS Ready</span>
                </div>
            </div>
        </form>
    </main>

    <footer class="max-w-6xl w-full mx-auto text-center py-4 text-xs text-slate-400">
        &copy; 2026 Benteng Siber. Zero-Trust Multi-Tenant ISP FiberOps &amp; Network Orchestrator.
    </footer>

    <!-- HIGH-CONVERTING $2,500 DEDICATED TURNKEY PROJECT BOOKING MODAL -->
    <div id="enterprise-inquiry-modal" class="fixed inset-0 z-50 {{ $autoOpenProjectModal ? '' : 'hidden' }} bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-auto">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#011835] to-[#0f3559] px-6 py-5 flex items-start justify-between text-white gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#e8913a]/20 border border-[#e8913a]/50 text-[#f4b168] text-[10px] font-mono font-bold uppercase tracking-wider mb-2">
                        7-Day Turnkey Delivery Guarantee &bull; $2,500 One-Time
                    </span>
                    <h3 class="font-display font-extrabold text-lg sm:text-xl leading-tight">Book Your $2,500 Dedicated White-Label ISP Deployment</h3>
                    <p class="text-xs text-slate-300 mt-1">Unlimited technicians, custom domain, signed branded Android APK, multi-router MikroTik + OLT + Stripe/QRIS setup.</p>
                </div>
                <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.add('hidden')" class="text-slate-300 hover:text-white text-2xl font-bold leading-none cursor-pointer">&times;</button>
            </div>

            <!-- Value Stack Strip -->
            <div class="bg-amber-50/70 border-b border-amber-200/80 px-6 py-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] font-semibold text-[#011835]">
                <div>&#10003; Zero Per-Seat Lock-In</div>
                <div>&#10003; Deployed on Your Server</div>
                <div>&#10003; Full Source/Admin Handover</div>
            </div>

            <form action="{{ route('tenant.enterprise_inquiry') }}" method="POST" class="p-6 space-y-4 text-xs">
                @csrf
                <input type="text" name="website_hp" value="" class="hidden" tabindex="-1" autocomplete="off">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Company / ISP Name *</label>
                        <input type="text" name="enterprise_company" value="{{ old('enterprise_company') }}" required placeholder="e.g. Skyline Fiber Broadband" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Your Name &amp; Role *</label>
                        <input type="text" name="enterprise_name" value="{{ old('enterprise_name') }}" required placeholder="e.g. David Chen, Founder / CTO" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Business Email *</label>
                        <input type="email" name="enterprise_email" value="{{ old('enterprise_email') }}" required placeholder="david@skylinefiber.com" class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">WhatsApp / Phone (with Country Code) *</label>
                        <input type="text" name="enterprise_phone" value="{{ old('enterprise_phone') }}" required placeholder="+1 / +44 / +971 / +62 ..." class="w-full px-3 py-2.5 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Field Technicians *</label>
                        <input type="number" name="estimated_technicians" min="1" value="{{ old('estimated_technicians', 25) }}" required class="w-full px-3 py-2.5 rounded-lg border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">MikroTik Routers / POPs</label>
                        <input type="number" name="router_count" min="1" value="{{ old('router_count', 3) }}" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Infrastructure</label>
                        <select name="deployment_target" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 bg-white">
                            <option value="dedicated_cloud">Dedicated Cloud VPS (AWS / DO / Vultr)</option>
                            <option value="on_premise">On-Premise NOC Server</option>
                            <option value="hybrid">Managed by Benteng Siber ($250–$500/mo)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Target Launch Timeline</label>
                        <select name="timeline" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 bg-white">
                            <option value="asap">ASAP — Launch Within 7 Days</option>
                            <option value="within_30_days">Within 30 Days</option>
                            <option value="evaluating">Architecture Discovery Call First</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hardware &amp; Branding Notes (OLT, Stripe/QRIS, Custom Domain)</label>
                    <textarea name="notes" rows="2" placeholder="e.g. 3x MikroTik CCR2004, ZTE C320 OLT, Stripe USD billing, custom domain ops.ourisp.com">{{ old('notes') }}</textarea>
                </div>

                <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3 text-[11px]">
                        <a href="https://wa.me/6281223554539?text={{ urlencode('Hi Benteng Siber! I want to fast-track a $2,500 Dedicated ISP Project deployment.') }}" target="_blank" rel="noopener noreferrer" class="font-bold text-emerald-700 hover:underline">
                            Skip Form &rarr; WhatsApp Direct
                        </a>
                        <span class="text-slate-300">|</span>
                        <a href="https://bentengsiber.com/proposal.html" target="_blank" rel="noopener noreferrer" class="font-bold text-[#0f3559] hover:underline">
                            View Proposal PDF
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.add('hidden')" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 font-semibold cursor-pointer">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#e8913a] hover:bg-[#d07e2e] text-[#011835] font-extrabold shadow-sm cursor-pointer">
                            Reserve $2,500 Project Slot &rarr;
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
