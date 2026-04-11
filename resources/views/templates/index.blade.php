@extends('layouts.app')
@section('title', 'Templates')
@section('content')

<div class="mb-6">
    <h1 class="text-3xl font-bold gradient-text">📝 Templates</h1>
    <p style="color: var(--text-secondary);">
        Manage templates — max 6 per type · 20 follow-up levels
    </p>
</div>

{{-- Template Type Tabs --}}
<div class="mb-6">
    {{-- Initial Tab --}}
    <div class="flex gap-2 mb-3 flex-wrap">
        <button onclick="switchType('bulk_template')"
                id="tab-bulk_template"
                class="px-5 py-2.5 rounded-xl font-semibold
                       text-sm transition-all"
                style="background: var(--bg-secondary);
                       border: 1px solid var(--border-color);
                       color: var(--text-secondary);">
            📧 Initial Outbound
        </button>
    </div>

    {{-- Follow-up Tabs 1-20 --}}
    <div class="flex gap-2 flex-wrap">
        @for($i = 1; $i <= 20; $i++)
        <button onclick="switchType('followup_{{ $i }}')"
                id="tab-followup_{{ $i }}"
                class="px-4 py-2 rounded-xl font-semibold
                       text-xs transition-all"
                style="background: var(--bg-secondary);
                       border: 1px solid var(--border-color);
                       color: var(--text-secondary);">
            🔄 FU {{ $i }}
        </button>
        @endfor
    </div>
</div>

{{-- Info Banner --}}
<div id="typeBanner" class="mb-6 hidden"></div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Template List --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold" id="typeLabel">
                Templates
            </h2>
            <span id="templateCount" class="badge-blue">
                0/6
            </span>
        </div>
        <div id="templatesList">
            <div class="text-center py-8"
                 style="color: var(--text-secondary);">
                <div class="spinner mx-auto mb-3"></div>
                Loading...
            </div>
        </div>
    </div>

    {{-- Create Template Form --}}
    <div class="card">
        <h2 class="text-lg font-bold mb-6">➕ Create Template</h2>

        <div class="space-y-4">

            {{-- Name --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Template Name *
                </label>
                <input type="text"
                       id="tplName"
                       placeholder="e.g. Medical Outreach #1"
                       class="input">
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Type *
                </label>
                <select id="tplType"
                        onchange="onTypeChange()"
                        class="input">
                    <option value="bulk_template">
                        📧 Initial Outbound
                    </option>
                    @for($i = 1; $i <= 20; $i++)
                    <option value="followup_{{ $i }}">
                        🔄 Follow-up {{ $i }}
                    </option>
                    @endfor
                </select>
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Subject *
                </label>
                <input type="text"
                       id="tplSubject"
                       placeholder="{company} — {domain} opportunity"
                       class="input">
                <p id="subjectNote"
                   class="text-xs mt-1 hidden"
                   style="color: var(--text-secondary);">
                    💡 Follow-ups use original subject with Re: prefix
                </p>
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-sm font-semibold mb-2"
                       style="color: var(--text-secondary);">
                    Body *
                </label>
                <textarea id="tplBody"
                          rows="10"
                          class="input font-mono text-sm"
                          placeholder="Hi {firstName},&#10;&#10;..."></textarea>
            </div>

            {{-- Variables --}}
            <div class="p-4 rounded-xl"
                 style="background: rgba(59,130,246,0.08);
                        border: 1px solid rgba(59,130,246,0.2);">
                <p class="text-xs font-bold mb-3"
                   style="color: var(--accent-blue);">
                    📌 Click to insert:
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach([
                        '{company}'   => 'Company',
                        '{domain}'    => 'Domain',
                        '{price}'     => 'Price',
                        '{firstName}' => 'First Name',
                        '{yourName}'  => 'Your Name',
                    ] as $var => $desc)
                    <button onclick="insertVar('{{ $var }}')"
                            class="text-xs px-3 py-1.5 rounded-lg
                                   font-mono font-bold transition-all
                                   hover:scale-105"
                            style="background: rgba(59,130,246,0.15);
                                   color: var(--accent-blue);
                                   border: 1px solid
                                   rgba(59,130,246,0.3);">
                        {{ $var }}
                        <span class="opacity-60 font-normal">
                            {{ $desc }}
                        </span>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Sample Templates --}}
            <div class="p-4 rounded-xl"
                 style="background: rgba(16,185,129,0.08);
                        border: 1px solid rgba(16,185,129,0.2);">
                <p class="text-xs font-bold mb-3"
                   style="color: var(--accent-green);">
                    📋 Load Sample:
                </p>
                <div class="flex flex-wrap gap-2">
                    <button onclick="loadSample('initial')"
                            class="text-xs px-3 py-1.5 rounded-lg"
                            style="background: rgba(16,185,129,0.15);
                                   color: var(--accent-green);
                                   border: 1px solid
                                   rgba(16,185,129,0.3);">
                        📧 Initial
                    </button>
                    <button onclick="loadSample('followup1')"
                            class="text-xs px-3 py-1.5 rounded-lg"
                            style="background: rgba(16,185,129,0.15);
                                   color: var(--accent-green);
                                   border: 1px solid
                                   rgba(16,185,129,0.3);">
                        🔄 FU 1
                    </button>
                    <button onclick="loadSample('followup2')"
                            class="text-xs px-3 py-1.5 rounded-lg"
                            style="background: rgba(16,185,129,0.15);
                                   color: var(--accent-green);
                                   border: 1px solid
                                   rgba(16,185,129,0.3);">
                        🔄 FU 2
                    </button>
                    <button onclick="loadSample('followupgeneric')"
                            class="text-xs px-3 py-1.5 rounded-lg"
                            style="background: rgba(16,185,129,0.15);
                                   color: var(--accent-green);
                                   border: 1px solid
                                   rgba(16,185,129,0.3);">
                        🔄 Generic FU
                    </button>
                </div>
            </div>

            {{-- Save --}}
            <button onclick="saveTemplate()"
                    class="btn-primary w-full py-4 text-lg">
                💾 Save Template
            </button>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
let currentType = 'bulk_template';

const samples = {
    initial: {
        subject: '{company} — {domain} domain opportunity',
        body:
`Hi {firstName},

I came across {company} and noticed that {domain} is currently available.

This domain would be a perfect match for your business — easy to remember, professional, and directly relevant to what you do.

I'm offering it at {price}. Would you be open to a quick conversation?

Best regards,
{yourName}`
    },
    followup1: {
        subject: 'Re: {domain}',
        body:
`Hi {firstName},

Just following up on my previous email about {domain}.

I wanted to make sure it didn't get lost in your inbox. This domain is still available and I believe it could add real value to {company}.

Happy to answer any questions.

Best,
{yourName}`
    },
    followup2: {
        subject: 'Re: {domain}',
        body:
`Hi {firstName},

I'll keep this brief — {domain} is still available.

A domain like this can significantly improve how customers find {company} online. Would {price} work for you?

Best,
{yourName}`
    },
    followupgeneric: {
        subject: 'Re: {domain}',
        body:
`Hi {firstName},

Still wanted to check if {domain} could be useful for {company}.

The domain is still available. Happy to discuss pricing or answer any questions you might have.

Best,
{yourName}`
    },
};

document.addEventListener('DOMContentLoaded', () => {
    switchType('bulk_template');
});

function switchType(type) {
    currentType = type;

    // Reset all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(t => {
        t.style.background  = 'var(--bg-secondary)';
        t.style.color       = 'var(--text-secondary)';
        t.style.borderColor = 'var(--border-color)';
    });

    // Activate current tab
    const active = document.getElementById(`tab-${type}`);
    if (active) {
        active.style.background  = 'rgba(59,130,246,0.15)';
        active.style.color       = 'var(--accent-blue)';
        active.style.borderColor = 'var(--accent-blue)';
    }

    // Update label
    const label = type === 'bulk_template'
        ? '📧 Initial Outbound Templates'
        : `🔄 Follow-up ${type.replace('followup_', '')} Templates`;

    document.getElementById('typeLabel').textContent = label;

    // Show info banner for follow-ups
    const banner = document.getElementById('typeBanner');
    const num    = parseInt(type.replace('followup_', ''));

    if (type !== 'bulk_template' && num >= 3) {
        banner.classList.remove('hidden');
        banner.innerHTML = `
            <div class="p-4 rounded-xl"
                 style="background: rgba(139,92,246,0.08);
                        border: 1px solid rgba(139,92,246,0.2);">
                <p class="text-sm font-bold"
                   style="color: var(--accent-purple);">
                    🔄 Follow-up ${num} Templates
                </p>
                <p class="text-xs mt-1"
                   style="color: var(--text-secondary);">
                    If no template exists for this level —
                    system automatically falls back to
                    the nearest previous level.
                </p>
            </div>`;
    } else {
        banner.classList.add('hidden');
    }

    // Sync form select
    document.getElementById('tplType').value = type;
    onTypeChange();

    loadTemplates(type);
}

function onTypeChange() {
    const type    = document.getElementById('tplType').value;
    const subNote = document.getElementById('subjectNote');

    if (type !== 'bulk_template') {
        subNote.classList.remove('hidden');
    } else {
        subNote.classList.add('hidden');
    }

    // Sync tab
    switchTypeFromSelect(type);
}

function switchTypeFromSelect(type) {
    currentType = type;
    loadTemplates(type);
}

function loadSample(key) {
    const sample = samples[key];
    if (!sample) return;

    document.getElementById('tplSubject').value = sample.subject;
    document.getElementById('tplBody').value    = sample.body;

    const typeMap = {
        initial:       'bulk_template',
        followup1:     'followup_1',
        followup2:     'followup_2',
        followupgeneric: currentType !== 'bulk_template'
            ? currentType
            : 'followup_3',
    };

    if (typeMap[key]) {
        document.getElementById('tplType').value = typeMap[key];
    }

    toast('Loaded', 'Sample template loaded', 'success');
}

async function loadTemplates(type) {
    const res = await apiGet(`/api/templates?type=${type}`);
    const el  = document.getElementById('templatesList');
    const cnt = res.templates?.length || 0;

    const countEl = document.getElementById('templateCount');
    countEl.textContent = `${cnt}/6`;
    countEl.className   = cnt >= 6 ? 'badge-red'   :
                          cnt >= 4 ? 'badge-amber'  : 'badge-blue';

    if (!res.success || !cnt) {
        el.innerHTML = `
            <div class="card text-center py-8">
                <div class="text-4xl mb-3">📭</div>
                <p style="color: var(--text-secondary);">
                    No templates for this type yet.
                </p>
                <p class="text-sm mt-2"
                   style="color: var(--text-secondary);">
                    Load a sample or create one →
                </p>
            </div>`;
        return;
    }

    el.innerHTML = res.templates.map((t, i) => `
        <div class="card mb-3 transition-all
                    hover:border-blue-500/30"
             style="border: 1px solid var(--border-color);">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-1 rounded-full
                                 font-bold"
                          style="background: rgba(59,130,246,0.15);
                                 color: var(--accent-blue);">
                        #${i + 1}
                    </span>
                    <p class="font-bold">${t.name}</p>
                </div>
                <button onclick="deleteTemplate(${t.id})"
                        class="btn-danger text-xs px-3 py-1.5">
                    🗑️
                </button>
            </div>
            <div class="p-2 rounded-lg mb-2"
                 style="background: var(--bg-tertiary);">
                <p class="text-xs font-semibold mb-1"
                   style="color: var(--text-secondary);">
                    Subject:
                </p>
                <p class="text-sm">${t.subject_template}</p>
            </div>
            <div class="p-2 rounded-lg"
                 style="background: var(--bg-tertiary);">
                <p class="text-xs font-semibold mb-1"
                   style="color: var(--text-secondary);">
                    Body Preview:
                </p>
                <p class="text-xs font-mono whitespace-pre-wrap"
                   style="color: var(--text-secondary);">
${t.body_template.substring(0, 150)}${
    t.body_template.length > 150 ? '...' : ''}
                </p>
            </div>
        </div>
    `).join('');
}

function insertVar(variable) {
    const body  = document.getElementById('tplBody');
    const start = body.selectionStart;
    const end   = body.selectionEnd;
    body.value  = body.value.substring(0, start)
                + variable
                + body.value.substring(end);
    body.selectionStart =
    body.selectionEnd   = start + variable.length;
    body.focus();
}

async function saveTemplate() {
    const name    = document.getElementById('tplName').value.trim();
    const type    = document.getElementById('tplType').value;
    const subject = document.getElementById('tplSubject').value.trim();
    const body    = document.getElementById('tplBody').value.trim();

    if (!name || !body) {
        toast('Error', 'Name and body are required', 'error');
        return;
    }

    if (type === 'bulk_template' && !subject) {
        toast('Error', 'Subject required for initial emails', 'error');
        return;
    }

    const res = await apiPost('/api/templates', {
        name,
        type,
        subject_template: subject || 'Re: {domain} domain',
        body_template:    body
    });

    if (res.success) {
        toast('Saved! ✅', 'Template saved', 'success');
        document.getElementById('tplName').value    = '';
        document.getElementById('tplSubject').value = '';
        document.getElementById('tplBody').value    = '';
        loadTemplates(type);
    } else {
        toast('Error', res.error, 'error');
    }
}

async function deleteTemplate(id) {
    if (!confirm('Delete this template?')) return;
    const res = await apiDelete(`/api/templates/${id}`);
    if (res.success) {
        toast('Deleted', 'Template deleted', 'success');
        loadTemplates(currentType);
    } else {
        toast('Error', res.error, 'error');
    }
}
</script>
@endsection