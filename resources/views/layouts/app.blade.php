<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Domain Outreach')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,100..900;1,100..900&display=swap');

        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #13131a;
            --bg-tertiary: #1a1a24;
            --border-color: #252530;
            --text-primary: #e4e4e7;
            --text-secondary: #a1a1aa;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-amber: #f59e0b;
            --accent-red: #ef4444;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: "Urbanist", sans-serif;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 200;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        /* ── Overlay (mobile only) ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 199;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.active { display: block; }

        /* ── Mobile top bar ── */
        .mobile-topbar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            z-index: 198;
            align-items: center;
            padding: 0 16px;
            gap: 12px;
        }

        .hamburger {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background: var(--bg-tertiary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .hamburger:hover { background: var(--border-color); }
        .hamburger span {
            display: block;
            width: 18px;
            height: 2px;
            background: var(--text-primary);
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        /* Animate to X when open */
        .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ── Main content ── */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }

        /* ── Nav items ── */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-radius: 10px;
            margin: 4px 12px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
            text-decoration: none;
        }
        .nav-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        .nav-item.active {
            background: rgba(59,130,246,0.15);
            color: var(--accent-blue);
            border-left: 3px solid var(--accent-blue);
        }

        /* ── Cards & buttons ── */
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
        }
        .btn-primary {
            background: var(--accent-blue);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover { opacity: 0.9; transform: scale(1.02); }
        .btn-green {
            background: var(--accent-green);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-danger {
            background: rgba(239,68,68,0.1);
            color: var(--accent-red);
            border: 1px solid rgba(239,68,68,0.3);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        /* ── Inputs ── */
        .input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-primary);
            outline: none;
            transition: all 0.2s;
        }
        .input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }

        /* ── Badges ── */
        .badge-green { background: rgba(16,185,129,0.15); color: var(--accent-green); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-red   { background: rgba(239,68,68,0.15);  color: var(--accent-red);   padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-blue  { background: rgba(59,130,246,0.15); color: var(--accent-blue);  padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-amber { background: rgba(245,158,11,0.15); color: var(--accent-amber); padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* ── Helpers ── */
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glow-blue { box-shadow: 0 0 30px rgba(59,130,246,0.2); }
        .glow-green { box-shadow: 0 0 30px rgba(16,185,129,0.2); }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            max-width: 350px;
        }
        .toast.show { transform: translateY(0); opacity: 1; }

        /* ── Spinner ── */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            width: 20px; height: 20px;
            border: 2px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        /* ════════════════════════════════
           MOBILE BREAKPOINT  (≤ 768px)
        ════════════════════════════════ */
        @media (max-width: 768px) {

            /* Show topbar, hide sidebar by default */
            .mobile-topbar { display: flex; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }

            /* Push content down below topbar; remove sidebar indent */
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-top: calc(56px + 16px); /* topbar height + gap */
            }

            /* Slightly smaller card padding on mobile */
            .card { padding: 16px; }

            /* Toast fills width on small screens */
            .toast {
                left: 16px;
                right: 16px;
                bottom: 16px;
                max-width: none;
            }
        }
    </style>
</head>
<body>

{{-- ── Mobile top bar ── --}}
<div class="mobile-topbar">
    <button class="hamburger" id="hamburgerBtn" aria-label="Toggle navigation" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <h1 class="text-base font-bold gradient-text">🌐 Domain Outreach</h1>
</div>

{{-- ── Sidebar overlay (closes sidebar when tapping outside) ── --}}
<div class="sidebar-overlay" id="sidebarOverlay"></div>

{{-- ── Sidebar ── --}}
<div class="sidebar" id="sidebar">

    {{-- Logo --}}
    <div class="p-6 border-b" style="border-color: var(--border-color);">
        <h1 class="text-xl font-bold gradient-text">🌐 Domain Outreach</h1>
        <p class="text-xs mt-1" style="color: var(--text-secondary);">Professional Outbound System</p>
    </div>

    {{-- User --}}
    <div class="p-4 border-b" style="border-color: var(--border-color);">
        <div class="flex items-center gap-3">
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}"
                     class="w-9 h-9 rounded-full" alt="Avatar">
            @else
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-lg"
                     style="background: var(--bg-tertiary);">👤</div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate" style="color: var(--text-primary);">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs truncate" style="color: var(--text-secondary);">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 py-4">
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           onclick="closeSidebar()">
            <span>📊</span> Dashboard
        </a>
        <a href="{{ route('campaigns.index') }}"
           class="nav-item {{ request()->routeIs('campaigns*') ? 'active' : '' }}"
           onclick="closeSidebar()">
            <span>🚀</span> Campaigns
        </a>
        <a href="{{ route('templates.index') }}"
           class="nav-item {{ request()->routeIs('templates*') ? 'active' : '' }}"
           onclick="closeSidebar()">
            <span>📝</span> Templates
        </a>
        <a href="{{ route('gmail-accounts.index') }}"
           class="nav-item {{ request()->routeIs('gmail-accounts*') ? 'active' : '' }}"
           onclick="closeSidebar()">
            <span>📧</span> Gmail Accounts
        </a>
    </nav>

    {{-- Logout --}}
    <div class="p-4 border-t" style="border-color: var(--border-color);">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="nav-item w-full text-left"
                    style="color: var(--accent-red);">
                <span>🚪</span> Logout
            </button>
        </form>
    </div>
</div>

{{-- ── Main Content ── --}}
<div class="main-content">
    @yield('content')
</div>

{{-- ── Toast ── --}}
<div id="toast" class="toast">
    <div class="flex items-center gap-3">
        <span id="toastIcon" class="text-xl">✅</span>
        <div>
            <p id="toastTitle" class="font-bold text-sm" style="color: var(--text-primary);"></p>
            <p id="toastMsg"   class="text-xs mt-1"      style="color: var(--text-secondary);"></p>
        </div>
    </div>
</div>

<script>
/* ── CSRF helper ── */
const csrf = document.querySelector('meta[name="csrf-token"]').content;

/* ── Sidebar toggle ── */
const hamburgerBtn    = document.getElementById('hamburgerBtn');
const sidebar         = document.getElementById('sidebar');
const sidebarOverlay  = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('active');
    hamburgerBtn.classList.add('open');
    hamburgerBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden'; // prevent background scroll
}

function closeSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('active');
    hamburgerBtn.classList.remove('open');
    hamburgerBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
}

hamburgerBtn.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
});

sidebarOverlay.addEventListener('click', closeSidebar);

/* Close sidebar on Escape key */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
});

/* ── Toast ── */
function toast(title, msg, type = 'success') {
    const t = document.getElementById('toast');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMsg').textContent   = msg;
    document.getElementById('toastIcon').textContent  =
        type === 'success' ? '✅' :
        type === 'error'   ? '❌' :
        type === 'warning' ? '⚠️' : 'ℹ️';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 4000);
}

/* ── API helpers ── */
function apiPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify(data)
    }).then(r => r.json());
}

function apiGet(url) {
    return fetch(url, { headers: { 'X-CSRF-TOKEN': csrf } }).then(r => r.json());
}

function apiDelete(url) {
    return fetch(url, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf }
    }).then(r => r.json());
}
</script>

@yield('scripts')
</body>
</html>