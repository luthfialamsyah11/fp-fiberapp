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
                        <div class="py-1 max-h-64 overflow-y-auto">
                            @if(isset($notifications) && count($notifications) > 0)
                                @foreach($notifications as $notif)
                                    <a href="{{ $notif['url'] }}" class="block px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0">
                                        <p class="text-xs font-semibold text-slate-900">{{ $notif['title'] }}</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $notif['note'] }}</p>
                                        <p class="text-[9px] text-slate-400 mt-0.5">{{ $notif['time_ago'] }}</p>
                                    </a>
                                @endforeach
                            @else
                                <div class="px-4 py-6 text-center text-slate-400 text-xs">
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
        });
    </script>
</body>
</html>
