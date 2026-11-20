<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Portal Sign In | Benteng Siber FiberOps Command Center</title>
    <meta name="theme-color" content="#ffffff">

    <!-- Google Fonts: Plus Jakarta Sans, Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Compiled Production Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset('build/app.css') }}?v=20261120v3">

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f6f7f9;
            color: #1e293b;
        }
        .font-display {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
        .auth-dot-bg {
            background-image: radial-gradient(rgba(15, 53, 89, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
        }
        .btn-corporate-navy {
            background-color: #0f3559;
            color: #ffffff;
        }
        .btn-corporate-navy:hover {
            background-color: #1a4a7a;
        }
        .btn-corporate-accent {
            background-color: #e8913a;
            color: #ffffff;
        }
        .btn-corporate-accent:hover {
            background-color: #d07e2e;
        }
        .role-pill-active {
            border-color: #0f3559 !important;
            background-color: #eef4fa !important;
            color: #0f3559 !important;
            box-shadow: 0 0 0 2px rgba(15, 53, 89, 0.14);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between antialiased selection:bg-[#e8913a]/20 selection:text-[#0f3559] relative overflow-x-hidden">

    <!-- Subtle Bright Architectural Dot Background -->
    <div class="fixed inset-0 pointer-events-none z-0 auth-dot-bg opacity-70" aria-hidden="true"></div>
    <div class="fixed top-0 inset-x-0 h-1 bg-gradient-to-r from-[#0f3559] via-[#e8913a] to-[#0f3559] z-20" aria-hidden="true"></div>

    <!-- Top Bright Corporate Navigation Bar -->
    <header class="relative z-10 w-full bg-white/95 backdrop-blur-md border-b border-slate-200/90">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
            <a href="https://bentengsiber.com/" class="group inline-flex items-center gap-2.5 text-sm font-semibold text-[#0f3559] hover:text-[#e8913a] transition-colors">
                <span class="w-9 h-9 rounded-lg bg-[#f6f7f9] border border-slate-200 flex items-center justify-center text-[#0f3559] group-hover:border-[#e8913a] group-hover:text-[#e8913a] transition-colors">
                    <ion-icon name="arrow-back-outline" class="text-base"></ion-icon>
                </span>
                <span class="tracking-tight">Back to Benteng Siber</span>
            </a>

            <!-- System Status Pill -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>NOC Core Operational</span>
                </span>
            </div>
        </div>
    </header>

    <!-- Main Auth Content -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-[460px]">

            <!-- Brand Lockup & Header -->
            <div class="text-center mb-7">
                <div class="inline-flex items-center justify-center mb-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#0f3559] flex items-center justify-center text-white shadow-lg shadow-[#0f3559]/20 border border-[#1a4a7a]">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <path d="M9 12l2 2 4-4" stroke="#e8913a" stroke-width="2.4"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-mono font-semibold tracking-widest uppercase text-[#d07e2e] bg-[#fff5ea] border border-[#e8913a]/35 px-3 py-1 rounded-full mb-2.5">
                        FIBEROPS COMMAND CENTER • RBAC AUTH
                    </span>
                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-[#0f3559] tracking-tight">
                        Admin &amp; Supervisor Sign In
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5 max-w-sm mx-auto leading-relaxed">
                        Authorized web portal for Super Admin dispatch operations and Field Supervisor live monitoring.
                    </p>
                </div>
            </div>

            <!-- Bright Corporate Login Card -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 sm:p-8 shadow-[0_14px_40px_rgba(15,53,89,0.08)]">

                <!-- Quick Demo Credentials Selector (Super Admin & Field Supervisor) -->
                <div class="mb-6 bg-[#f6f7f9] border border-slate-200 rounded-xl p-3.5">
                    <div class="flex items-center justify-between mb-2.5">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-[#0f3559]">
                            <ion-icon name="key-outline" class="text-[#e8913a] text-sm"></ion-icon>
                            <span>Quick Access Roles</span>
                        </div>
                        <span class="text-[10px] font-mono font-semibold text-slate-500 uppercase tracking-wider">1-Click Fill</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            id="btn-fill-admin"
                            class="px-3 py-2.5 rounded-xl bg-white hover:bg-[#eef4fa] border border-slate-200 hover:border-[#0f3559]/50 text-[#0f3559] text-xs font-semibold transition-all active:scale-95 flex flex-col items-center justify-center gap-0.5 min-h-[54px] cursor-pointer shadow-sm"
                        >
                            <span class="inline-flex items-center gap-1.5 font-bold">
                                <ion-icon name="shield-checkmark-outline" class="text-sm text-[#0f3559]"></ion-icon>
                                <span>Super Admin</span>
                            </span>
                            <span class="text-[10px] font-normal text-slate-500">Full NOC &amp; Dispatch</span>
                        </button>

                        <button
                            type="button"
                            id="btn-fill-supervisor"
                            class="px-3 py-2.5 rounded-xl bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-500/60 text-[#0f3559] text-xs font-semibold transition-all active:scale-95 flex flex-col items-center justify-center gap-0.5 min-h-[54px] cursor-pointer shadow-sm"
                        >
                            <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700">
                                <ion-icon name="eye-outline" class="text-sm text-emerald-600"></ion-icon>
                                <span>Supervisor</span>
                            </span>
                            <span class="text-[10px] font-normal text-slate-500">Tracking, Proofs &amp; Reports</span>
                        </button>
                    </div>

                    <div class="mt-2.5 pt-2 border-t border-slate-200/80 text-[11px] font-mono text-slate-600 flex items-center justify-between gap-2">
                        <span id="demo-pill-label" class="truncate">Account: admin@fsm.com</span>
                        <span class="text-[#d07e2e] font-semibold shrink-0">pass: password123</span>
                    </div>
                </div>

                <!-- Error Messages Box -->
                @if($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2.5" role="alert">
                        <ion-icon name="alert-circle" class="text-lg flex-shrink-0 mt-0.5 text-red-600"></ion-icon>
                        <div class="space-y-1">
                            <div class="font-bold text-red-800">Authentication Failed</div>
                            <ul class="list-disc list-inside space-y-0.5 text-red-700 text-[11px]">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Sign In Form -->
                <form id="admin-login-form" action="{{ route('admin.login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-[#0f3559] uppercase tracking-wider mb-1.5">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <ion-icon name="mail-outline" class="text-lg"></ion-icon>
                            </div>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email"
                                class="w-full min-h-[48px] pl-11 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 text-base sm:text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#e8913a]/30 focus:border-[#e8913a] transition-all"
                                placeholder="admin@fsm.com or supervisor@fsm.com"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-[#0f3559] uppercase tracking-wider">
                                Password
                            </label>
                            <span class="text-[11px] font-mono text-slate-500">Protected Session</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <ion-icon name="lock-closed-outline" class="text-lg"></ion-icon>
                            </div>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full min-h-[48px] pl-11 pr-12 py-3 bg-white border border-slate-300 rounded-xl text-slate-900 text-base sm:text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#e8913a]/30 focus:border-[#e8913a] transition-all"
                                placeholder="Enter password"
                            >
                            <!-- 44x44px Touch Target Toggle -->
                            <button
                                type="button"
                                id="toggle-password"
                                class="absolute inset-y-0 right-0 w-11 h-full flex items-center justify-center text-slate-500 hover:text-[#0f3559] transition-colors focus:outline-none active:scale-90"
                                aria-label="Show or hide password"
                            >
                                <ion-icon id="eye-icon" name="eye-outline" class="text-lg"></ion-icon>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & SSL Indicator -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                id="remember"
                                name="remember"
                                type="checkbox"
                                class="w-4 h-4 rounded border-slate-300 text-[#0f3559] focus:ring-[#e8913a]/40 cursor-pointer"
                            >
                            <span class="text-xs font-medium text-slate-600">Remember my session</span>
                        </label>
                        <span class="text-[11px] text-slate-500 flex items-center gap-1 font-mono">
                            <ion-icon name="shield-checkmark" class="text-emerald-600"></ion-icon>
                            <span>TLS 1.3 Encrypted</span>
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        id="btn-submit"
                        class="btn-corporate-accent w-full min-h-[48px] mt-2 py-3.5 px-5 rounded-xl font-bold text-sm sm:text-base shadow-md shadow-[#e8913a]/25 transition-all duration-150 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#e8913a] focus:ring-offset-2 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-55 disabled:cursor-not-allowed"
                    >
                        <span id="btn-text">Sign In to Command Center</span>
                        <ion-icon id="btn-icon" name="arrow-forward" class="text-base"></ion-icon>
                        <svg id="btn-spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Other Ecosystem Portals -->
                <div class="mt-5 pt-4 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs">
                    <a href="https://app.bentengsiber.com/?demo=1" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-[#0f3559] hover:text-[#e8913a] font-semibold transition-colors">
                        <ion-icon name="phone-portrait-outline" class="text-sm"></ion-icon>
                        <span>Technician PWA &rarr;</span>
                    </a>
                    <a href="{{ route('public.request.create') }}" class="inline-flex items-center gap-1.5 text-slate-600 hover:text-[#0f3559] font-semibold transition-colors">
                        <ion-icon name="document-text-outline" class="text-sm"></ion-icon>
                        <span>Client Report Portal &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Footer Meta -->
            <div class="mt-6 text-center space-y-1">
                <p class="text-xs text-slate-500">
                    &copy; 2026 Benteng Siber — FiberOps Field Service Management.
                </p>
                <p class="text-[11px] font-mono text-slate-400">
                    Sanctum Session Guard &bull; Rate-Limited Auth (5 req/min)
                </p>
            </div>
        </div>
    </main>

    <!-- Bottom Light Status Bar -->
    <footer class="relative z-10 w-full py-3 border-t border-slate-200 bg-white text-center">
        <div class="text-[11px] font-mono text-slate-500 flex items-center justify-center gap-3 flex-wrap px-4">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Super Admin &amp; Field Supervisor RBAC
            </span>
            <span class="text-slate-300">&bull;</span>
            <span>SHA-256 BAST Engine</span>
            <span class="text-slate-300">&bull;</span>
            <span>bentengsiber.com</span>
        </div>
    </footer>

    <!-- Interactive Scripts: Quick Fill, Password Toggle, Anti-Double Submit -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('toggle-password');
            const eyeIcon = document.getElementById('eye-icon');
            const btnFillAdmin = document.getElementById('btn-fill-admin');
            const btnFillSupervisor = document.getElementById('btn-fill-supervisor');
            const demoPillLabel = document.getElementById('demo-pill-label');
            const form = document.getElementById('admin-login-form');
            const btnSubmit = document.getElementById('btn-submit');
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            const btnSpinner = document.getElementById('btn-spinner');

            function setActivePill(activeBtn) {
                [btnFillAdmin, btnFillSupervisor].forEach(function (b) {
                    if (b) b.classList.remove('role-pill-active');
                });
                if (activeBtn) activeBtn.classList.add('role-pill-active');
            }

            function fillCredentials(email, pass, roleLabel, btnEl) {
                if (!emailInput || !passwordInput) return;
                emailInput.value = email;
                passwordInput.value = pass;
                if (demoPillLabel) demoPillLabel.textContent = roleLabel + ': ' + email;
                setActivePill(btnEl);

                emailInput.classList.add('ring-2', 'ring-emerald-500/40', 'border-emerald-500');
                passwordInput.classList.add('ring-2', 'ring-emerald-500/40', 'border-emerald-500');
                setTimeout(function () {
                    emailInput.classList.remove('ring-2', 'ring-emerald-500/40', 'border-emerald-500');
                    passwordInput.classList.remove('ring-2', 'ring-emerald-500/40', 'border-emerald-500');
                }, 900);
            }

            if (btnFillAdmin) {
                btnFillAdmin.addEventListener('click', function () {
                    fillCredentials('admin@fsm.com', 'password123', 'Super Admin', btnFillAdmin);
                });
            }

            if (btnFillSupervisor) {
                btnFillSupervisor.addEventListener('click', function () {
                    fillCredentials('supervisor@fsm.com', 'password123', 'Supervisor', btnFillSupervisor);
                });
            }

            if (toggleBtn && passwordInput && eyeIcon) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    eyeIcon.setAttribute('name', isPassword ? 'eye-off-outline' : 'eye-outline');
                });
            }

            if (form && btnSubmit) {
                form.addEventListener('submit', function () {
                    btnSubmit.disabled = true;
                    btnText.textContent = 'Authenticating Session...';
                    if (btnIcon) btnIcon.classList.add('hidden');
                    if (btnSpinner) btnSpinner.classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>
