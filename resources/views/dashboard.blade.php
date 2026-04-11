@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

{{-- Header --}}
<div class="mb-8 flex items-center justify-between flex-wrap gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text">
            📊 Dashboard
        </h1>
        <p style="color: var(--text-secondary);">
            Welcome back, {{ auth()->user()->name }}!
        </p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('campaigns.index') }}"
           class="btn-primary flex items-center gap-2">
            🚀 New Campaign
        </a>
        <a href="{{ route('auth.google.account') }}"
           class="btn-green flex items-center gap-2">
            ➕ Add Account
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="card text-center glow-blue">
        <div class="text-4xl font-bold mb-1"
             style="color: var(--accent-blue);"
             id="statCampaigns">
            <div class="spinner mx-auto"></div>
        </div>
        <div class="text-sm"
             style="color: var(--text-secondary);">
            Campaigns
        </div>
    </div>
    <div class="card text-center glow-green">
        <div class="text-4xl font-bold mb-1"
             style="color: var(--accent-green);"
             id="statSent">
            <div class="spinner mx-auto"></div>
        </div>
        <div class="text-sm"
             style="color: var(--text-secondary);">
            Emails Sent
        </div>
    </div>
    <div class="card text-center"
         style="box-shadow: 0 0 30px rgba(139,92,246,0.2);">
        <div class="text-4xl font-bold mb-1"
             style="color: var(--accent-purple);"
             id="statReplies">
            <div class="spinner mx-auto"></div>
        </div>
        <div class="text-sm"
             style="color: var(--text-secondary);">
            Replies
        </div>
    </div>
    <div class="card text-center glow-amber"
         style="box-shadow: 0 0 30px rgba(245,158,11,0.2);">
        <div class="text-4xl font-bold mb-1"
             style="color: var(--accent-amber);"
             id="statAccounts">
            <div class="spinner mx-auto"></div>
        </div>
        <div class="text-sm"
             style="color: var(--text-secondary);">
            Gmail Accounts
        </div>
    </div>
</div>

{{-- Two Column Layout --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Gmail Accounts Status --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold flex items-center gap-2">
                📧 Gmail Accounts
            </h2>
            <div class="flex gap-2">
                <button onclick="loadAccounts()"
                        class="text-xs px-3 py-1.5 rounded-lg"
                        style="background: var(--bg-tertiary);
                               color: var(--text-secondary);
                               border: 1px solid var(--border-color);">
                    🔄 Refresh
                </button>
                <a href="{{ route('auth.google.account') }}"
                   class="text-xs px-3 py-1.5 rounded-lg"
                   style="background: var(--accent-green);
                          color: white;">
                    ➕ Add
                </a>
            </div>
        </div>
        <div id="accountsList">
            <div class="text-center py-6"
                 style="color: var(--text-secondary);">
                <div class="spinner mx-auto mb-2"></div>
                Loading...
            </div>
        </div>
    </div>

    {{-- Templates Status --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold flex items-center gap-2">
                📝 Templates
            </h2>
            <a href="{{ route('templates.index') }}"
               class="text-xs px-3 py-1.5 rounded-lg"
               style="background: var(--accent-blue);
                      color: white;">
                Manage →
            </a>
        </div>
        <div id="templateStatus">
            <div class="text-center py-6"
                 style="color: var(--text-secondary);">
                <div class="spinner mx-auto mb-2"></div>
                Loading...
            </div>
        </div>
    </div>
</div>

{{-- Queue Status --}}
<div class="card mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold flex items-center gap-2">
            ⚙️ Queue Status
        </h2>
        <button onclick="loadQueueStatus()"
                class="text-xs px-3 py-1.5 rounded-lg"
                style="background: var(--bg-tertiary);
                       color: var(--text-secondary);
                       border: 1px solid var(--border-color);">
            🔄 Refresh
        </button>
    </div>
    <div id="queueStatus">
        <div class="text-center py-6"
             style="color: var(--text-secondary);">
            <div class="spinner mx-auto mb-2"></div>
            Checking queue...
        </div>
    </div>
</div>

{{-- Recent Campaigns --}}
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold flex items-center gap-2">
            🚀 Recent Campaigns
        </h2>
        <a href="{{ route('campaigns.index') }}"
           style="color: var(--accent-blue);"
           class="text-sm">
            View All →
        </a>
    </div>
    <div id="recentCampaigns">
        <div class="text-center py-6"
             style="color: var(--text-secondary);">
            <div class="spinner mx-auto mb-2"></div>
            Loading...
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadAccounts();
    loadTemplateStatus();
    loadQueueStatus();
    loadRecentCampaigns();

    // Auto refresh every 30 seconds
    setInterval(() => {
        loadStats();
        loadAccounts();
        loadQueueStatus();
    }, 30000);
});

// ============================================================
// STATS
// ============================================================
async function loadStats() {
    const [camps, accounts] = await Promise.all([
        apiGet('/api/campaigns'),
        apiGet('/api/gmail-accounts')
    ]);

    if (camps.success) {
        const c = camps.campaigns;
        document.getElementById('statCampaigns').textContent =
            c.length;
        document.getElementById('statSent').textContent =
            c.reduce((a, b) => a + b.sent_count, 0);
        document.getElementById('statReplies').textContent =
            c.reduce((a, b) => a + b.replied_count, 0);
    }

    if (accounts.success) {
        document.getElementById('statAccounts').textContent =
            accounts.accounts.length;
    }
}

// ============================================================
// ACCOUNTS
// ============================================================
async function loadAccounts() {
    const res = await apiGet('/api/gmail-accounts');
    const el  = document.getElementById('accountsList');

    if (!res.success || !res.accounts.length) {
        el.innerHTML = `
            <div class="text-center py-6">
                <p style="color: var(--text-secondary);">
                    No accounts connected.
                </p>
                <a href="{{ route('auth.google.account') }}"
                   class="text-sm mt-2 inline-block"
                   style="color: var(--accent-blue);">
                    Connect Gmail Account →
                </a>
            </div>`;
        return;
    }

    el.innerHTML = res.accounts.map(a => `
        <div class="flex items-center justify-between
                    p-3 rounded-xl mb-2"
             style="background: var(--bg-tertiary);
                    border: 1px solid var(--border-color);">
            <div class="flex items-center gap-3">
                ${a.avatar
                    ? `<img src="${a.avatar}"
                            class="w-9 h-9 rounded-full">`
                    : `<div class="w-9 h-9 rounded-full
                                  flex items-center justify-center"
                            style="background: var(--bg-secondary);">
                           📧
                       </div>`
                }
                <div>
                    <p class="text-sm font-semibold">
                        ${a.email}
                    </p>
                    <p class="text-xs"
                       style="color: var(--text-secondary);">
                        ${a.daily_sent}/${a.daily_limit} today
                        · ${a.remaining} remaining
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                ${tokenBadge(a.token_status, a.token_expires_in)}
                <span class="${a.is_active
                    ? 'badge-green' : 'badge-red'}">
                    ${a.is_active ? 'Active' : 'Off'}
                </span>
            </div>
        </div>
    `).join('');
}

function tokenBadge(status, mins) {
    if (status === 'valid')
        return `<span class="badge-green">
                    🔑 ${mins}m
                </span>`;
    if (status === 'expiring')
        return `<span class="badge-amber">
                    ⚠️ ${mins}m
                </span>`;
    if (status === 'critical')
        return `<span class="badge-red">
                    🔴 ${mins}m
                </span>`;
    if (status === 'expired')
        return `<span class="badge-red">❌ Expired</span>`;
    return `<span class="badge-blue">🔑 —</span>`;
}

// ============================================================
// TEMPLATE STATUS
// ============================================================
async function loadTemplateStatus() {
    const res = await apiGet('/api/templates/all');
    const el  = document.getElementById('templateStatus');

    if (!res.success) {
        el.innerHTML = `
            <p style="color: var(--text-secondary);">
                Failed to load templates
            </p>`;
        return;
    }

    const types = [
        { key: 'bulk_template', label: '📧 Initial',  color: 'var(--accent-blue)' },
        { key: 'followup_1',    label: '🔄 FU 1',     color: 'var(--accent-green)' },
        { key: 'followup_2',    label: '🔄 FU 2',     color: 'var(--accent-purple)' },
        { key: 'followup_3',    label: '🔄 FU 3',     color: 'var(--accent-amber)' },
        { key: 'followup_4',    label: '🔄 FU 4',     color: 'var(--accent-blue)' },
        { key: 'followup_5',    label: '🔄 FU 5',     color: 'var(--accent-green)' },
    ];

    const templates = res.templates || {};

    el.innerHTML = `
        <div class="space-y-3">
            ${types.map(t => {
                const count = templates[t.key]?.length || 0;
                const pct   = Math.round((count / 6) * 100);
                return `
                    <div>
                        <div class="flex justify-between
                                    text-sm mb-1">
                            <span>${t.label}</span>
                            <span class="font-bold"
                                  style="color: ${t.color};">
                                ${count}/6
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full"
                             style="background: var(--bg-tertiary);">
                            <div class="h-full rounded-full"
                                 style="width: ${pct}%;
                                        background: ${t.color};
                                        transition: width 0.5s;">
                            </div>
                        </div>
                    </div>`;
            }).join('')}
        </div>
        <a href="{{ route('templates.index') }}"
           class="block text-center mt-4 text-sm py-2 rounded-xl"
           style="background: rgba(59,130,246,0.1);
                  color: var(--accent-blue);">
            Manage All Templates →
        </a>`;
}

// ============================================================
// QUEUE STATUS
// ============================================================
async function loadQueueStatus() {
    const res = await apiGet('/api/campaigns');
    const el  = document.getElementById('queueStatus');

    if (!res.success) return;

    const pending = res.campaigns.reduce(
        (a, c) => a + (c.pending_count || 0), 0
    );
    const failed  = res.campaigns.reduce(
        (a, c) => a + (c.failed_count || 0), 0
    );

    el.innerHTML = `
        <div class="grid grid-cols-3 gap-4">
            <div class="text-center p-4 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-2xl font-bold mb-1 ${
                    pending > 0
                    ? 'text-yellow-400'
                    : ''}"
                     style="${pending === 0
                        ? 'color: var(--accent-green);' : ''}">
                    ${pending}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Pending
                </div>
            </div>
            <div class="text-center p-4 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-2xl font-bold mb-1"
                     style="color: ${
                        failed > 0
                        ? 'var(--accent-red)'
                        : 'var(--accent-green)'};">
                    ${failed}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Failed
                </div>
            </div>
            <div class="text-center p-4 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="text-2xl font-bold mb-1"
                     style="color: var(--accent-blue);">
                    ${res.campaigns.length}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Campaigns
                </div>
            </div>
        </div>

        ${pending > 0 ? `
        <div class="mt-4 p-3 rounded-xl flex items-center gap-3"
             style="background: rgba(245,158,11,0.08);
                    border: 1px solid rgba(245,158,11,0.2);">
            <div class="spinner"
                 style="border-top-color: var(--accent-amber);">
            </div>
            <div>
                <p class="text-sm font-bold"
                   style="color: var(--accent-amber);">
                    ${pending} emails in queue
                </p>
                <p class="text-xs"
                   style="color: var(--text-secondary);">
                    Make sure queue worker is running:
                    <code style="color: var(--accent-green);">
                        php artisan queue:work
                    </code>
                </p>
            </div>
        </div>` : `
        <div class="mt-4 p-3 rounded-xl flex items-center gap-3"
             style="background: rgba(16,185,129,0.08);
                    border: 1px solid rgba(16,185,129,0.2);">
            <span class="text-xl">✅</span>
            <p class="text-sm font-bold"
               style="color: var(--accent-green);">
                Queue is clear — all emails sent
            </p>
        </div>`}

        ${failed > 0 ? `
        <div class="mt-3 p-3 rounded-xl flex items-center
                    justify-between"
             style="background: rgba(239,68,68,0.08);
                    border: 1px solid rgba(239,68,68,0.2);">
            <div class="flex items-center gap-3">
                <span class="text-xl">❌</span>
                <p class="text-sm font-bold"
                   style="color: var(--accent-red);">
                    ${failed} emails failed
                </p>
            </div>
            <a href="{{ route('campaigns.index') }}"
               class="text-xs px-3 py-1.5 rounded-lg"
               style="background: var(--accent-red);
                      color: white;">
                View & Retry →
            </a>
        </div>` : ''}
    `;
}

// ============================================================
// RECENT CAMPAIGNS
// ============================================================
async function loadRecentCampaigns() {
    const res = await apiGet('/api/campaigns');
    const el  = document.getElementById('recentCampaigns');

    if (!res.success || !res.campaigns.length) {
        el.innerHTML = `
            <div class="text-center py-8">
                <div class="text-5xl mb-3">🚀</div>
                <p class="font-bold mb-2">No campaigns yet</p>
                <p style="color: var(--text-secondary);"
                   class="mb-4">
                    Create your first outbound campaign
                </p>
                <a href="{{ route('campaigns.index') }}"
                   class="btn-primary inline-block">
                    ➕ New Campaign
                </a>
            </div>`;
        return;
    }

    el.innerHTML = res.campaigns.slice(0, 5).map(c => `
        <div class="flex items-center justify-between
                    p-4 rounded-xl mb-3 cursor-pointer
                    transition-all hover:border-blue-500/30"
             style="background: var(--bg-tertiary);
                    border: 1px solid var(--border-color);"
             onclick="window.location='/campaigns/${c.id}'">
            <div class="flex-1 min-w-0 mr-4">
                <div class="flex items-center gap-2 mb-1">
                    <p class="font-bold truncate">${c.name}</p>
                    <span class="${c.status === 'active'
                        ? 'badge-green' : 'badge-amber'}
                        text-xs">
                        ${c.status}
                    </span>
                </div>
                <p class="text-xs truncate"
                   style="color: var(--text-secondary);">
                    ${c.domain} · ${c.price}
                </p>
            </div>
            <div class="flex items-center gap-4 text-sm
                        flex-shrink-0">
                <div class="text-center">
                    <div class="font-bold"
                         style="color: var(--accent-green);">
                        ${c.sent_count}
                    </div>
                    <div class="text-xs"
                         style="color: var(--text-secondary);">
                        Sent
                    </div>
                </div>
                <div class="text-center">
                    <div class="font-bold"
                         style="color: var(--accent-purple);">
                        ${c.replied_count}
                    </div>
                    <div class="text-xs"
                         style="color: var(--text-secondary);">
                        Replied
                    </div>
                </div>
                <div class="text-center">
                    <div class="font-bold"
                         style="color: var(--accent-amber);">
                        ${c.follow_up_count}
                    </div>
                    <div class="text-xs"
                         style="color: var(--text-secondary);">
                        FU
                    </div>
                </div>
                ${c.pending_count > 0 ? `
                <div class="spinner"
                     style="border-top-color:
                            var(--accent-amber);">
                </div>` : ''}
            </div>
        </div>
    `).join('');
}
</script>
@endsection