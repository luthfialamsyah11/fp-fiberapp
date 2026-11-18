<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Admin Sign In - FiberOps Command Center</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brandBg: '#06090e',
                        brandSurface: '#0c1220',
                        brandBorder: '#162238',
                        brandCyan: '#22d3ee',
                        brandBlue: '#2563eb',
                    }
                }
            }
        }
    </script>
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="bg-brandBg text-slate-100 min-h-screen flex flex-col justify-between antialiased selection:bg-cyan-500 selection:text-slate-950 font-sans relative overflow-x-hidden">

    <!-- Ambient Cyber Glows (Refined Obsidian Anti-Generic AI) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[760px] max-w-[95vw] h-[400px] bg-gradient-to-b from-blue-600/15 via-cyan-500/10 to-transparent blur-[140px] rounded-full"></div>
        <div class="absolute -bottom-36 right-4 sm:right-16 w-[480px] h-[340px] bg-indigo-600/10 blur-[130px] rounded-full"></div>
        <!-- High-contrast micro-dot matrix pattern -->
        <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    </div>

    <!-- Top Navigation Bar -->
    <header class="relative z-10 w-full max-w-5xl mx-auto px-4 sm:px-6 pt-5 pb-3 flex items-center justify-between">
        <a href="https://bentengsiber.com/" class="group inline-flex items-center gap-2.5 text-xs sm:text-sm text-slate-400 hover:text-white transition-all duration-150 active:scale-95">
            <span class="w-9 h-9 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-center text-slate-300 group-hover:border-cyan-500/50 group-hover:text-cyan-400 group-hover:shadow-[0_0_15px_-3px_rgba(34,211,238,0.3)] transition-all">
                <ion-icon name="arrow-back-outline" class="text-base"></ion-icon>
            </span>
            <span class="font-medium tracking-tight">Back to Portal</span>
        </a>

        <!-- System Status Bead -->
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] sm:text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Operational Core Active</span>
            </span>
        </div>
    </header>

    <!-- Main Auth Content -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-6 sm:py-10">
        <div class="w-full max-w-md">
            
            <!-- Brand Badge & Header -->
            <div class="text-center mb-6 sm:mb-8">
                <div class="relative inline-flex items-center justify-center mb-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 via-indigo-600 to-cyan-500 p-0.5 shadow-xl shadow-blue-500/20">
                        <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-cyan-400">
                            <ion-icon name="shield-checkmark" class="text-3xl"></ion-icon>
                        </div>
                    </div>
                    <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-cyan-500 border-2 border-slate-950"></span>
                    </span>
                </div>
                
                <div>
                    <span class="inline-block text-[10px] sm:text-[11px] font-mono font-bold tracking-widest uppercase text-cyan-400 bg-cyan-950/60 border border-cyan-800/50 px-3 py-1 rounded-full mb-2">
                        FIBEROPS COMMAND CENTER • RBAC PORTAL
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Admin &amp; Dispatcher</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1.5 max-w-sm mx-auto leading-relaxed">
                        Centralized field technician dispatch, ODN optical loss validation, and cryptographic digital BAST audit.
                    </p>
                </div>
            </div>

            <!-- Login Card Container -->
            <div class="bg-brandSurface/90 border border-slate-800/90 ring-1 ring-white/5 rounded-2xl p-5 sm:p-8 backdrop-blur-2xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.8)]">
                
                <!-- Quick Demo Fill Pills Section -->
                <div class="mb-5 bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 sm:p-3.5">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-200">
                            <ion-icon name="key-outline" class="text-cyan-400 text-sm"></ion-icon>
                            <span>Evaluation Credentials:</span>
                        </div>
                        <span class="text-[10px] font-mono text-slate-400">1-Click Auto Fill</span>
                    </div>

                    <!-- Role Pills Grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            type="button" 
                            id="btn-fill-admin" 
                            class="px-2.5 py-2 rounded-lg bg-blue-600/15 hover:bg-blue-600/25 border border-blue-500/30 text-blue-300 hover:text-white text-xs font-medium transition-all active:scale-95 flex items-center justify-center gap-1.5 text-center min-h-[40px] cursor-pointer"
                        >
                            <ion-icon name="shield-outline" class="text-sm"></ion-icon>
                            <span>Admin Dispatcher</span>
                        </button>

                        <button 
                            type="button" 
                            id="btn-fill-supervisor" 
                            class="px-2.5 py-2 rounded-lg bg-emerald-600/15 hover:bg-emerald-600/25 border border-emerald-500/30 text-emerald-300 hover:text-white text-xs font-medium transition-all active:scale-95 flex items-center justify-center gap-1.5 text-center min-h-[40px] cursor-pointer"
                        >
                            <ion-icon name="person-circle-outline" class="text-sm"></ion-icon>
                            <span>Supervisor</span>
                        </button>
                    </div>

                    <!-- Notice banner -->
                    <div class="mt-2 text-[11px] font-mono text-slate-400 flex items-center justify-between pt-1.5 border-t border-slate-800/60">
                        <span id="demo-pill-label">Account: admin@fsm.com</span>
                        <span class="text-cyan-400 font-semibold">pass: password123</span>
                    </div>
                </div>

                <!-- Error Messages Box -->
                @if($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-start gap-2.5">
                        <ion-icon name="alert-circle" class="text-lg flex-shrink-0 mt-0.5 text-red-400"></ion-icon>
                        <div class="space-y-1">
                            <div class="font-semibold text-red-200">Authentication Failed:</div>
                            <ul class="list-disc list-inside space-y-0.5 text-red-300/90 text-[11px]">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form id="admin-login-form" action="{{ route('admin.login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Administrator Email Address
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
                                class="w-full min-h-[48px] pl-11 pr-4 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 text-base sm:text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/40 focus:border-cyan-500 transition-all"
                                placeholder="admin@fsm.com"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                Password
                            </label>
                            <span class="text-[11px] font-mono text-slate-400">Min. 8 Characters</span>
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
                                class="w-full min-h-[48px] pl-11 pr-12 py-3 bg-slate-950/80 border border-slate-800 rounded-xl text-slate-100 text-base sm:text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/40 focus:border-cyan-500 transition-all"
                                placeholder="••••••••"
                            >
                            <!-- Password Toggle (44x44px Ergonomic Touch Target) -->
                            <button 
                                type="button" 
                                id="toggle-password" 
                                class="absolute inset-y-0 right-0 w-11 h-full flex items-center justify-center text-slate-400 hover:text-white transition-colors focus:outline-none active:scale-90"
                                aria-label="Show or hide password"
                            >
                                <ion-icon id="eye-icon" name="eye-outline" class="text-lg"></ion-icon>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me & Security Notice -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input 
                                id="remember" 
                                name="remember" 
                                type="checkbox" 
                                class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-cyan-500 focus:ring-cyan-500/40 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer"
                            >
                            <span class="text-xs text-slate-300">Remember my session</span>
                        </label>
                        <span class="text-[11px] text-slate-400 flex items-center gap-1 font-mono">
                            <ion-icon name="shield-checkmark" class="text-cyan-400"></ion-icon>
                            <span>SSL 256-Bit</span>
                        </span>
                    </div>

                    <!-- Submit Button with 5-State Interactivity & Loading State -->
                    <button 
                        type="submit" 
                        id="btn-submit"
                        class="w-full min-h-[48px] mt-2 py-3.5 px-5 rounded-xl bg-gradient-to-r from-blue-600 via-blue-500 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white font-semibold text-sm sm:text-base shadow-lg shadow-blue-500/25 transition-all duration-150 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 focus:ring-offset-slate-900 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="btn-text">Sign In to Command Center</span>
                        <ion-icon id="btn-icon" name="arrow-forward" class="text-base"></ion-icon>
                        <svg id="btn-spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Alternative Link for Field Tech PWA -->
                <div class="mt-4 pt-4 border-t border-slate-800/80 text-center">
                    <a href="https://app.bentengsiber.com/?demo=1" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-xs text-cyan-400 hover:text-cyan-300 font-medium transition-colors">
                        <ion-icon name="phone-portrait-outline" class="text-sm"></ion-icon>
                        <span>Open Field Technician Terminal (Mobile PWA) →</span>
                    </a>
                </div>

            </div>

            <!-- Footer Meta -->
            <div class="mt-6 text-center space-y-1.5">
                <p class="text-[11px] font-mono text-slate-500">
                    &copy; 2026 FiberOps Field Service Management System.
                </p>
                <p class="text-[10px] font-mono text-slate-600">
                    Sanctum Session Guard • Token Rotation Active
                </p>
            </div>

        </div>
    </main>

    <!-- Bottom Security Bar -->
    <footer class="relative z-10 w-full py-3 border-t border-slate-900 bg-slate-950/60 backdrop-blur-md text-center">
        <div class="text-[11px] font-mono text-slate-400 flex items-center justify-center gap-3 flex-wrap px-4">
            <span class="flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                Laravel 10 Backend
            </span>
            <span class="text-slate-700">•</span>
            <span>Sanctum Multi-Role RBAC</span>
            <span class="text-slate-700">•</span>
            <span>MySQL 8.0 Cluster</span>
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

            // Quick Fill Helper
            function fillCredentials(email, pass, label, btnEl) {
                if (!emailInput || !passwordInput) return;
                emailInput.value = email;
                passwordInput.value = pass;
                if (demoPillLabel) demoPillLabel.textContent = 'Account: ' + email;
                
                // Visual Flash Feedback
                emailInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
                passwordInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
                
                const origHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<ion-icon name="checkmark-circle"></ion-icon> Filled!';
                
                setTimeout(() => {
                    emailInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
                    passwordInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
                    btnEl.innerHTML = origHtml;
                }, 1200);
            }

            if (btnFillAdmin) {
                btnFillAdmin.addEventListener('click', function () {
                    fillCredentials('admin@fsm.com', 'password123', 'admin@fsm.com', btnFillAdmin);
                });
            }

            if (btnFillSupervisor) {
                btnFillSupervisor.addEventListener('click', function () {
                    fillCredentials('supervisor@fsm.com', 'password123', 'supervisor@fsm.com', btnFillSupervisor);
                });
            }

            // Password Toggle
            if (toggleBtn && passwordInput && eyeIcon) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    eyeIcon.setAttribute('name', isPassword ? 'eye-off-outline' : 'eye-outline');
                });
            }

            // Prevent double-submit with loading indicator
            if (form && btnSubmit) {
                form.addEventListener('submit', function () {
                    btnSubmit.disabled = true;
                    btnText.textContent = 'Authenticating Session...';
                    btnIcon.classList.add('hidden');
                    btnSpinner.classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>
