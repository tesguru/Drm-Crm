@extends('layouts.app')
@section('title', 'Campaigns')
@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold gradient-text">🚀 Campaigns</h1>
        <p style="color: var(--text-secondary);">
            Create and manage outbound campaigns
        </p>
    </div>
    <button onclick="showCreateModal()"
            class="btn-primary flex items-center gap-2">
        ➕ New Campaign
    </button>
</div>

{{-- Campaigns List --}}
<div id="campaignsList">
    <div class="text-center py-12"
         style="color: var(--text-secondary);">
        <div class="spinner mx-auto mb-3"></div>
        Loading campaigns...
    </div>
</div>

{{-- Create Campaign Modal --}}
<div id="createModal"
     class="hidden fixed inset-0 z-50 flex items-center
            justify-center p-4"
     style="background: rgba(0,0,0,0.8);">
    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl"
         style="background: var(--bg-secondary);
                border: 1px solid var(--border-color);">

        <div class="p-6 border-b flex items-center justify-between"
             style="border-color: var(--border-color);">
            <h2 class="text-xl font-bold">🚀 New Campaign</h2>
            <button onclick="hideCreateModal()"
                    class="text-2xl hover:opacity-70"
                    style="color: var(--text-secondary);">×</button>
        </div>

        <div class="p-6 space-y-5">

            {{-- Campaign Name --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Campaign Name * (must be unique)
                </label>
                <input type="text"
                       id="campName"
                       placeholder="txwebdesign.com outreach"
                       class="input">
            </div>

            {{-- Domain + Price --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2"
                           style="color: var(--text-secondary);">
                        Domain *
                    </label>
                    <input type="text"
                           id="campDomain"
                           placeholder="txwebdesign.com"
                           class="input">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2"
                           style="color: var(--text-secondary);">
                        Price *
                    </label>
                    <input type="text"
                           id="campPrice"
                           placeholder="$2,499"
                           class="input">
                </div>
            </div>

            {{-- Your Name --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Your Name *
                </label>
                <input type="text"
                       id="campYourName"
                       placeholder="John"
                       class="input">
            </div>

            {{-- Recipients --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-semibold"
                           style="color: var(--text-secondary);">
                        Recipients * (paste all at once)
                    </label>
                    <span id="recipientCount"
                          class="badge-blue">0 emails</span>
                </div>
                <textarea id="campRecipients"
                          rows="6"
                          class="input font-mono text-sm"
                          placeholder="john@business.com&#10;mary@company.com&#10;bob@agency.com&#10;..."
                          oninput="countRecipients()"></textarea>
            </div>

            {{-- Gmail Accounts --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Gmail Accounts * (select sending accounts)
                </label>
                <div id="accountCheckboxes"
                     class="space-y-2 p-3 rounded-xl"
                     style="background: var(--bg-tertiary);">
                    <div class="text-sm"
                         style="color: var(--text-secondary);">
                        Loading accounts...
                    </div>
                </div>
            </div>

            {{-- Split Mode --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Split Mode *
                </label>
                <div class="flex gap-3">
                    <button onclick="setSplitMode('equal')"
                            id="splitEqual"
                            class="flex-1 py-3 rounded-xl font-semibold
                                   text-sm transition-all"
                            style="background: rgba(59,130,246,0.15);
                                   color: var(--accent-blue);
                                   border: 2px solid var(--accent-blue);">
                        ⚖️ Equal Split
                    </button>
                    <button onclick="setSplitMode('custom')"
                            id="splitCustom"
                            class="flex-1 py-3 rounded-xl font-semibold
                                   text-sm transition-all"
                            style="background: var(--bg-tertiary);
                                   color: var(--text-secondary);
                                   border: 2px solid var(--border-color);">
                        ✏️ Custom Split
                    </button>
                </div>
            </div>

            {{-- Preview Split --}}
            <button onclick="previewSplit()"
                    class="w-full py-3 rounded-xl font-semibold text-sm"
                    style="background: rgba(139,92,246,0.15);
                           color: var(--accent-purple);
                           border: 1px solid rgba(139,92,246,0.3);">
                👁️ Preview Split
            </button>

            {{-- Preview Result --}}
            <div id="splitPreview" class="hidden"></div>

            {{-- Submit --}}
            <button onclick="createCampaign()"
                    id="createBtn"
                    class="btn-green w-full py-4 text-lg">
                🚀 Create Campaign & Start Sending
            </button>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
let splitMode    = 'equal';
let allAccounts  = [];

document.addEventListener('DOMContentLoaded', () => {
    loadCampaigns();
    loadAccountCheckboxes();
});

async function loadCampaigns() {
    const res = await apiGet('/api/campaigns');
    const el  = document.getElementById('campaignsList');

    if (!res.success || !res.campaigns.length) {
        el.innerHTML = `
            <div class="card text-center py-12">
                <div class="text-5xl mb-4">🚀</div>
                <p class="text-lg font-bold mb-2">No campaigns yet</p>
                <p style="color: var(--text-secondary);" class="mb-6">
                    Create your first outbound campaign
                </p>
                <button onclick="showCreateModal()"
                        class="btn-primary">
                    ➕ New Campaign
                </button>
            </div>`;
        return;
    }

    el.innerHTML = res.campaigns.map(c => `
        <div class="card mb-4 cursor-pointer
                    hover:border-blue-500/50 transition-all"
             style="border: 1px solid var(--border-color);"
             onclick="window.location='/campaigns/${c.id}'">

            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold">${c.name}</h3>
                    <p class="text-sm"
                       style="color: var(--text-secondary);">
                        ${c.domain} · ${c.price}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="${c.status === 'active'
                        ? 'badge-green'
                        : 'badge-amber'}">
                        ${c.status}
                    </span>
                    <button onclick="event.stopPropagation();
                                     deleteCampaign(${c.id})"
                            class="btn-danger text-xs px-3 py-1.5">
                        🗑️
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                ${statBox('Total', c.total_emails, 'var(--accent-blue)')}
                ${statBox('Sent', c.sent_count, 'var(--accent-green)')}
                ${statBox('Replied', c.replied_count, 'var(--accent-purple)')}
                ${statBox('Follow-ups', c.follow_up_count, 'var(--accent-amber)')}
                ${statBox('Failed', c.failed_count, 'var(--accent-red)')}
            </div>

            ${c.pending_count > 0 ? `
                <div class="mt-3 p-3 rounded-xl text-sm flex
                            items-center gap-2"
                     style="background: rgba(59,130,246,0.08);">
                    <div class="spinner"></div>
                    <span style="color: var(--accent-blue);">
                        ${c.pending_count} emails queued —
                        queue worker must be running
                    </span>
                </div>` : ''
            }
        </div>
    `).join('');
}

function statBox(label, value, color) {
    return `
        <div class="text-center p-3 rounded-xl"
             style="background: var(--bg-tertiary);">
            <div class="text-xl font-bold"
                 style="color: ${color};">${value}</div>
            <div class="text-xs"
                 style="color: var(--text-secondary);">${label}</div>
        </div>`;
}

async function loadAccountCheckboxes() {
    const res     = await apiGet('/api/gmail-accounts');
    allAccounts   = res.accounts || [];
    const el      = document.getElementById('accountCheckboxes');

    if (!allAccounts.length) {
        el.innerHTML = `
            <p class="text-sm" style="color: var(--accent-red);">
                No Gmail accounts connected.
                <a href="{{ route('auth.google.account') }}"
                   class="underline">Add one first</a>
            </p>`;
        return;
    }

    el.innerHTML = allAccounts.map(a => `
        <label class="flex items-center gap-3 p-3 rounded-xl
                      cursor-pointer hover:bg-black/20 transition-all"
               style="border: 1px solid var(--border-color);">
            <input type="checkbox"
                   name="gmail_accounts"
                   value="${a.id}"
                   class="w-4 h-4 rounded"
                   style="accent-color: var(--accent-blue);">
            <div class="flex-1">
                <p class="text-sm font-semibold">${a.email}</p>
                <p class="text-xs"
                   style="color: var(--text-secondary);">
                    ${a.remaining} remaining today
                    · ${a.token_status === 'expired'
                        ? '❌ Token expired'
                        : '✅ Token valid'}
                </p>
            </div>
        </label>
    `).join('');
}

function countRecipients() {
    const val  = document.getElementById('campRecipients').value;
    const list = val.split(/[\n,;]+/)
                    .map(e => e.trim())
                    .filter(e => e.includes('@'));
    const el   = document.getElementById('recipientCount');
    el.textContent = `${list.length} emails`;
    el.className   = list.length > 0 ? 'badge-green' : 'badge-red';
}

function setSplitMode(mode) {
    splitMode = mode;
    const eq  = document.getElementById('splitEqual');
    const cu  = document.getElementById('splitCustom');

    if (mode === 'equal') {
        eq.style.background  = 'rgba(59,130,246,0.15)';
        eq.style.color       = 'var(--accent-blue)';
        eq.style.borderColor = 'var(--accent-blue)';
        cu.style.background  = 'var(--bg-tertiary)';
        cu.style.color       = 'var(--text-secondary)';
        cu.style.borderColor = 'var(--border-color)';
    } else {
        cu.style.background  = 'rgba(59,130,246,0.15)';
        cu.style.color       = 'var(--accent-blue)';
        cu.style.borderColor = 'var(--accent-blue)';
        eq.style.background  = 'var(--bg-tertiary)';
        eq.style.color       = 'var(--text-secondary)';
        eq.style.borderColor = 'var(--border-color)';
    }
}

function getSelectedAccounts() {
    return [...document.querySelectorAll(
        'input[name="gmail_accounts"]:checked'
    )].map(i => parseInt(i.value));
}

async function previewSplit() {
    const recipients = document.getElementById('campRecipients').value;
    const accounts   = getSelectedAccounts();

    if (!recipients || !accounts.length) {
        toast('Error', 'Add recipients and select accounts first', 'error');
        return;
    }

    const res = await apiPost('/api/campaigns/preview-split', {
        recipients,
        gmail_accounts: accounts,
        split_mode:     splitMode,
        custom_splits:  {}
    });

    const el = document.getElementById('splitPreview');

    if (res.success) {
        el.classList.remove('hidden');
        el.innerHTML = `
            <div class="p-4 rounded-xl"
                 style="background: var(--bg-tertiary);">
                <p class="font-bold mb-3">
                    👁️ Split Preview —
                    ${res.total} emails · ~${res.total_time}
                </p>
                ${res.preview.map(p => `
                    <div class="flex items-center justify-between
                                py-2 border-b"
                         style="border-color: var(--border-color);">
                        <span class="text-sm">${p.account}</span>
                        <span class="badge-blue">
                            ${p.count} emails
                        </span>
                    </div>
                `).join('')}
            </div>`;
    } else {
        toast('Error', res.error, 'error');
    }
}

async function createCampaign() {
    const name       = document.getElementById('campName').value.trim();
    const domain     = document.getElementById('campDomain').value.trim();
    const price      = document.getElementById('campPrice').value.trim();
    const yourName   = document.getElementById('campYourName').value.trim();
    const recipients = document.getElementById('campRecipients').value;
    const accounts   = getSelectedAccounts();

    if (!name || !domain || !price || !yourName || !recipients) {
        toast('Error', 'Please fill all fields', 'error');
        return;
    }

    if (!accounts.length) {
        toast('Error', 'Select at least one Gmail account', 'error');
        return;
    }

    const btn      = document.getElementById('createBtn');
    btn.textContent = '⏳ Creating...';
    btn.disabled    = true;

    const res = await apiPost('/api/campaigns', {
        name,
        domain,
        price,
        your_name:     yourName,
        recipients,
        gmail_accounts: accounts,
        split_mode:     splitMode,
        custom_splits:  {}
    });

    btn.textContent = '🚀 Create Campaign & Start Sending';
    btn.disabled    = false;

    if (res.success) {
        toast('Created!', res.message, 'success');
        hideCreateModal();
        loadCampaigns();
    } else {
        toast('Error', res.error, 'error');
    }
}

async function deleteCampaign(id) {
    if (!confirm('Delete this campaign?')) return;
    const res = await apiDelete(`/api/campaigns/${id}`);
    if (res.success) {
        toast('Deleted', 'Campaign deleted', 'success');
        loadCampaigns();
    } else {
        toast('Error', res.error, 'error');
    }
}

function showCreateModal() {
    document.getElementById('createModal')
            .classList.remove('hidden');
}

function hideCreateModal() {
    document.getElementById('createModal')
            .classList.add('hidden');
}
</script>
@endsection