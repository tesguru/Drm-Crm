@extends('layouts.app')
@section('title', 'Gmail Accounts')
@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold gradient-text">📧 Gmail Accounts</h1>
        <p style="color: var(--text-secondary);">
            Manage sending accounts
        </p>
    </div>
    <a href="{{ route('auth.google.account') }}"
       class="btn-green flex items-center gap-2">
        ➕ Add Account
    </a>
</div>

{{-- Accounts Grid --}}
<div id="accountsGrid">
    <div class="text-center py-12"
         style="color: var(--text-secondary);">
        <div class="spinner mx-auto mb-3"></div>
        Loading accounts...
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

    return `
    <div class="card" id="account-${a.id}">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                ${a.avatar
                    ? `<img src="${a.avatar}"
                            class="w-12 h-12 rounded-full">`
                    : `<div class="w-12 h-12 rounded-full flex items-center
                                  justify-center text-2xl"
                            style="background: var(--bg-tertiary);">📧</div>`
                }
                <div>
                    <p class="font-bold">${a.email}</p>
                    <p class="text-xs"
                       style="color: var(--text-secondary);">
                        ${a.name || 'Gmail Account'}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="toggleAccount(${a.id}, ${a.is_active})"
                        class="text-sm px-3 py-1.5 rounded-lg font-medium
                               transition-all hover:scale-105"
                        style="background: ${a.is_active
                            ? 'rgba(16,185,129,0.15)'
                            : 'rgba(239,68,68,0.15)'};
                               color: ${a.is_active
                            ? 'var(--accent-green)'
                            : 'var(--accent-red)'};
                               border: 1px solid ${a.is_active
                            ? 'rgba(16,185,129,0.3)'
                            : 'rgba(239,68,68,0.3)'};">
                    ${a.is_active ? '✅ Active' : '❌ Inactive'}
                </button>
                <button onclick="deleteAccount(${a.id})"
                        class="btn-danger text-sm px-3 py-1.5">
                    🗑️
                </button>
            </div>
        </div>

        {{-- Token Status --}}
        <div class="p-3 rounded-xl mb-4 flex items-center
                    justify-between"
             style="background: var(--bg-tertiary);">
            <div class="flex items-center gap-2">
                <span>${tokenIcon}</span>
                <span class="text-sm font-semibold"
                      style="color: ${tokenColor};">
                    Token:
                    ${a.token_status === 'expired'
                        ? 'Expired'
                        : a.token_status === 'valid'
                        ? `Valid · ${a.token_expires_in} mins left`
                        : `Expiring · ${a.token_expires_in} mins left`}
                </span>
            </div>
            ${a.token_status === 'expired' ? `
                <a href="{{ route('auth.google.account') }}"
                   class="text-xs px-3 py-1.5 rounded-lg font-medium"
                   style="background: var(--accent-blue); color: white;">
                    🔄 Refresh
                </a>` : ''
            }
        </div>

        {{-- Daily Progress --}}
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-2">
                <span style="color: var(--text-secondary);">
                    Daily Usage
                </span>
                <span class="font-bold">
                    ${a.daily_sent} / ${a.daily_limit}
                    <span style="color: var(--text-secondary);">
                        (${a.remaining} remaining)
                    </span>
                </span>
            </div>
            <div class="h-2 rounded-full overflow-hidden"
                 style="background: var(--bg-tertiary);">
                <div class="h-full rounded-full transition-all"
                     style="width: ${pct}%;
                            background: ${pct >= 90
                                ? 'var(--accent-red)'
                                : pct >= 70
                                ? 'var(--accent-amber)'
                                : 'var(--accent-green)'};">
                </div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="text-center p-3 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-blue);">
                    ${a.daily_sent}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                     Today
                </div>
            </div>
            <div class="text-center p-3 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-green);">
                    ${a.total_sent}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Total Sent
                </div>
            </div>
            <div class="text-center p-3 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-purple);">
                    ${a.daily_limit}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Daily Limit
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-2">
            <div class="flex-1 flex items-center gap-2">
                <input type="number"
                       id="limit-${a.id}"
                       value="${a.daily_limit}"
                       min="1" max="500"
                       class="input text-sm"
                       placeholder="Daily limit">
                <button onclick="updateLimit(${a.id})"
                        class="btn-primary text-sm px-4 py-2 whitespace-nowrap">
                    Set Limit
                </button>
            </div>
            <button onclick="resetDaily(${a.id})"
                    class="text-sm px-4 py-2 rounded-lg font-medium"
                    style="background: rgba(245,158,11,0.15);
                           color: var(--accent-amber);
                           border: 1px solid rgba(245,158,11,0.3);">
                🔄 Reset
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
    const res = await apiPost(
        `/api/gmail-accounts/${id}/reset-daily`, {}
    );
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