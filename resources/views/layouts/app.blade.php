<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SmartKlon Mart</title>
    <meta name="description" content="Sistem Manajemen Stok Barang Berbasis UHF RFID — SmartKlon Mart">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="app-shell">

    {{-- ===== SIDEBAR ===== --}}
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">SMARTKLON</span>
                <span class="brand-sub">MART</span>
            </div>
        </div>

        <nav class="sidebar-nav">

            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" id="nav-dashboard">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                <span class="nav-label">Dashboard</span>
            </a>

            <a href="{{ route('stock.index') }}" class="nav-item {{ request()->routeIs('stock.*') ? 'active' : '' }}" id="nav-stock">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="currentColor" stroke-width="2"/>
                        <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="currentColor" stroke-width="2"/>
                        <line x1="12" y1="12" x2="12" y2="16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <line x1="10" y1="14" x2="14" y2="14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="nav-label">Ketersediaan Stok</span>
            </a>

            <a href="#" class="nav-item nav-item--soon" id="nav-rack">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <rect x="2" y="3" width="20" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="2" y="10" width="20" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
                        <rect x="2" y="17" width="20" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
                        <line x1="6" y1="7" x2="6" y2="10" stroke="currentColor" stroke-width="2"/>
                        <line x1="18" y1="7" x2="18" y2="10" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                <span class="nav-label">Monitoring Rak</span>
            </a>

            <a href="#" class="nav-item nav-item--soon" id="nav-expiry">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                        <polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="nav-label">Status Kedaluwarsa</span>
            </a>

            <a href="#" class="nav-item nav-item--soon" id="nav-log">
                <span class="nav-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-width="2"/>
                        <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/>
                        <line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2"/>
                        <line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </span>
                <span class="nav-label">Log Kejadian</span>
            </a>
        </nav>


        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="user-details">
                    <span class="user-name">{{ auth()->user()->name }}</span>
                    <span class="user-role">Administrator</span>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Logout" id="btn-logout">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <polyline points="16 17 21 12 16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- ===== MAIN CONTENT ===== --}}
    <main class="main-content" id="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="topbar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>
                <div class="topbar-breadcrumb">
                    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>
            <div class="topbar-right">
                <div class="realtime-indicator" id="realtime-indicator">
                    <span class="indicator-dot"></span>
                    <span class="indicator-text">Real-time</span>
                </div>
                <div class="topbar-time" id="topbar-time"></div>
            </div>
        </header>

        <div class="content-area">
            @if(session('success'))
                <div class="alert alert--success" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2"/><polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert--error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12.01" y2="16" stroke="currentColor" stroke-width="2"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script>
    // Live clock
    function updateClock() {
        const el = document.getElementById('topbar-time');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleString('id-ID', {
                weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar--collapsed');
            mainContent.classList.toggle('main-content--expanded');
        });
    }

    // Auto-dismiss alerts
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });
</script>
@stack('scripts')
</body>
</html>
