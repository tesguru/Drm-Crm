@extends('layouts.app')
@section('title', 'Campaign Detail')
@section('content')

<div id="campaignDetail">
    <div class="text-center py-12"
         style="color: var(--text-secondary);">
        <div class="spinner mx-auto mb-3"></div>
        Loading campaign...
    </div>
</div>

@endsection
@section('scripts')
<script>
const campaignId = {{ $id }};
let refreshTimer  = null;
let countdownTimers = {};

document.addEventListener('DOMContentLoaded', () => {
    loadCampaign();
    // Auto refresh every 15 seconds
    refreshTimer = setInterval(loadCampaign, 15000);
});

async function loadCampaign() {
    const res = await apiGet(`/api/campaigns/${campaignId}`);
    const el  = document.getElementById('campaignDetail');

    if (!res.success) {
        el.innerHTML = `
            <div class="card text-center py-12">
                <div class="text-5xl mb-4">❌</div>
                <p style="color: var(--accent-red);">
                    Campaign not found
                </p>
            </div>`;
        return;
    }

    const c = res.campaign;

    el.innerHTML = `
        {{-- Header --}}
        <div class="flex items-center gap-4 mb-6 flex-wrap">
            <a href="{{ route('campaigns.index') }}"
               class="text-2xl hover:opacity-70">←</a>
            <div class="flex-1">
                <h1 class="text-3xl font-bold gradient-text">
                    ${c.name}
                </h1>
                <p style="color: var(--text-secondary);">
                    ${c.domain} · ${c.price} · ${c.your_name}
                </p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <button onclick="checkReplies()"
                        id="checkRepliesBtn"
                        class="btn-primary flex items-center gap-2">
                    🔍 Check Replies
                </button>
                <button onclick="sendFollowUps()"
                        id="followUpBtn"
                        class="btn-green flex items-center gap-2">
                    🔄 Send Follow-ups
                </button>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
           ${statBox('Total',      c.total_emails,   'var(--accent-blue)')}
${statBox('Sent',       c.sent_count,     'var(--accent-green)')}
${statBox('Replied',    c.replied_count,  'var(--accent-purple)')}
${statBox('Bounced',    c.bounce_count,   'var(--accent-red)')}      
${statBox('Follow-ups', c.follow_up_count,'var(--accent-amber)')}
        </div>

        {{-- Live Queue Tracker --}}
        <div class="card mb-6">
            <div class="flex items-center
                        justify-between mb-4">
                <h2 class="text-lg font-bold
                            flex items-center gap-2">
                    ⚡ Live Queue Tracker
                </h2>
                <div class="flex items-center gap-2">
                    ${c.emails.filter(
                        e => e.status === 'pending'
                    ).length > 0 ? `
                    <div class="spinner"
                         style="border-top-color:
                                var(--accent-green);">
                    </div>
                    <span class="text-xs"
                          style="color: var(--accent-green);">
                        Sending in progress
                    </span>` : `
                    <span class="badge-green">
                        ✅ Queue clear
                    </span>`}
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mb-4">
                <div class="flex justify-between
                            text-sm mb-2">
                    <span style="color: var(--text-secondary);">
                        Overall Progress
                    </span>
                    <span class="font-bold">
                        ${c.sent_count} /
                        ${c.total_emails} sent
                        (${c.total_emails > 0
                            ? Math.round(
                                (c.sent_count / c.total_emails)
                                * 100
                            )
                            : 0}%)
                    </span>
                </div>
                <div class="h-3 rounded-full overflow-hidden"
                     style="background: var(--bg-tertiary);">
                    <div class="h-full rounded-full
                                transition-all duration-500"
                         style="width: ${c.total_emails > 0
                            ? Math.round(
                                (c.sent_count / c.total_emails)
                                * 100
                            )
                            : 0}%;
                                background: linear-gradient(
                                    90deg,
                                    var(--accent-blue),
                                    var(--accent-green)
                                );">
                    </div>
                </div>
            </div>

            {{-- Per Account Progress --}}
            <div id="accountProgress"
                 class="space-y-3 mb-4">
                ${buildAccountProgress(c.emails)}
            </div>

            {{-- Pending Queue --}}
            ${c.emails.filter(
                e => e.status === 'pending'
            ).length > 0 ? `
            <div class="mt-4">
                <h3 class="text-sm font-bold mb-3"
                    style="color: var(--text-secondary);">
                    ⏳ Emails In Queue
                </h3>
                <div class="space-y-2 max-h-48
                            overflow-y-auto">
                    ${c.emails
                        .filter(e => e.status === 'pending')
                        .map(e => `
                        <div class="flex items-center
                                    justify-between p-3
                                    rounded-xl"
                             style="background: var(--bg-tertiary);
                                    border: 1px solid
                                    rgba(245,158,11,0.2);">
                            <div class="flex items-center gap-3">
                                <div class="spinner"
                                     style="border-top-color:
                                     var(--accent-amber);
                                     width: 16px;
                                     height: 16px;">
                                </div>
                                <div>
                                    <p class="text-sm font-medium">
                                        ${e.to_email}
                                    </p>
                                    <p class="text-xs"
                                       style="color:
                                       var(--text-secondary);">
                                        Via ${e.gmail_account || '—'}
                                    </p>
                                </div>
                            </div>
                            <span class="badge-amber">
                                ⏳ Queued
                            </span>
                        </div>
                    `).join('')}
                </div>
            </div>` : ''}
        </div>

        {{-- Follow-up Status --}}
        <div id="followUpStatus" class="card mb-6">
            <div class="text-center py-4"
                 style="color: var(--text-secondary);">
                <div class="spinner mx-auto mb-2"></div>
                Loading follow-up status...
            </div>
        </div>

        {{-- Emails Table --}}
        <div class="card">
            <div class="flex items-center
                        justify-between mb-4 flex-wrap gap-3">
                <h2 class="text-lg font-bold">
                    📧 All Emails (${c.emails.length})
                </h2>
                <div class="flex gap-2 flex-wrap">
                    <select id="filterStatus"
                            onchange="filterEmails()"
                            class="input text-sm py-2"
                            style="width: auto;">
                        <option value="all">All Status</option>
                        <option value="sent">Sent</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                    <select id="filterReply"
                            onchange="filterEmails()"
                            class="input text-sm py-2"
                            style="width: auto;">
                        <option value="all">All Replies</option>
                        <option value="replied">Replied</option>
                        <option value="no_reply">No Reply</option>
                    </select>
                    <button onclick="retryFailed()"
                            class="text-sm px-4 py-2 rounded-lg"
                            style="background: rgba(239,68,68,0.1);
                                   color: var(--accent-red);
                                   border: 1px solid
                                   rgba(239,68,68,0.3);">
                        🔄 Retry Failed
                    </button>
                </div>
            </div>
            <div id="emailsTable">
                ${buildEmailsTable(c.emails)}
            </div>
        </div>
    `;

    window.allEmails = c.emails;
    loadFollowUpStatus();
}

// ============================================================
// BUILD ACCOUNT PROGRESS
// Shows per account how many sent vs total
// ============================================================
function buildAccountProgress(emails) {
    if (!emails.length) return '';

    // Group by from_email
    const accounts = {};
    emails.forEach(e => {
        if (!e.gmail_account) return;
        if (!accounts[e.gmail_account]) {
            accounts[e.gmail_account] = {
                total:   0,
                sent:    0,
                pending: 0,
                failed:  0,
            };
        }
        accounts[e.gmail_account].total++;
        if (e.status === 'sent')    accounts[e.gmail_account].sent++;
        if (e.status === 'pending') accounts[e.gmail_account].pending++;
        if (e.status === 'failed')  accounts[e.gmail_account].failed++;
    });

    return Object.entries(accounts).map(([email, stats]) => {
        const pct = Math.round((stats.sent / stats.total) * 100);
        return `
            <div class="p-3 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <div class="flex justify-between
                            items-center mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">📧</span>
                        <span class="text-sm font-medium
                                     truncate max-w-48">
                            ${email}
                        </span>
                    </div>
                    <div class="flex items-center gap-2
                                text-xs flex-shrink-0">
                        <span style="color: var(--accent-green);">
                            ${stats.sent} sent
                        </span>
                        ${stats.pending > 0 ? `
                        <span style="color: var(--accent-amber);">
                            · ${stats.pending} pending
                        </span>` : ''}
                        ${stats.failed > 0 ? `
                        <span style="color: var(--accent-red);">
                            · ${stats.failed} failed
                        </span>` : ''}
                        <span style="color: var(--text-secondary);">
                            / ${stats.total}
                        </span>
                    </div>
                </div>
                <div class="h-2 rounded-full overflow-hidden"
                     style="background: var(--bg-secondary);">
                    <div class="h-full rounded-full transition-all"
                         style="width: ${pct}%;
                                background: ${
                                    pct === 100
                                    ? 'var(--accent-green)'
                                    : 'var(--accent-blue)'
                                };">
                    </div>
                </div>
            </div>`;
    }).join('');
}

function statBox(label, value, color) {
    return `
        <div class="card text-center">
            <div class="text-2xl font-bold"
                 style="color: ${color};">${value}</div>
            <div class="text-xs mt-1"
                 style="color: var(--text-secondary);">
                ${label}
            </div>
        </div>`;
}

function buildEmailsTable(emails) {
    if (!emails.length) {
        return `
            <div class="text-center py-8"
                 style="color: var(--text-secondary);">
                <div class="text-4xl mb-3">📭</div>
                <p>No emails found</p>
            </div>`;
    }

    return `
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom: 1px solid
                               var(--border-color);">
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Recipient
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            From
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Status
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Reply
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Follow-ups
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Last Template
                        </th>
                        <th class="text-left py-3 px-3"
                            style="color:
                                   var(--text-secondary);">
                            Sent At
                        </th>
                    </tr>
                </thead>
                <tbody>
                    ${emails.map(e => `
                        <tr style="border-bottom: 1px solid
                                   var(--border-color);"
                            class="hover:bg-white/5
                                   transition-all">
                            <td class="py-3 px-3">
                                <p class="font-medium">
                                    ${e.to_email}
                                </p>
                                <p class="text-xs"
                                   style="color:
                                   var(--text-secondary);">
                                    ${e.company_name || '—'}
                                </p>
                            </td>
                            <td class="py-3 px-3 text-xs"
                                style="color:
                                       var(--text-secondary);">
                                ${e.gmail_account || '—'}
                            </td>
                            <td class="py-3 px-3">
                                <span class="${
                                    statusBadge(e.status)}">
                                    ${e.status === 'pending'
                                        ? '⏳ ' : ''}
                                    ${e.status}
                                </span>
                            </td>
                           <td class="py-3 px-3">
    ${e.has_bounce
        ? `<span class="badge-red">💀 Bounced</span>`
        : e.has_reply
            ? `<span class="badge-green">✅ Replied</span>`
            : `<span class="badge-blue">⏳ Waiting</span>`
    }
</td>
                            <td class="py-3 px-3">
                                <span class="font-bold"
                                      style="color: ${
                                          e.follow_up_count > 0
                                          ? 'var(--accent-amber)'
                                          : 'var(--text-secondary)'
                                      };">
                                    ${e.follow_up_count} sent
                                </span>
                            </td>
                            <td class="py-3 px-3">
                                ${templateBadge(e.template_type)}
                            </td>
                            <td class="py-3 px-3 text-xs"
                                style="color:
                                       var(--text-secondary);">
                                ${e.sent_at
                                    ? new Date(e.sent_at)
                                          .toLocaleString()
                                    : '—'}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>`;
}

function templateBadge(type) {
    if (!type) return '—';
    const map = {
        bulk_template: ['📧 Initial', 'var(--accent-blue)'],
        followup_1:    ['🔄 FU #1',  'var(--accent-green)'],
        followup_2:    ['🔄 FU #2',  'var(--accent-amber)'],
        followup_3:    ['🔄 FU #3',  'var(--accent-purple)'],
    };

    // Handle followup_4 and above
    if (type.startsWith('followup_')) {
        const num = type.replace('followup_', '');
        return `<span class="text-xs font-medium"
                      style="color: var(--accent-purple);">
                    🔄 FU #${num}
                </span>`;
    }

    const [label, color] = map[type]
        || ['—', 'var(--text-secondary)'];

    return `<span class="text-xs font-medium"
                  style="color: ${color};">
                ${label}
            </span>`;
}

function statusBadge(status) {
    return status === 'sent'    ? 'badge-green' :
           status === 'pending' ? 'badge-amber' :
           status === 'failed'  ? 'badge-red'   :
           status === 'bounced' ? 'badge-red'   : 'badge-blue'; // ← ADD
}

function filterEmails() {
    const statusFilter = document.getElementById(
        'filterStatus'
    ).value;
    const replyFilter  = document.getElementById(
        'filterReply'
    ).value;

    let filtered = window.allEmails;

    if (statusFilter !== 'all') {
        filtered = filtered.filter(
            e => e.status === statusFilter
        );
    }

    if (replyFilter === 'replied') {
        filtered = filtered.filter(e => e.has_reply);
    } else if (replyFilter === 'no_reply') {
        filtered = filtered.filter(e => !e.has_reply);
    }

    document.getElementById('emailsTable').innerHTML =
        buildEmailsTable(filtered);
}

async function loadFollowUpStatus() {
    const res = await apiGet(
        `/api/follow-ups/${campaignId}/progress`
    );
    const el = document.getElementById('followUpStatus');

    if (!res.success) return;

    el.innerHTML = `
        <div class="flex items-center
                    justify-between mb-4">
            <h2 class="text-lg font-bold">
                🔄 Follow-up Progress
            </h2>
            <div class="flex items-center gap-2">
                ${res.in_queue > 0 ? `
                <div class="spinner"
                     style="border-top-color:
                            var(--accent-purple);">
                </div>
                <span class="text-xs"
                      style="color: var(--accent-purple);">
                    ${res.in_queue} sending now
                </span>` : `
                <span class="badge-green">✅ Queue clear</span>`}
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="p-3 rounded-xl text-center"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-green);">
                    ${res.eligible}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Eligible
                </div>
            </div>
            <div class="p-3 rounded-xl text-center"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-purple);">
                    ${res.replied}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    Replied (skip)
                </div>
            </div>
            <div class="p-3 rounded-xl text-center"
                 style="background: var(--bg-tertiary);">
                <div class="text-xl font-bold"
                     style="color: var(--accent-blue);">
                    ${res.in_queue}
                </div>
                <div class="text-xs"
                     style="color: var(--text-secondary);">
                    In Queue Now
                </div>
            </div>
        </div>

        {{-- Per Email Follow-up Status --}}
        <div class="space-y-2 max-h-64 overflow-y-auto">
            ${res.follow_ups.map(e => `
                <div class="flex items-center
                            justify-between p-3 rounded-xl"
                     style="background: var(--bg-tertiary);
                            border: 1px solid ${
                                e.has_reply
                                ? 'rgba(16,185,129,0.3)'
                                : e.follow_up_count > 0
                                ? 'rgba(245,158,11,0.3)'
                                : 'var(--border-color)'
                            };">
                    <div class="flex items-center gap-3">
                        <div>
                            <p class="text-sm font-medium">
                                ${e.to_email}
                            </p>
                            <p class="text-xs"
                               style="color:
                               var(--text-secondary);">
                                Via ${e.from_email}
                                ${e.last_follow_up
                                    ? '· Last: '
                                      + e.last_follow_up
                                    : ''}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2
                                flex-shrink-0">
                        ${e.has_reply ? `
                        <span class="badge-green">
                            ✅ Replied
                        </span>` : `
                        <span class="text-xs font-bold"
                              style="color:
                              var(--accent-amber);">
                            ${e.follow_up_count} sent
                        </span>
                        <span class="text-xs px-2 py-1
                                     rounded-lg"
                              style="background:
                              rgba(139,92,246,0.15);
                              color: var(--accent-purple);">
                            Next: FU ${e.next_type
                                .replace('followup_', '')}
                        </span>`}
                    </div>
                </div>
            `).join('')}
        </div>

        ${res.eligible > 0 ? `
        <div class="mt-4 p-4 rounded-xl flex items-center
                    justify-between"
             style="background: rgba(16,185,129,0.08);
                    border: 1px solid rgba(16,185,129,0.2);">
            <div>
                <p class="font-bold"
                   style="color: var(--accent-green);">
                    ${res.eligible} ready for follow-up
                </p>
                <p class="text-sm"
                   style="color: var(--text-secondary);">
                    ${res.replied} will be skipped
                </p>
            </div>
            <button onclick="sendFollowUps()"
                    class="btn-green">
                🔄 Send Now
            </button>
        </div>` : ''}
    `;
}

async function sendFollowUps() {
    const btn = document.getElementById('followUpBtn');
    btn.textContent = '⏳ Queuing...';
    btn.disabled    = true;

    const res = await apiPost(
        `/api/follow-ups/${campaignId}/send`, {}
    );

    btn.textContent = '🔄 Send Follow-ups';
    btn.disabled    = false;

    if (res.success) {
        toast(
            '🔄 Queued!',
            `${res.dispatched} queued · `
            + `${res.skipped} skipped · `
            + `${res.estimate}`,
            'success'
        );
        loadCampaign();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function checkReplies() {
    const btn = document.getElementById('checkRepliesBtn');
    btn.textContent = '⏳ Checking...';
    btn.disabled    = true;

    const res = await apiPost(
        `/api/follow-ups/${campaignId}/check-replies`, {}
    );

    btn.textContent = '🔍 Check Replies';
    btn.disabled    = false;

    if (res.success) {
        toast(
            '🔍 Done',
            res.message,
            res.replies_found > 0 ? 'success' : 'info'
        );
        loadCampaign();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function retryFailed() {
    const res = await apiPost(
        `/api/campaigns/${campaignId}/retry-failed`, {}
    );

    if (res.success) {
        toast('Retrying', res.message, 'success');
        loadCampaign();
    } else {
        toast('Error', res.error, 'error');
    }
}
</script>
@endsection