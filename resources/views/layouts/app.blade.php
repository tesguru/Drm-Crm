<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Domain Outreach')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0; left: 0;
        }
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 24px;
        }
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
        .badge-green {
            background: rgba(16,185,129,0.15);
            color: var(--accent-green);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-red {
            background: rgba(239,68,68,0.15);
            color: var(--accent-red);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-blue {
            background: rgba(59,130,246,0.15);
            color: var(--accent-blue);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-amber {
            background: rgba(245,158,11,0.15);
            color: var(--accent-amber);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glow-blue { box-shadow: 0 0 30px rgba(59,130,246,0.2); }
        .glow-green { box-shadow: 0 0 30px rgba(16,185,129,0.2); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
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
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .spinner {
            width: 20px; height: 20px;
            border: 2px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

{{-- Sidebar --}}
<div class="sidebar flex flex-col">

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
                <p class="text-sm font-semibold truncate"
                   style="color: var(--text-primary);">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs truncate"
                   style="color: var(--text-secondary);">
                    {{ auth()->user()->email }}
                </p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 py-4">
        <a href="{{ route('dashboard') }}"
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span>📊</span> Dashboard
        </a>
        <a href="{{ route('campaigns.index') }}"
           class="nav-item {{ request()->routeIs('campaigns*') ? 'active' : '' }}">
            <span>🚀</span> Campaigns
        </a>
        <a href="{{ route('templates.index') }}"
           class="nav-item {{ request()->routeIs('templates*') ? 'active' : '' }}">
            <span>📝</span> Templates
        </a>
        <a href="{{ route('gmail-accounts.index') }}"
           class="nav-item {{ request()->routeIs('gmail-accounts*') ? 'active' : '' }}">
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

{{-- Main Content --}}
<div class="main-content">
    @yield('content')
</div>

{{-- Toast --}}
<div id="toast" class="toast">
    <div class="flex items-center gap-3">
        <span id="toastIcon" class="text-xl">✅</span>
        <div>
            <p id="toastTitle" class="font-bold text-sm"
               style="color: var(--text-primary);"></p>
            <p id="toastMsg" class="text-xs mt-1"
               style="color: var(--text-secondary);"></p>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

function toast(title, msg, type = 'success') {
    const t = document.getElementById('toast');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMsg').textContent = msg;
    document.getElementById('toastIcon').textContent =
        type === 'success' ? '✅' :
        type === 'error'   ? '❌' :
        type === 'warning' ? '⚠️' : 'ℹ️';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 4000);
}

function apiPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf
        },
        body: JSON.stringify(data)
    }).then(r => r.json());
}

function apiGet(url) {
    return fetch(url, {
        headers: { 'X-CSRF-TOKEN': csrf }
    }).then(r => r.json());
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