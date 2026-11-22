<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Daftarkan ISP Anda | Benteng Siber FiberOps Multi-Tenant Cloud</title>
    <meta name="description" content="Daftarkan penyedia layanan internet (ISP) Anda untuk memulai operasional lapangan Zero-Trust dengan NOC dispatch, PWA teknisi, dan BAST SHA-256.">
    <meta name="theme-color" content="#0f3559">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Compiled Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset('build/app.css') }}?v=20261120v3">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f6f7f9;
            color: #1e293b;
        }
        .font-display {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .auth-dot-bg {
            background-image: radial-gradient(rgba(15, 53, 89, 0.08) 1px, transparent 1px);
            background-size: 26px 26px;
        }
    </style>
</head>
<body class="min-h-screen auth-dot-bg flex flex-col justify-between p-4 sm:p-6 lg:p-8">

    <!-- Top Navigation Header -->
    <header class="max-w-6xl w-full mx-auto flex items-center justify-between pb-6 border-b border-slate-200">
        <a href="/" class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0f3559] to-[#011835] flex items-center justify-center text-white shadow-md">
                <svg class="w-5 h-5 text-[#e8913a]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <span class="font-display font-extrabold text-xl text-[#0f3559] tracking-tight">Benteng Siber</span>
                <span class="block text-xs font-semibold text-[#e8913a] tracking-wider uppercase">Multi-Tenant FiberOps Cloud</span>
            </div>
        </a>

        <div class="flex items-center gap-4">
            <span class="hidden sm:inline-block text-xs text-slate-500 font-medium">Sudah memiliki akun ISP?</span>
            <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg text-xs font-bold text-[#0f3559] bg-white hover:bg-slate-50 transition-colors shadow-sm">
                Login Console &rarr;
            </a>
        </div>
    </header>

    <!-- Main Registration Box -->
    <main class="max-w-4xl w-full mx-auto my-8 bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="p-6 sm:p-10 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-3">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Uji Coba Gratis 14 Hari Tanpa Kartu Kredit
            </div>
            <h1 class="font-display font-extrabold text-2xl sm:text-3xl text-[#0f3559] tracking-tight">
                Daftarkan Organisasi ISP Anda
            </h1>
            <p class="text-sm text-slate-600 mt-2 max-w-2xl">
                Dapatkan konsol Super Admin NOC khusus, pemetaan GIS fiber (OLT/ODP), aplikasi mobile teknisi offline-first, dan tanda tangan digital BAST SHA-256 untuk seluruh tim operasional Anda.
            </p>
        </div>

        @if (session('enterprise_success'))
            <div class="m-6 sm:m-10 mb-0 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <strong class="font-bold block">Permintaan Enterprise Diterima!</strong>
                    <span class="text-xs text-emerald-800">{{ session('enterprise_success') }}</span>
                </div>
                <a href="https://wa.me/6281223554539?text={{ urlencode('Halo tim Benteng Siber, kami baru saja mengirimkan Enterprise Inquiry dan ingin menjadwalkan demo arsitektur.') }}" target="_blank" rel="noopener noreferrer"
                    class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold whitespace-nowrap">
                    Fast-Track via WhatsApp &rarr;
                </a>
            </div>
        @endif

        @if ($errors->any())
            <div class="m-6 sm:m-10 mb-0 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
                <strong class="font-bold flex items-center gap-1.5 mb-1">
                    <svg class="w-4 h-4 text-red-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    Mohon lengkapi formulir pendaftaran:
                </strong>
                <ul class="list-disc list-inside space-y-0.5 text-xs text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tenant.register.post') }}" method="POST" class="p-6 sm:p-10 space-y-8">
            @csrf

            <!-- Section 1: Profil Organisasi ISP -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">1</span>
                    Identitas & Subdomain Organisasi ISP
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="company_name">
                            Nama Perusahaan / ISP *
                        </label>
                        <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required
                            placeholder="Contoh: PT Lintas Data Nusantara"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="slug">
                            Subdomain Pilihan (URL Instan) *
                        </label>
                        <div class="flex items-center">
                            <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required
                                placeholder="nusantara"
                                class="w-full px-4 py-2.5 rounded-l-lg border border-r-0 border-slate-300 text-sm lowercase focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all font-mono">
                            <span class="px-3 py-2.5 bg-slate-100 border border-slate-300 rounded-r-lg text-xs font-medium text-slate-500 whitespace-nowrap">
                                .bentengsiber.com
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Hanya huruf kecil, angka, dan strip (-). Contoh: <code>cahaya-fiber</code></p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Pemilihan Paket Langganan -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">2</span>
                    Pilih Paket Langganan Awal (14 Hari Trial Aktif)
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Starter Card -->
                    <label class="relative border-2 rounded-xl p-4 cursor-pointer hover:border-[#0f3559] transition-all has-[:checked]:border-[#0f3559] has-[:checked]:bg-blue-50/40">
                        <input type="radio" name="plan" value="starter" class="sr-only" {{ old('plan', 'starter') === 'starter' ? 'checked' : '' }}>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-display font-bold text-sm text-[#0f3559] block">Paket Starter</span>
                                <span class="text-xs text-slate-500">ISP Skala Kecil / RT RW Net</span>
                            </div>
                            <span class="text-xs font-extrabold text-[#e8913a]">Rp 1,5 Jt/bln</span>
                        </div>
                        <ul class="mt-3 space-y-1 text-xs text-slate-600 border-t border-slate-200/60 pt-2.5">
                            <li class="flex items-center gap-1.5 font-semibold text-slate-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Maks 5 Teknisi Lapangan
                            </li>
                            <li class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Hingga 50 Node ODP/ODC
                            </li>
                            <li class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                BAST Segel SHA-256
                            </li>
                        </ul>
                    </label>

                    <!-- Growth Card -->
                    <label class="relative border-2 rounded-xl p-4 cursor-pointer hover:border-[#0f3559] transition-all has-[:checked]:border-[#0f3559] has-[:checked]:bg-blue-50/40">
                        <div class="absolute -top-2.5 right-3 bg-[#e8913a] text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                            Populer
                        </div>
                        <input type="radio" name="plan" value="growth" class="sr-only" {{ old('plan') === 'growth' ? 'checked' : '' }}>
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-display font-bold text-sm text-[#0f3559] block">Paket Growth</span>
                                <span class="text-xs text-slate-500">ISP Regional Berkembang</span>
                            </div>
                            <span class="text-xs font-extrabold text-[#e8913a]">Rp 4,5 Jt/bln</span>
                        </div>
                        <ul class="mt-3 space-y-1 text-xs text-slate-600 border-t border-slate-200/60 pt-2.5">
                            <li class="flex items-center gap-1.5 font-semibold text-slate-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Maks 20 Teknisi Lapangan
                            </li>
                            <li class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Hingga 250 Node ODP/ODC
                            </li>
                            <li class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                Auto-Dispatch Jarak GPS
                            </li>
                        </ul>
                    </label>

                    <!-- Enterprise Card (Verified Sales Activation — No Unverified 9999-Node Abuse) -->
                    <div class="relative border-2 border-dashed border-[#0f3559]/40 bg-slate-50/70 rounded-xl p-4 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-display font-bold text-sm text-[#0f3559] block">Enterprise</span>
                                    <span class="text-xs text-slate-500">Tier-1 Telco &amp; Multi-Branch</span>
                                </div>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-[#0f3559] text-white uppercase">Verified</span>
                            </div>
                            <ul class="mt-3 space-y-1 text-xs text-slate-600 border-t border-slate-200/60 pt-2.5">
                                <li class="flex items-center gap-1.5 font-semibold text-slate-800">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    Unlimited Teknisi &amp; ODP
                                </li>
                                <li class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                    Custom Domain &amp; Dedicated SLA
                                </li>
                            </ul>
                        </div>
                        <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.remove('hidden')"
                            class="mt-3 w-full py-2 px-3 rounded-lg bg-[#0f3559] hover:bg-[#011835] text-white text-xs font-bold transition-all cursor-pointer">
                            Hubungi Enterprise Sales &rarr;
                        </button>
                    </div>
                </div>
            </div>

            <!-- Section 3: Akun Super Admin ISP -->
            <div>
                <h3 class="font-display font-bold text-base text-[#0f3559] flex items-center gap-2 mb-4 pb-2 border-b border-slate-100">
                    <span class="w-6 h-6 rounded-full bg-[#0f3559] text-white text-xs flex items-center justify-center font-bold">3</span>
                    Akun Super Admin Utama ISP
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_name">
                            Nama Lengkap Penanggung Jawab *
                        </label>
                        <input id="contact_name" name="contact_name" type="text" value="{{ old('contact_name') }}" required
                            placeholder="Contoh: Budi Santoso, S.T."
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_phone">
                            Nomor WhatsApp / Telepon *
                        </label>
                        <input id="contact_phone" name="contact_phone" type="text" value="{{ old('contact_phone') }}" required
                            placeholder="08123456789"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="contact_email">
                            Email Bisnis (Login Super Admin) *
                        </label>
                        <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email') }}" required
                            placeholder="noc@perusahaan.net.id"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <!-- Spacer for grid alignment -->
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="password">
                            Kata Sandi Baru * (Min 8 Karakter)
                        </label>
                        <input id="password" name="password" type="password" required
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5" for="password_confirmation">
                            Konfirmasi Kata Sandi *
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-[#0f3559] focus:ring-2 focus:ring-[#0f3559]/20 transition-all">
                    </div>
                </div>
            </div>

            <!-- Submit Button & Guarantee Strip -->
            <div class="pt-4 border-t border-slate-200">
                <button type="submit" class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-[#0f3559] to-[#011835] text-white font-display font-bold text-base hover:shadow-lg hover:shadow-[#0f3559]/25 transition-all flex items-center justify-center gap-2">
                    Aktifkan Instance ISP &amp; Mulai Trial 14 Hari &rarr;
                </button>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-6 text-xs text-slate-500 font-medium">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Isolasi Data Database Zero-Leak
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Sertifikat BAST Bersegel SHA-256
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        PWA Android Offline-First
                    </span>
                </div>
            </div>
        </form>
    </main>

    <footer class="max-w-6xl w-full mx-auto text-center py-4 text-xs text-slate-400">
        &copy; 2026 Benteng Siber. Platform Manajemen Layanan Lapangan FiberOps &amp; Pertahanan Siber Telekomunikasi.
    </footer>

    <!-- Enterprise Sales Inquiry Modal -->
    <div id="enterprise-inquiry-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4" onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-[#011835] to-[#0f3559] px-6 py-4 flex items-center justify-between text-white">
                <div>
                    <h3 class="font-display font-bold text-base">Aktivasi Paket Enterprise Carrier</h3>
                    <p class="text-[11px] text-slate-300">Unlimited Teknisi, Custom Domain, Integrasi OLT/MikroTik &amp; Dedicated SLA</p>
                </div>
                <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.add('hidden')" class="text-slate-300 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
            </div>
            <form action="{{ route('tenant.enterprise_inquiry') }}" method="POST" class="p-6 space-y-4 text-xs">
                @csrf
                <input type="text" name="website_hp" value="" class="hidden" tabindex="-1" autocomplete="off">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Perusahaan / ISP *</label>
                        <input type="text" name="enterprise_company" required placeholder="PT Telco Nasional" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama PIC *</label>
                        <input type="text" name="enterprise_name" required placeholder="Direktur / CTO" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Korporat *</label>
                        <input type="email" name="enterprise_email" required placeholder="cto@telco.net.id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">WhatsApp Aktif *</label>
                        <input type="text" name="enterprise_phone" required placeholder="0812..." class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Estimasi Jumlah Teknisi *</label>
                        <input type="number" name="estimated_technicians" min="1" value="50" required class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Estimasi Node ODP/ODC</label>
                        <input type="number" name="estimated_fiber_nodes" min="0" value="500" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kebutuhan Khusus (OLT, Custom Domain, Billing)</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Integrasi 4 OLT ZTE C320 + GenieACS + Custom Domain noc.ispkami.net" class="w-full px-3 py-2 rounded-lg border border-slate-300"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('enterprise-inquiry-modal').classList.add('hidden')" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 font-semibold">Batal</button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-[#e8913a] hover:bg-[#d07e2e] text-white font-bold shadow-sm cursor-pointer">Kirim Permintaan Enterprise &rarr;</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
