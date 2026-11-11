<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Admin - FiberOps Command Center</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#2563EB',
                        primaryHover: '#1D4ED8',
                        brandBg: '#090D16',
                        brandSurface: '#0F172A',
                        brandBorder: '#1E293B',
                    }
                }
            }
        }
    </script>
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body class="bg-brandBg text-slate-100 min-h-screen flex flex-col justify-between antialiased selection:bg-blue-600 selection:text-white font-sans relative overflow-x-hidden">

    <!-- Subtle Ambient Background Light (Anti-Generic AI: no extreme neon cliches, refined deep navy glow) -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[720px] h-[400px] bg-blue-600/10 blur-[140px] rounded-full"></div>
        <div class="absolute -bottom-40 right-10 w-[500px] h-[350px] bg-indigo-600/10 blur-[130px] rounded-full"></div>
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b0a_1px,transparent_1px),linear-gradient(to_bottom,#1e293b0a_1px,transparent_1px)] bg-[size:32px_32px]"></div>
    </div>

    <!-- Top Navigation / Back to Landing -->
    <header class="relative z-10 w-full max-w-6xl mx-auto px-6 py-6 flex items-center justify-between">
        <a href="/" class="group inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors duration-200">
            <span class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center text-slate-400 group-hover:border-slate-700 group-hover:text-white transition-all">
                <ion-icon name="arrow-back-outline" class="text-base"></ion-icon>
            </span>
            <span class="font-medium">Kembali ke Portal</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Sistem Operasional Aktif
            </span>
        </div>
    </header>

    <!-- Main Auth Content -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            
            <!-- Brand Badge -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 shadow-lg shadow-blue-600/25 border border-blue-400/30 mb-4">
                    <ion-icon name="shield-checkmark" class="text-2xl text-white"></ion-icon>
                </div>
                <div class="inline-block">
                    <span class="text-[11px] font-bold tracking-widest uppercase text-blue-400 bg-blue-950/60 border border-blue-800/60 px-3 py-1 rounded-full">
                        Admin Dispatcher Portal
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-3">FiberOps Control Center</h1>
                <p class="text-sm text-slate-400 mt-1.5 max-w-xs mx-auto">
                    Akses terpadu monitoring teknisi lapangan, manajemen tiket, dan dispatching otomatis.
                </p>
            </div>

            <!-- Login Card -->
            <div class="bg-slate-900/80 border border-slate-800/90 rounded-2xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl shadow-black/40">
                
                <!-- Quick Demo Fill Pill -->
                <div class="mb-6 bg-slate-950/70 border border-slate-800 rounded-xl p-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 flex-shrink-0">
                            <ion-icon name="key-outline" class="text-sm"></ion-icon>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-200">Kredensial Demo Evaluasi</div>
                            <div class="text-[11px] text-slate-400 truncate">admin@fsm.com • password123</div>
                        </div>
                    </div>
                    <button type="button" id="btn-fill-demo" class="px-2.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition-all active:scale-95 flex-shrink-0 cursor-pointer">
                        Isi Cepat
                    </button>
                </div>

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-xs flex items-start gap-3">
                        <ion-icon name="alert-circle-outline" class="text-lg flex-shrink-0 mt-0.5 text-red-400"></ion-icon>
                        <div class="space-y-1">
                            <div class="font-semibold text-red-300">Autentikasi Gagal:</div>
                            <ul class="list-disc list-inside space-y-0.5 text-red-400/90">
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
                            Alamat Email Admin
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <ion-icon name="mail-outline" class="text-base"></ion-icon>
                            </div>
                            <input 
                                id="email" 
                                type="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="email"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="admin@fsm.com"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                                Kata Sandi
                            </label>
                            <span class="text-[11px] text-slate-500">Min. 8 karakter</span>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <ion-icon name="lock-closed-outline" class="text-base"></ion-icon>
                            </div>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                class="w-full pl-10 pr-12 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-slate-100 text-sm placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                placeholder="••••••••"
                            >
                            <!-- Password Toggle (44px touch target) -->
                            <button 
                                type="button" 
                                id="toggle-password" 
                                class="absolute inset-y-0 right-0 w-11 h-full flex items-center justify-center text-slate-400 hover:text-slate-200 transition-colors focus:outline-none"
                                aria-label="Tampilkan atau sembunyikan kata sandi"
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
                                class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-blue-600 focus:ring-blue-500 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer"
                            >
                            <span class="text-xs text-slate-300">Ingat sesi saya</span>
                        </label>
                        <span class="text-[11px] text-slate-500 flex items-center gap-1">
                            <ion-icon name="shield-outline"></ion-icon>
                            256-Bit SSL
                        </span>
                    </div>

                    <!-- Submit Button with loading state -->
                    <button 
                        type="submit" 
                        id="btn-submit"
                        class="w-full mt-2 py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm shadow-lg shadow-blue-600/30 transition-all duration-150 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-slate-900 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="btn-text">Masuk ke Dashboard</span>
                        <ion-icon id="btn-icon" name="arrow-forward" class="text-base"></ion-icon>
                        <svg id="btn-spinner" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

            </div>

            <!-- Footer Meta -->
            <div class="mt-6 text-center space-y-2">
                <p class="text-xs text-slate-500">
                    Bukan administrator? 
                    <a href="/" class="text-blue-400 hover:underline">Buka Aplikasi Teknisi</a>
                </p>
                <p class="text-[11px] text-slate-600">
                    &copy; 2026 FiberOps Field Service Management. All rights reserved.
                </p>
            </div>

        </div>
    </main>

    <!-- Bottom Security Bar -->
    <footer class="relative z-10 w-full py-4 border-t border-slate-900/60 bg-slate-950/40 text-center">
        <div class="text-[11px] text-slate-500 flex items-center justify-center gap-4 flex-wrap px-4">
            <span>Server: Laravel 10 (Sanctum RBAC)</span>
            <span>•</span>
            <span>Role: Administrator & Dispatcher</span>
            <span>•</span>
            <span>Database: MySQL 8.0</span>
        </div>
    </footer>

    <!-- Interactive Scripts: Quick Fill, Password Toggle, Submit State -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('toggle-password');
            const eyeIcon = document.getElementById('eye-icon');
            const btnFillDemo = document.getElementById('btn-fill-demo');
            const form = document.getElementById('admin-login-form');
            const btnSubmit = document.getElementById('btn-submit');
            const btnText = document.getElementById('btn-text');
            const btnIcon = document.getElementById('btn-icon');
            const btnSpinner = document.getElementById('btn-spinner');

            // Quick Demo Fill
            if (btnFillDemo) {
                btnFillDemo.addEventListener('click', function () {
                    emailInput.value = 'admin@fsm.com';
                    passwordInput.value = 'password123';
                    emailInput.focus();
                    btnFillDemo.textContent = 'Terisi!';
                    btnFillDemo.classList.replace('bg-blue-600', 'bg-emerald-600');
                    setTimeout(() => {
                        btnFillDemo.textContent = 'Isi Cepat';
                        btnFillDemo.classList.replace('bg-emerald-600', 'bg-blue-600');
                    }, 1500);
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
                    btnText.textContent = 'Memverifikasi...';
                    btnIcon.classList.add('hidden');
                    btnSpinner.classList.remove('hidden');
                });
            }
        });
    </script>
</body>
</html>
