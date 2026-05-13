@extends('layouts.app')
@section('title', 'Gmail Accounts')
@section('content')

<style>
    /* ── Page header ── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    .page-header h1 { font-size: clamp(1.4rem, 5vw, 1.875rem); }

    /* ── Account card ── */
    .account-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .account-email-info {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        flex: 1;
    }
    .account-email-info p.email {
        font-weight: 700;
        word-break: break-all;
        font-size: 0.9rem;
    }
    .account-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    /* ── Token status row ── */
    .token-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
        padding: 12px;
        border-radius: 12px;
        margin-bottom: 16px;
        background: var(--bg-tertiary);
    }

    /* ── Stats grid: 3 cols on desktop, 3 cols on mobile too (compact) ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 16px;
    }
    .stat-box {
        text-align: center;
        padding: 10px 6px;
        border-radius: 12px;
        background: var(--bg-tertiary);
    }
    .stat-box .stat-value { font-size: 1.1rem; font-weight: 700; }
    .stat-box .stat-label { font-size: 0.65rem; color: var(--text-secondary); }

    /* ── Bottom actions row ── */
    .card-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .limit-group {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        min-width: 0;
    }
    .limit-group input { flex: 1; min-width: 0; }
    .reset-btn {
        text-sm px-4 py-2 rounded-lg font-medium;
        flex-shrink: 0;
        font-size: 0.8rem;
        padding: 9px 14px;
        border-radius: 10px;
        font-weight: 600;
        background: rgba(245,158,11,0.15);
        color: var(--accent-amber);
        border: 1px solid rgba(245,158,11,0.3);
        cursor: pointer;
        white-space: nowrap;
    }

    /* ── Mobile tweaks ── */
    @media (max-width: 480px) {
        .account-actions {
            width: 100%;
            justify-content: flex-end;
        }
        .card-actions { flex-direction: column; }
        .limit-group { width: 100%; }
        .reset-btn { width: 100%; text-align: center; }
    }
</style>

{{-- Page header --}}
<div class="page-header">
    <div>
        <h1 class="font-bold gradient-text">📧 Gmail Accounts</h1>
        <p style="color: var(--text-secondary);">Manage sending accounts</p>
    </div>
    <a href="{{ route('auth.google.account') }}"
       class="btn-green flex items-center gap-2 text-sm" style="white-space:nowrap;">
        ➕ Add Account
    </a>
</div>

{{-- Accounts Grid --}}
<div id="accountsGrid">
    <div class="text-center py-12" style="color: var(--text-secondary);">
        <div class="spinner mx-auto mb-3"></div>
        Loading accounts…
    </div>
</div>

@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', loadAccounts);

async function loadAccounts() {
    const res = await apiGet('/api/gmail-accounts');
    const el  = document.getElementById('accountsGrid');

    if (!res.success || !res.accounts.length) {
        el.innerHTML = `
            <div class="card text-center py-12">
                <div class="text-5xl mb-4">📭</div>
                <p class="text-lg font-bold mb-2">No accounts connected</p>
                <p style="color: var(--text-secondary);" class="mb-6">
                    Connect your Gmail accounts to start sending
                </p>
                <a href="{{ route('auth.google.account') }}"
                   class="btn-green inline-block">
                    ➕ Connect Gmail Account
                </a>
            </div>`;
        return;
    }

    el.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            ${res.accounts.map(a => accountCard(a)).join('')}
        </div>`;
}

function accountCard(a) {
    const tokenColor =
        a.token_status === 'valid'    ? 'var(--accent-green)'  :
        a.token_status === 'expiring' ? 'var(--accent-amber)'  :
        a.token_status === 'critical' ? 'var(--accent-red)'    :
        a.token_status === 'expired'  ? 'var(--accent-red)'    :
        'var(--text-secondary)';

    const tokenIcon =
        a.token_status === 'valid'    ? '🟢' :
        a.token_status === 'expiring' ? '🟡' :
        a.token_status === 'critical' ? '🟠' :
        a.token_status === 'expired'  ? '🔴' : '⚪';

    const pct = Math.round((a.daily_sent / a.daily_limit) * 100);

    const progressColor = pct >= 90
        ? 'var(--accent-red)'
        : pct >= 70
        ? 'var(--accent-amber)'
        : 'var(--accent-green)';

    const tokenLabel = a.token_status === 'expired'
        ? 'Expired'
        : a.token_status === 'valid'
        ? `Valid · ${a.token_expires_in} mins left`
        : `Expiring · ${a.token_expires_in} mins left`;

    const avatarHtml = a.avatar
        ? `<img src="${a.avatar}" class="w-11 h-11 rounded-full flex-shrink-0">`
        : `<div class="w-11 h-11 rounded-full flex items-center justify-center text-2xl flex-shrink-0"
               style="background: var(--bg-tertiary);">📧</div>`;

    const refreshBtn = a.token_status === 'expired'
        ? `<a href="{{ route('auth.google.account') }}"
               class="text-xs px-3 py-1.5 rounded-lg font-medium flex-shrink-0"
               style="background: var(--accent-blue); color: white; white-space:nowrap;">
               🔄 Refresh
           </a>`
        : '';

    return `
    <div class="card" id="account-${a.id}">

        {{-- Header --}}
        <div class="account-card-header">
            <div class="account-email-info">
                ${avatarHtml}
                <div style="min-width:0;">
                    <p class="email">${a.email}</p>
                    <p class="text-xs" style="color: var(--text-secondary);">
                        ${a.name || 'Gmail Account'}
                    </p>
                </div>
            </div>
            <div class="account-actions">
                <button onclick="toggleAccount(${a.id}, ${a.is_active})"
                        class="text-xs px-3 py-1.5 rounded-lg font-medium transition-all"
                        style="background: ${a.is_active ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)'};
                               color: ${a.is_active ? 'var(--accent-green)' : 'var(--accent-red)'};
                               border: 1px solid ${a.is_active ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'};
                               white-space: nowrap;">
                    ${a.is_active ? '✅ Active' : '❌ Inactive'}
                </button>
                <button onclick="deleteAccount(${a.id})"
                        class="btn-danger text-sm px-3 py-1.5 flex-shrink-0">
                    🗑️
                </button>
            </div>
        </div>

        {{-- Token Status --}}
        <div class="token-row">
            <div class="flex items-center gap-2 min-w-0">
                <span>${tokenIcon}</span>
                <span class="text-sm font-semibold" style="color: ${tokenColor};">
                    Token: ${tokenLabel}
                </span>
            </div>
            ${refreshBtn}
        </div>

        {{-- Daily Progress --}}
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-2" style="flex-wrap:wrap; gap:4px;">
                <span style="color: var(--text-secondary);">Daily Usage</span>
                <span class="font-bold">
                    ${a.daily_sent} / ${a.daily_limit}
                    <span style="color: var(--text-secondary);">(${a.remaining} left)</span>
                </span>
            </div>
            <div class="h-2 rounded-full overflow-hidden" style="background: var(--bg-tertiary);">
                <div class="h-full rounded-full transition-all"
                     style="width: ${pct}%; background: ${progressColor};"></div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-value" style="color: var(--accent-blue);">${a.daily_sent}</div>
                <div class="stat-label">Today</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--accent-green);">${a.total_sent}</div>
                <div class="stat-label">Total Sent</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: var(--accent-purple);">${a.daily_limit}</div>
                <div class="stat-label">Daily Limit</div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="card-actions">
            <div class="limit-group">
                <input type="number"
                       id="limit-${a.id}"
                       value="${a.daily_limit}"
                       min="1" max="500"
                       class="input text-sm"
                       placeholder="Daily limit">
                <button onclick="updateLimit(${a.id})"
                        class="btn-primary text-sm px-4 py-2"
                        style="white-space:nowrap; flex-shrink:0;">
                    Set Limit
                </button>
            </div>
            <button onclick="resetDaily(${a.id})" class="reset-btn">
                🔄 Reset Daily
            </button>
        </div>

    </div>`;
}

async function toggleAccount(id, isActive) {
    const res = await apiPost(`/api/gmail-accounts/${id}/toggle`, {});
    if (res.success) {
        toast('Updated', res.message, 'success');
        loadAccounts();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function updateLimit(id) {
    const limit = document.getElementById(`limit-${id}`).value;
    const res   = await apiPost(`/api/gmail-accounts/${id}/limit`, {
        daily_limit: parseInt(limit)
    });
    if (res.success) {
        toast('Updated', 'Daily limit updated', 'success');
        loadAccounts();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function resetDaily(id) {
    const res = await apiPost(`/api/gmail-accounts/${id}/reset-daily`, {});
    if (res.success) {
        toast('Reset', 'Daily count reset to 0', 'success');
        loadAccounts();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function deleteAccount(id) {
    if (!confirm('Remove this Gmail account?')) return;
    const res = await apiDelete(`/api/gmail-accounts/${id}`);
    if (res.success) {
        toast('Removed', 'Account removed', 'success');
        loadAccounts();
    } else {
        toast('Error', res.error, 'error');
    }
}
</script>
@endsection