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
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.18) 0%, rgba(37, 99, 235, 0.04) 100%);
            border-left: 3px solid #3B82F6;
            color: #FFFFFF;
            font-weight: 600;
        }
        .nav-item:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.06);
            color: #FFFFFF;
            transform: translateX(3px);
        }
        .nav-item {
            border-left: 3px solid transparent;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease, padding 0.22s ease;
        }

        /* Collapsible Desktop Icon-Rail Sidebar */
        #admin-sidebar {
            transition: width 220ms cubic-bezier(0.22, 1, 0.36, 1), transform 300ms ease-in-out;
        }
        @media (min-width: 768px) {
            html.sidebar-collapsed #admin-sidebar {
                width: 72px;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-label {
                display: none;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-brand {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            html.sidebar-collapsed #admin-sidebar .nav-item {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
                border-left-width: 0;
                position: relative;
            }
            html.sidebar-collapsed #admin-sidebar .nav-item.active {
                background: rgba(37, 99, 235, 0.22);
                box-shadow: inset 3px 0 0 #3B82F6;
            }
            html.sidebar-collapsed #admin-sidebar .nav-item:hover:not(.active) {
                transform: none;
            }
            html.sidebar-collapsed #admin-sidebar .nav-icon {
                margin-right: 0;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-badge {
                position: absolute;
                top: 4px;
                right: 12px;
                margin-left: 0;
                min-width: 16px;
                height: 16px;
                padding: 0 4px;
                font-size: 9px;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-user-row {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-logout-btn {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            html.sidebar-collapsed #admin-sidebar .sidebar-logout-icon {
                margin-right: 0;
            }
            html.sidebar-collapsed #sidebar-collapse-icon {
                transform: rotate(180deg);
            }
        }
    </style>
    <script>
        (function() {
            try {
                if (window.innerWidth >= 768 && localStorage.getItem('fsm_admin_sidebar_collapsed') === '1') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="bg-fsmBg text-textPrimary antialiased flex h-screen overflow-hidden">

    <!-- Sidebar Backdrop for Mobile -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-20 hidden md:hidden transition-opacity"></div>

    <!-- Floating Tooltip for Collapsed Icon-Rail Mode -->
    <div id="sidebar-rail-tooltip" class="fixed left-[82px] z-50 hidden pointer-events-none px-2.5 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-semibold shadow-lg border border-slate-700/80 whitespace-nowrap"></div>

    <!-- Sidebar -->
    <aside id="admin-sidebar" class="w-64 bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col flex-shrink-0 shadow-xl border-r border-slate-700/30 z-30 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0">
        <!-- Logo Header -->
        <a href="{{ route('admin.dashboard') }}" data-tooltip="FiberOps Dashboard" class="sidebar-brand h-16 flex items-center px-6 border-b border-slate-700/50 gap-3 hover:bg-slate-800 transition-colors text-white no-underline">
            <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center shadow-md shadow-blue-500/20 flex-shrink-0">
                <ion-icon name="wifi" class="text-white text-lg"></ion-icon>
            </div>
            <div class="sidebar-label overflow-hidden whitespace-nowrap">
                <span class="text-base font-semibold tracking-tight block leading-tight">FiberOps</span>
                <span class="text-[10px] text-slate-400 font-medium tracking-wider uppercase">FSM Controller</span>
            </div>
        </a>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
            
            <a href="{{ route('admin.dashboard') }}" data-tooltip="Dashboard" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.dashboard') ? 'active text-white' : '' }}">
                <ion-icon name="grid-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Dashboard</span>
            </a>
            
            <a href="{{ route('admin.tasks.index') }}" data-tooltip="Task Management" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.tasks.*') ? 'active text-white' : '' }}">
                <ion-icon name="list-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Task Management</span>
            </a>

            @php $pendingClientBadge = (int) ($pendingClientRequestsCount ?? 0); @endphp
            <a href="{{ route('admin.client_requests.index') }}" data-tooltip="Client Requests" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm relative {{ request()->routeIs('admin.client_requests.*') ? 'active text-white' : '' }}">
                <ion-icon name="mail-unread-outline" class="nav-icon text-lg mr-3 flex-shrink-0 {{ $pendingClientBadge > 0 ? 'text-sky-400' : '' }}"></ion-icon>
                <span class="sidebar-label truncate">Client Requests</span>
                <span id="sidebar-client-requests-badge" class="sidebar-badge ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-slate-950 shadow-sm {{ $pendingClientBadge > 0 ? '' : 'hidden' }}">
                    {{ $pendingClientBadge > 99 ? '99+' : $pendingClientBadge }}
                </span>
            </a>

            <a href="{{ route('admin.inventory.index') }}" data-tooltip="Inventory & Materials" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.inventory.*') ? 'active text-white' : '' }}">
                <ion-icon name="cube-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Inventory &amp; Materials</span>
            </a>

            <a href="{{ route('admin.assignments') }}" data-tooltip="Assignment Monitoring" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.assignments') ? 'active text-white' : '' }}">
                <ion-icon name="calendar-clear-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Assignment Monitoring</span>
            </a>
            
            <a href="{{ route('admin.technicians.index') }}" data-tooltip="Technicians" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.technicians.*') ? 'active text-white' : '' }}">
                <ion-icon name="people-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Technicians</span>
            </a>

            <a href="{{ route('admin.tracking') }}" data-tooltip="Live Tracking" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.tracking') ? 'active text-white' : '' }}">
                <ion-icon name="locate-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Live Tracking</span>
            </a>

            <a href="{{ route('admin.proofs') }}" data-tooltip="Proof of Work" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.proofs') ? 'active text-white' : '' }}">
                <ion-icon name="images-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Proof of Work</span>
            </a>

            <a href="{{ route('admin.history') }}" data-tooltip="Job History" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.history') ? 'active text-white' : '' }}">
                <ion-icon name="archive-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Job History</span>
            </a>

            <a href="{{ route('admin.reports') }}" data-tooltip="Reports" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.reports') ? 'active text-white' : '' }}">
                <ion-icon name="bar-chart-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Reports</span>
            </a>

            <a href="{{ route('admin.settings') }}" data-tooltip="Settings" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.settings') ? 'active text-white' : '' }}">
                <ion-icon name="settings-outline" class="nav-icon text-lg mr-3 flex-shrink-0"></ion-icon>
                <span class="sidebar-label truncate">Settings</span>
            </a>

            <a href="{{ route('admin.guide') }}" data-tooltip="Admin Operations Guide" class="nav-item flex items-center px-3 py-2.5 rounded-xl text-slate-300 text-sm {{ request()->routeIs('admin.guide') ? 'active text-white' : '' }}">
                <ion-icon name="book-outline" class="nav-icon text-lg mr-3 flex-shrink-0 text-amber-400"></ion-icon>
                <span class="sidebar-label truncate">Admin Operations Guide</span>
            </a>
        </nav>
        
        <!-- User Info / Logout Footer -->
        <div class="p-3 border-t border-slate-700/50 bg-slate-900/40">
            <div data-tooltip="{{ auth()->user()->name ?? 'Admin' }} (Administrator)" class="sidebar-user-row flex items-center gap-3 px-2 py-1.5 mb-2">
                <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="sidebar-label overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                    <p class="text-[10px] text-slate-400 truncate">Administrator</p>
                </div>
            </div>
            <button type="button" data-tooltip="Log Out" onclick="document.getElementById('admin-logout-modal').classList.remove('hidden')" class="sidebar-logout-btn flex items-center px-3 py-2 w-full text-left rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all text-xs font-medium cursor-pointer">
                <ion-icon name="log-out-outline" class="sidebar-logout-icon text-base mr-2 pointer-events-none flex-shrink-0"></ion-icon>
                <span class="sidebar-label">Log Out</span>
            </button>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden relative min-w-0">
        <!-- Top Header/Navbar -->
        <header class="h-16 backdrop-blur-md bg-white/90 border-b border-fsmBorder flex items-center justify-between px-4 sm:px-6 z-10 sticky top-0">
            <div class="flex items-center gap-2 sm:gap-3">
                <button id="sidebar-toggle" type="button" title="Toggle Navigation Drawer" class="md:hidden text-slate-500 hover:text-slate-800 focus:outline-none flex items-center justify-center p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                    <ion-icon name="menu-outline" class="text-2xl"></ion-icon>
                </button>
                <button id="sidebar-collapse-btn" type="button" title="Minimize / Expand Sidebar (Ctrl+B)" aria-label="Toggle Sidebar Width"
                    class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 active:scale-95 text-slate-600 hover:text-slate-900 transition-all cursor-pointer">
                    <ion-icon id="sidebar-collapse-icon" name="chevron-back-outline" class="text-base transition-transform duration-200"></ion-icon>
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
                        <div id="notification-list-container" class="py-1 max-h-64 overflow-y-auto">
                            @if(isset($notifications) && count($notifications) > 0)
                                @foreach($notifications as $notif)
                                    <a href="{{ $notif['url'] }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                                        <p class="text-xs font-semibold text-slate-900">{{ $notif['title'] }}</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $notif['note'] }}</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">{{ $notif['time_ago'] }}</p>
                                    </a>
                                @endforeach
                            @else
                                <div id="notification-empty-state" class="px-4 py-6 text-center text-slate-400 text-xs">
                                    <ion-icon name="notifications-off-outline" class="text-2xl mb-1 text-slate-300"></ion-icon>
                                    <p>No new notifications.</p>
                                </div>
                            @endif
                        </div>
                        <div class="px-4 py-2 text-center bg-slate-50 rounded-b-xl">
                            <a href="{{ route('admin.history') }}" class="text-[10px] font-bold text-primary hover:underline">View Full History</a>
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
        <main id="admin-main-content" class="flex-1 overflow-x-hidden overflow-y-auto bg-fsmBg p-4 sm:p-6">
            
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

            <div id="admin-live-page-root">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Global Live Field Update Toast Container (APK -> Admin Instant Sync) -->
    <div id="admin-live-toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none"></div>

    <!-- Global Logout Confirmation Modal -->
    <div id="admin-logout-modal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-sm font-bold text-textPrimary mb-1">Confirm Sign Out</h3>
            <p class="text-xs text-textSecondary mb-5">Are you sure you want to log out of the FiberOps Admin Portal?</p>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('admin-logout-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50">Cancel</button>
                <form action="{{ route('admin.logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm">Log Out</button>
                </form>
            </div>
        </div>
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

            // Desktop Collapsible Icon-Rail Sidebar (Persisted + Leaflet Resize + Hover Tooltips)
            var collapseBtn = document.getElementById('sidebar-collapse-btn');
            var railTooltip = document.getElementById('sidebar-rail-tooltip');

            function updateCollapseAria() {
                if (!collapseBtn) return;
                var collapsed = document.documentElement.classList.contains('sidebar-collapsed');
                collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                collapseBtn.setAttribute('title', collapsed ? 'Expand Sidebar (Ctrl+B)' : 'Minimize Sidebar (Ctrl+B)');
            }

            function toggleDesktopSidebar() {
                if (window.innerWidth < 768) return;
                var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem('fsm_admin_sidebar_collapsed', collapsed ? '1' : '0');
                } catch (e) {}
                if (railTooltip) railTooltip.classList.add('hidden');
                updateCollapseAria();

                // Notify Leaflet maps and responsive grids after CSS width transition settles
                setTimeout(function() {
                    window.dispatchEvent(new Event('resize'));
                }, 240);
            }

            if (collapseBtn) {
                updateCollapseAria();
                collapseBtn.addEventListener('click', toggleDesktopSidebar);
            }

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
                    var tag = (document.activeElement && document.activeElement.tagName || '').toLowerCase();
                    if (tag === 'input' || tag === 'textarea' || tag === 'select' || document.activeElement?.isContentEditable) {
                        return;
                    }
                    e.preventDefault();
                    toggleDesktopSidebar();
                }
            });

            if (sidebar && railTooltip) {
                var tooltipTargets = sidebar.querySelectorAll('[data-tooltip]');
                tooltipTargets.forEach(function(el) {
                    function showTip() {
                        if (window.innerWidth < 768 || !document.documentElement.classList.contains('sidebar-collapsed')) {
                            return;
                        }
                        var label = el.getAttribute('data-tooltip');
                        if (!label) return;
                        var rect = el.getBoundingClientRect();
                        railTooltip.textContent = label;
                        railTooltip.style.top = (rect.top + (rect.height / 2)) + 'px';
                        railTooltip.style.transform = 'translateY(-50%)';
                        railTooltip.classList.remove('hidden');
                    }
                    function hideTip() {
                        railTooltip.classList.add('hidden');
                    }
                    el.addEventListener('mouseenter', showTip);
                    el.addEventListener('mouseleave', hideTip);
                    el.addEventListener('focus', showTip);
                    el.addEventListener('blur', hideTip);
                });
            }

            // ─── Global Bi-Directional Admin Live Pulse Engine (APK -> Admin Instant Sync) ───
            var livePulseUrl = @json(route('admin.live_pulse'));
            var toastContainer = document.getElementById('admin-live-toast-container');
            var notifListContainer = document.getElementById('notification-list-container');
            var livePageRoot = document.getElementById('admin-live-page-root');
            var mainScrollContainer = document.getElementById('admin-main-content');
            var lastGlobalVersion = null;
            var lastEventKey = null;
            var isPollingPulse = false;

            function escHtmlAdmin(str) {
                if (!str) return '';
                var d = document.createElement('div');
                d.textContent = String(str);
                return d.innerHTML;
            }

            function showAdminLiveToast(evt) {
                if (!toastContainer || !evt) return;
                var iconMap = {
                    completed:      { icon: 'checkmark-done-circle', bg: 'bg-emerald-600', ring: 'border-emerald-500/40', label: 'Live Field Sync' },
                    proof:          { icon: 'camera',                bg: 'bg-amber-500',   ring: 'border-amber-400/40',   label: 'Live Field Sync' },
                    inventory:      { icon: 'cube',                  bg: 'bg-violet-600',  ring: 'border-violet-500/40',  label: 'Live Field Sync' },
                    client_request: { icon: 'mail-unread',           bg: 'bg-sky-600',     ring: 'border-sky-400/40',     label: 'New Client Request' },
                    progress:       { icon: 'pulse',                 bg: 'bg-blue-600',    ring: 'border-blue-500/40',    label: 'Live Field Sync' }
                };
                var style = iconMap[evt.type] || iconMap.progress;

                var card = document.createElement('div');
                card.className = 'pointer-events-auto bg-slate-900/95 backdrop-blur-md text-white rounded-2xl p-3.5 shadow-2xl border ' + style.ring + ' flex items-start gap-3 transition-all duration-200';
                card.innerHTML =
                    '<div class="w-9 h-9 rounded-xl ' + style.bg + ' text-white flex items-center justify-center flex-shrink-0 mt-0.5">'
                    + '<ion-icon name="' + style.icon + '" class="text-lg"></ion-icon>'
                    + '</div>'
                    + '<div class="flex-1 min-w-0">'
                    + '<div class="flex items-center justify-between gap-2">'
                    + '<span class="text-[9px] font-extrabold uppercase tracking-wider text-blue-300">' + escHtmlAdmin(style.label) + '</span>'
                    + '<span class="text-[10px] text-slate-400">Just now</span>'
                    + '</div>'
                    + '<p class="text-xs font-bold text-white truncate mt-0.5">' + escHtmlAdmin(evt.title) + '</p>'
                    + '<p class="text-[11px] text-slate-300 truncate mt-0.5">' + escHtmlAdmin(evt.subtitle || '') + '</p>'
                    + (evt.url ? '<a href="' + escHtmlAdmin(evt.url) + '" class="inline-flex items-center gap-1 mt-1.5 text-[11px] font-bold text-blue-400 hover:text-blue-300">Inspect Update &rarr;</a>' : '')
                    + '</div>'
                    + '<button type="button" class="text-slate-400 hover:text-white p-1 cursor-pointer" aria-label="Close">'
                    + '<ion-icon name="close" class="text-sm"></ion-icon>'
                    + '</button>';

                var closeBtn = card.querySelector('button');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        card.remove();
                    });
                }

                toastContainer.prepend(card);
                setTimeout(function() {
                    if (card.parentNode) card.remove();
                }, 6500);

                // Prepend to topbar Notification Bell dropdown list
                if (notifListContainer) {
                    var emptyEl = document.getElementById('notification-empty-state');
                    if (emptyEl) emptyEl.remove();
                    var item = document.createElement('a');
                    item.href = evt.url || '#';
                    item.className = 'block px-4 py-3 bg-blue-50/40 hover:bg-slate-50 transition-colors border-b border-slate-100';
                    item.innerHTML =
                        '<p class="text-xs font-bold text-slate-900">' + escHtmlAdmin(evt.title) + '</p>'
                        + '<p class="text-[10px] text-slate-600 mt-0.5">' + escHtmlAdmin(evt.subtitle || '') + '</p>'
                        + '<p class="text-[9px] text-blue-600 font-semibold mt-0.5">Live &bull; Just now</p>';
                    notifListContainer.prepend(item);
                }
            }

            function canSafelyHotRefreshListPage() {
                var path = window.location.pathname || '';
                // Skip form-editor pages, settings, guide, and pages with their own dedicated Leaflet/live timers
                if (
                    path.endsWith('/create') ||
                    path.endsWith('/edit') ||
                    path.includes('/settings') ||
                    path.includes('/guide') ||
                    path.includes('/tracking') ||
                    /\/tasks\/\d+$/.test(path)
                ) {
                    return false;
                }
                // Do not interrupt if any modal is currently open
                var openModals = document.querySelectorAll('.fixed.inset-0:not(.hidden)');
                if (openModals.length > 0) return false;

                // Do not interrupt if the admin is typing or interacting with an input/select/textarea
                var active = document.activeElement;
                if (active && (['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName) || active.isContentEditable)) {
                    return false;
                }
                return true;
            }

            async function hotRefreshCurrentAdminPage() {
                if (!canSafelyHotRefreshListPage() || !livePageRoot) return;
                var path = window.location.pathname || '';

                try {
                    var scrollTop = mainScrollContainer ? mainScrollContainer.scrollTop : 0;
                    var resp = await fetch(window.location.href, {
                        headers: { 'Accept': 'text/html', 'Cache-Control': 'no-cache' }
                    });
                    if (!resp.ok) return;
                    var html = await resp.text();
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var nextRoot = doc.getElementById('admin-live-page-root');
                    if (!nextRoot || !canSafelyHotRefreshListPage()) return;

                    // On /dashboard, preserve the live Leaflet #dashboard-map instance while hot-swapping KPIs, charts, roster, timeline & table
                    if (path.endsWith('/dashboard') && document.getElementById('dashboard-map')) {
                        var curSections = livePageRoot.querySelectorAll('section');
                        var nextSections = nextRoot.querySelectorAll('section');
                        curSections.forEach(function(sec, idx) {
                            if (!sec.querySelector('#dashboard-map') && nextSections[idx]) {
                                sec.innerHTML = nextSections[idx].innerHTML;
                            }
                        });
                        ['tab-content-roster', 'tab-content-timeline'].forEach(function(id) {
                            var curEl = document.getElementById(id);
                            var nextEl = doc.getElementById(id);
                            if (curEl && nextEl) curEl.innerHTML = nextEl.innerHTML;
                        });
                        document.getElementById('btn-map-sync')?.click();
                        return;
                    }

                    livePageRoot.innerHTML = nextRoot.innerHTML;
                    if (mainScrollContainer) {
                        mainScrollContainer.scrollTop = scrollTop;
                    }
                    // Re-bind backdrop click-to-close on swapped modal overlays
                    livePageRoot.querySelectorAll('.fixed.inset-0').forEach(function(modal) {
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) modal.classList.add('hidden');
                        });
                    });
                } catch (e) {}
            }

            async function checkAdminLivePulse() {
                if (document.hidden || isPollingPulse) return;
                isPollingPulse = true;
                try {
                    var resp = await fetch(livePulseUrl + '?_ts=' + Date.now(), {
                        headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
                    });
                    if (!resp.ok) return;
                    var data = await resp.json();
                    if (!data || !data.version) return;

                    // Live-sync Client Requests pending sidebar counter on every pulse
                    if (data.counts && typeof data.counts.client_requests_pending === 'number') {
                        var badgeEl = document.getElementById('sidebar-client-requests-badge');
                        if (badgeEl) {
                            var pendingReqCount = data.counts.client_requests_pending;
                            badgeEl.textContent = pendingReqCount > 99 ? '99+' : String(pendingReqCount);
                            badgeEl.classList.toggle('hidden', pendingReqCount <= 0);
                        }
                    }

                    if (lastGlobalVersion === null) {
                        lastGlobalVersion = data.version;
                        lastEventKey = data.latest_event ? data.latest_event.key : null;
                        return;
                    }

                    if (data.version !== lastGlobalVersion) {
                        lastGlobalVersion = data.version;
                        if (data.latest_event && data.latest_event.key !== lastEventKey) {
                            lastEventKey = data.latest_event.key;
                            showAdminLiveToast(data.latest_event);
                        }
                        await hotRefreshCurrentAdminPage();
                    }
                } catch (e) {
                } finally {
                    isPollingPulse = false;
                }
            }

            checkAdminLivePulse();
            setInterval(checkAdminLivePulse, 4000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) checkAdminLivePulse();
            });
            window.addEventListener('focus', checkAdminLivePulse);
        });
    </script>
</body>
</html>
