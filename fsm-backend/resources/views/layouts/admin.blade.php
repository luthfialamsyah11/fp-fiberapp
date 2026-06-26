<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - ISP FSM Portal</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
                        fsmBg: '#F8FAFC',
                        fsmSurface: '#FFFFFF',
                        fsmSidebar: '#1E293B',
                        fsmBorder: '#E2E8F0',
                        textPrimary: '#0F172A',
                        textSecondary: '#64748B',
                        fsmSuccess: '#16A34A',
                        fsmWarning: '#F59E0B',
                        fsmDanger: '#DC2626',
                    },
                    borderRadius: {
                        'xl': '12px',
                    }
                }
            }
        }
    </script>
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F8FAFC;
            color: #0F172A;
        }
        /* Custom scrollbar for sidebar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.15) 0%, rgba(37, 99, 235, 0) 100%);
            border-left: 3px solid #3B82F6;
            color: #FFFFFF;
            font-weight: 600;
        }
        .nav-item:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.05);
            color: #FFFFFF;
            transform: translateX(4px);
        }
        .nav-item {
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-fsmBg text-textPrimary antialiased flex h-screen overflow-hidden">

    <!-- Sidebar Backdrop for Mobile -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-20 hidden md:hidden transition-opacity"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar" class="w-64 bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col flex-shrink-0 shadow-xl border-r border-slate-700/30 z-30 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out">
        <!-- Logo Header -->
        <a href="{{ route('admin.dashboard') }}" class="h-16 flex items-center px-6 border-b border-slate-700/50 gap-3 hover:bg-slate-800 transition-colors text-white no-underline">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center shadow-md shadow-blue-500/20">
                <ion-icon name="wifi" class="text-white text-lg"></ion-icon>
            </div>
            <div>
                <span class="text-base font-semibold tracking-tight block leading-tight">FiberOps</span>
                <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase">FSM Controller</span>
            </div>
        </a>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
            
            <a href="{{ route('admin.dashboard') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.dashboard') ? 'active text-white' : '' }}">
                <ion-icon name="grid-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Dashboard</span>
            </a>
            
            <a href="{{ route('admin.tasks.index') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.tasks.*') ? 'active text-white' : '' }}">
                <ion-icon name="list-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Task Management</span>
            </a>

            <a href="{{ route('admin.assignments') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.assignments') ? 'active text-white' : '' }}">
                <ion-icon name="calendar-clear-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Assignment Monitoring</span>
            </a>
            
            <a href="{{ route('admin.technicians.index') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.technicians.*') ? 'active text-white' : '' }}">
                <ion-icon name="people-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Technicians</span>
            </a>

            <a href="{{ route('admin.tracking') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.tracking') ? 'active text-white' : '' }}">
                <ion-icon name="locate-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Live Tracking</span>
            </a>

            <a href="{{ route('admin.proofs') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.proofs') ? 'active text-white' : '' }}">
                <ion-icon name="images-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Proof of Work</span>
            </a>

            <a href="{{ route('admin.history') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.history') ? 'active text-white' : '' }}">
                <ion-icon name="archive-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Job History</span>
            </a>

            <a href="{{ route('admin.reports') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.reports') ? 'active text-white' : '' }}">
                <ion-icon name="bar-chart-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}" class="nav-item flex items-center px-3 py-2.5 rounded-xl transition-all text-slate-300 text-sm {{ request()->routeIs('admin.settings') ? 'active text-white' : '' }}">
                <ion-icon name="settings-outline" class="text-lg mr-3 flex-shrink-0"></ion-icon>
                <span>Settings</span>
            </a>
        </nav>
        
        <!-- User Info / Logout Footer -->
        <div class="p-3 border-t border-slate-700/50 bg-slate-900/40">
            <div class="flex items-center gap-3 px-2 py-1.5 mb-2">
                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-[10px] text-slate-400 truncate">Administrator</p>
                </div>
            </div>
            <form action="{{ route('admin.logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin keluar?')">
                @csrf
                <button type="submit" class="flex items-center px-3 py-2 w-full text-left rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all text-xs font-medium">
                    <ion-icon name="log-out-outline" class="text-sm mr-2"></ion-icon>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden relative">
        <!-- Top Header/Navbar -->
        <header class="h-16 backdrop-blur-md bg-white/90 border-b border-fsmBorder flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0">
            <div class="flex items-center gap-2 sm:gap-3">
                <button id="sidebar-toggle" class="md:hidden text-slate-500 hover:text-slate-800 focus:outline-none flex items-center justify-center p-1 rounded-md hover:bg-slate-100 transition-colors">
                    <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
                </button>
                <h1 class="text-base sm:text-lg font-semibold text-textPrimary truncate max-w-[150px] sm:max-w-none">@yield('header', 'Dashboard')</h1>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Status Badge -->
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 shadow-[0_0_10px_rgba(34,197,94,0.15)]">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-[11px] font-bold text-green-600 tracking-wide">Platform Online</span>
                </div>
                
                <!-- Notification Bell -->
                <div class="relative inline-block text-left" id="notification-dropdown">
                    <button type="button" class="relative cursor-pointer text-slate-500 hover:text-slate-800 transition-colors focus:outline-none flex items-center" id="notification-button">
                        <ion-icon name="notifications-outline" class="text-xl"></ion-icon>
                        <span class="absolute top-0 right-0 h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                    </button>
                    <!-- Dropdown Menu -->
                    <div class="hidden origin-top-right absolute right-0 mt-2 w-80 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none border border-slate-200 divide-y divide-slate-100 z-50" id="notification-menu" role="menu">
                        <div class="px-4 py-3">
                            <p class="text-xs font-bold text-slate-900">Recent Notifications</p>
                        </div>
                        <div class="py-1 max-h-60 overflow-y-auto">
                            <!-- Mock Notifications -->
                            <a href="{{ route('admin.tasks.index', ['status' => 'completed']) }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                                <p class="text-xs font-semibold text-slate-900">Alex Pratama completed job #TSK-0001</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">30 mins ago</p>
                            </a>
                            <a href="{{ route('admin.tasks.index', ['status' => 'on-going']) }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                                <p class="text-xs font-semibold text-slate-900">New Fiber connection requested by Lestari Indah</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">1 hour ago</p>
                            </a>
                            <a href="{{ route('admin.tracking') }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                                <p class="text-xs font-semibold text-slate-900">Budi Santoso active at SCBD Site</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">2 hours ago</p>
                            </a>
                        </div>
                        <div class="px-4 py-2 text-center bg-slate-50 rounded-b-xl">
                            <a href="#" class="text-[10px] font-bold text-primary hover:underline">Mark all as read</a>
                        </div>
                    </div>
                </div>
                
                <!-- Separator -->
                <div class="h-6 w-px bg-fsmBorder"></div>
                
                <div class="flex items-center gap-2">
                    <div class="text-right hidden sm:block">
                        <span class="text-xs font-medium text-textPrimary block">{{ auth()->user()->name ?? 'Admin FSM' }}</span>
                        <span class="text-[10px] text-textSecondary block">Super Admin</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Viewport Scroll Container -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-fsmBg p-4 sm:p-6">
            
            <!-- Session Messages -->
            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl shadow-sm text-sm" role="alert">
                    <ion-icon name="checkmark-circle-outline" class="text-xl text-green-600"></ion-icon>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl shadow-sm text-sm" role="alert">
                    <ion-icon name="alert-circle-outline" class="text-xl text-red-600"></ion-icon>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Notification Dropdown Logic
            var notifButton = document.getElementById('notification-button');
            var notifMenu = document.getElementById('notification-menu');

            if (notifButton && notifMenu) {
                notifButton.addEventListener('click', function(event) {
                    event.stopPropagation();
                    notifMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', function() {
                    notifMenu.classList.add('hidden');
                });

                notifMenu.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            }

            // Mobile Sidebar Toggle Logic
            var sidebarToggle = document.getElementById('sidebar-toggle');
            var sidebar = document.getElementById('admin-sidebar');
            var backdrop = document.getElementById('sidebar-backdrop');

            if (sidebarToggle && sidebar && backdrop) {
                function toggleSidebar() {
                    sidebar.classList.toggle('-translate-x-full');
                    backdrop.classList.toggle('hidden');
                }
                sidebarToggle.addEventListener('click', toggleSidebar);
                backdrop.addEventListener('click', toggleSidebar);
            }
        });
    </script>
</body>
</html>
