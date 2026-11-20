<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Work Order — Benteng Siber FiberOps</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563EB'><path d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('build/app.css') }}">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #0B1120;
            background-image: 
                radial-gradient(at 0% 0%, rgba(37, 99, 235, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(6, 182, 212, 0.12) 0px, transparent 50%);
        }
        .cyber-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(51, 65, 85, 0.6);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 30px -10px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-100 antialiased p-4 sm:p-6">

    <!-- Top Header -->
    <header class="max-w-4xl mx-auto w-full flex items-center justify-between py-4">
        <a href="/" class="flex items-center gap-2.5 text-white no-underline group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">
                <ion-icon name="wifi" class="text-white text-lg"></ion-icon>
            </div>
            <div>
                <span class="text-base font-extrabold tracking-tight block leading-tight">Benteng Siber</span>
                <span class="text-[10px] text-cyan-400 font-mono tracking-widest uppercase">FiberOps Live Portal</span>
            </div>
        </a>

        <div class="flex items-center gap-3">
            <a href="{{ route('public.request.create') }}" class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-cyan-500/30 hover:border-cyan-500/60 bg-cyan-950/30">
                <ion-icon name="add-circle-outline" class="text-sm"></ion-icon>
                <span>Request Service</span>
            </a>
            <a href="{{ route('admin.login') }}" class="text-xs font-semibold text-slate-400 hover:text-slate-200 transition-colors px-2 py-1">
                Staff Login
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-lg mx-auto w-full my-auto py-8">
        <div class="cyber-card rounded-2xl p-6 sm:p-8">
            <div class="text-center mb-6">
                <div class="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-400 flex items-center justify-center mx-auto mb-3 shadow-inner">
                    <ion-icon name="locate-outline" class="text-2xl animate-pulse"></ion-icon>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">Track Your Work Order</h1>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    View real-time technician GPS arrival, job progress milestones, and official digital certificate (BAST).
                </p>
            </div>

            @if(session('error'))
                <div class="mb-5 bg-red-950/60 border border-red-500/40 text-red-200 text-xs px-4 py-3 rounded-xl flex items-start gap-2.5">
                    <ion-icon name="alert-circle" class="text-base text-red-400 flex-shrink-0 mt-0.5"></ion-icon>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('tracking.process_lookup') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Ticket Number -->
                <div>
                    <label for="ticket_number" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Work Order Ticket ID <span class="text-cyan-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 font-mono text-xs">
                            #
                        </span>
                        <input type="text" name="ticket_number" id="ticket_number" value="{{ old('ticket_number') }}" required autofocus
                            class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-8 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono transition-all"
                            placeholder="TSK-0012 or 12">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Check your SMS, WhatsApp, or receipt token from our technician.</span>
                </div>

                <!-- Phone Verification -->
                <div>
                    <label for="phone_last4" class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Registered Phone Number <span class="text-cyan-400">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                            <ion-icon name="call-outline"></ion-icon>
                        </span>
                        <input type="text" name="phone_last4" id="phone_last4" value="{{ old('phone_last4') }}" required
                            class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            placeholder="Last 4 digits or full phone number">
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Security verification: Must match the contact phone on the work order.</span>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full mt-2 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <ion-icon name="search-outline" class="text-base"></ion-icon>
                    <span>Find &amp; Track Live Work Order</span>
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <p class="text-xs text-slate-400">
                    Need new fiber installation or reporting an outage?
                    <a href="{{ route('public.request.create') }}" class="text-cyan-400 hover:underline font-semibold block mt-1">
                        Submit a New Service Request &rarr;
                    </a>
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto w-full text-center py-4 text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Benteng Siber &bull; FiberOps Next-Gen Field Service Management Platform</p>
    </footer>

</body>
</html>
