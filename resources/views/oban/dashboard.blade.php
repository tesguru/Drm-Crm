<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oban Job Monitor</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a0f;
            --surface: #111118;
            --surface2: #1a1a24;
            --border: rgba(255,255,255,0.07);
            --border2: rgba(255,255,255,0.12);
            --text: #e8e8f0;
            --text-muted: #6b6b80;
            --text-dim: #3a3a4a;
            --accent: #7c6ef7;
            --accent-glow: rgba(124,110,247,0.15);
            --green: #34d399;
            --green-bg: rgba(52,211,153,0.08);
            --red: #f87171;
            --red-bg: rgba(248,113,113,0.08);
            --amber: #fbbf24;
            --amber-bg: rgba(251,191,36,0.08);
            --blue: #60a5fa;
            --blue-bg: rgba(96,165,250,0.08);
            --purple: #a78bfa;
            --purple-bg: rgba(167,139,250,0.08);
            --mono: 'JetBrains Mono', monospace;
            --sans: 'DM Sans', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .header-left { display: flex; align-items: center; gap: 14px; }

        .logo {
            width: 36px; height: 36px;
            background: var(--accent-glow);
            border: 1px solid rgba(124,110,247,0.3);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .logo svg { width: 18px; height: 18px; }

        .page-title {
            font-size: 18px;
            font-weight: 500;
            letter-spacing: -0.02em;
        }

        .page-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .header-right { display: flex; align-items: center; gap: 10px; }

        .live-badge {
            display: flex; align-items: center; gap: 6px;
            padding: 5px 10px;
            background: var(--green-bg);
            border: 1px solid rgba(52,211,153,0.2);
            border-radius: 20px;
            font-size: 11px;
            color: var(--green);
            font-family: var(--mono);
        }

        .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--green);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }

        .btn {
            display: flex; align-items: center; gap: 6px;
            padding: 7px 14px;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 8px;
            color: var(--text-muted);
            font-size: 12px;
            font-family: var(--sans);
            cursor: pointer;
            transition: all 0.15s;
        }

        .btn:hover { border-color: rgba(255,255,255,0.2); color: var(--text); }
        .btn svg { width: 13px; height: 13px; }
        .btn.spinning svg { animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        .btn-primary:hover { opacity: 0.9; color: white; }

        /* Summary cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            transition: border-color 0.15s;
            cursor: default;
        }

        .metric-card:hover { border-color: var(--border2); }

        .metric-label {
            font-size: 10px;
            font-family: var(--mono);
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 28px;
            font-weight: 600;
            font-family: var(--mono);
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .metric-value.completed { color: var(--green); }
        .metric-value.cancelled { color: var(--red); }
        .metric-value.scheduled { color: var(--amber); }
        .metric-value.available { color: var(--blue); }
        .metric-value.executing { color: var(--purple); }
        .metric-value.retryable { color: var(--amber); }
        .metric-value.discarded { color: var(--red); }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            gap: 12px;
        }

        .filter-tabs {
            display: flex;
            gap: 4px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 4px;
        }

        .filter-tab {
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-family: var(--mono);
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            background: transparent;
            transition: all 0.15s;
        }

        .filter-tab:hover { color: var(--text); }

        .filter-tab.active {
            background: var(--surface2);
            color: var(--text);
            border: 1px solid var(--border2);
        }

        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 6px 12px;
            flex: 1;
            max-width: 300px;
        }

        .search-box svg { width: 13px; height: 13px; color: var(--text-muted); flex-shrink: 0; }

        .search-box input {
            background: none;
            border: none;
            outline: none;
            font-size: 12px;
            font-family: var(--mono);
            color: var(--text);
            width: 100%;
        }

        .search-box input::placeholder { color: var(--text-dim); }

        /* Table */
        .table-wrapper {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .table-head {
            display: grid;
            grid-template-columns: 60px 110px 110px 1fr 120px 90px 150px;
            gap: 12px;
            padding: 10px 16px;
            background: var(--surface2);
            border-bottom: 1px solid var(--border);
            font-size: 10px;
            font-family: var(--mono);
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .job-row {
            display: grid;
            grid-template-columns: 60px 110px 110px 1fr 120px 90px 150px;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            align-items: center;
            font-size: 12px;
            transition: background 0.1s;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

        .job-row:last-child { border-bottom: none; }
        .job-row:hover { background: var(--surface2); }

        .job-id {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-family: var(--mono);
            font-weight: 500;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .badge-dot { width: 5px; height: 5px; border-radius: 50%; }

        .badge.completed { background: var(--green-bg); color: var(--green); }
        .badge.completed .badge-dot { background: var(--green); }
        .badge.cancelled { background: var(--red-bg); color: var(--red); }
        .badge.cancelled .badge-dot { background: var(--red); }
        .badge.available { background: var(--blue-bg); color: var(--blue); }
        .badge.available .badge-dot { background: var(--blue); animation: pulse 1s infinite; }
        .badge.scheduled { background: var(--amber-bg); color: var(--amber); }
        .badge.scheduled .badge-dot { background: var(--amber); }
        .badge.executing { background: var(--purple-bg); color: var(--purple); }
        .badge.executing .badge-dot { background: var(--purple); animation: pulse 0.6s infinite; }
        .badge.retryable { background: var(--amber-bg); color: var(--amber); }
        .badge.retryable .badge-dot { background: var(--amber); }
        .badge.discarded { background: var(--red-bg); color: var(--red); }
        .badge.discarded .badge-dot { background: var(--red); }

        .queue-tag {
            font-family: var(--mono);
            font-size: 11px;
            color: var(--text-muted);
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 2px 7px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .worker-cell { overflow: hidden; }
        .worker-name { font-size: 12px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .worker-args { font-size: 10px; font-family: var(--mono); color: var(--text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .attempt-cell { font-family: var(--mono); font-size: 11px; color: var(--text-muted); }
        .attempt-cell span { color: var(--text); }

        .time-cell { font-family: var(--mono); font-size: 10px; color: var(--text-muted); }

        /* Pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-top: 1px solid var(--border);
            background: var(--surface2);
        }

        .pagination-info {
            font-size: 11px;
            font-family: var(--mono);
            color: var(--text-muted);
        }

        .pagination-controls { display: flex; align-items: center; gap: 4px; }

        .page-btn {
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 11px;
            font-family: var(--mono);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s;
        }

        .page-btn:hover:not(:disabled) { border-color: var(--border2); color: var(--text); }
        .page-btn.active { background: var(--accent); border-color: var(--accent); color: white; }
        .page-btn:disabled { opacity: 0.3; cursor: not-allowed; }

        .per-page-select {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-muted);
            font-size: 11px;
            font-family: var(--mono);
            padding: 4px 8px;
            cursor: pointer;
            outline: none;
        }

        /* Empty / loading */
        .empty-state {
            padding: 3rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            font-family: var(--mono);
        }

        .empty-state svg { width: 32px; height: 32px; margin: 0 auto 12px; display: block; opacity: 0.3; }

        .loading-row {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-muted);
            font-size: 12px;
            font-family: var(--mono);
        }

        .spinner {
            width: 14px; height: 14px;
            border: 1.5px solid var(--border2);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        /* Footer */
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 11px;
            font-family: var(--mono);
            color: var(--text-dim);
        }

        .auto-refresh-toggle {
            display: flex; align-items: center; gap: 8px;
        }

        .toggle {
            width: 32px; height: 18px;
            background: var(--surface2);
            border: 1px solid var(--border2);
            border-radius: 9px;
            position: relative;
            cursor: pointer;
            transition: background 0.2s;
        }

        .toggle.on { background: var(--accent); border-color: var(--accent); }

        .toggle-thumb {
            width: 12px; height: 12px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px; left: 2px;
            transition: transform 0.2s;
        }

        .toggle.on .toggle-thumb { transform: translateX(14px); }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="#7c6ef7" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
                <path d="M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12"/>
            </svg>
        </div>
        <div>
            <div class="page-title">Oban monitor</div>
            <div class="page-subtitle">Laravel + Elixir job pipeline</div>
        </div>
    </div>
    <div class="header-right">
        <div class="live-badge">
            <span class="live-dot"></span>
            auto refresh
        </div>
        <button class="btn" id="refreshBtn" onclick="loadJobs()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" stroke-linecap="round"/>
                <path d="M21 3v5h-5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" stroke-linecap="round"/>
            </svg>
            Refresh
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="metric-card">
        <div class="metric-label">Available</div>
        <div class="metric-value available" id="s-available">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Scheduled</div>
        <div class="metric-value scheduled" id="s-scheduled">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Executing</div>
        <div class="metric-value executing" id="s-executing">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Completed</div>
        <div class="metric-value completed" id="s-completed">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Retryable</div>
        <div class="metric-value retryable" id="s-retryable">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Cancelled</div>
        <div class="metric-value cancelled" id="s-cancelled">0</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Discarded</div>
        <div class="metric-value discarded" id="s-discarded">0</div>
    </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <div class="filter-tabs" id="filterTabs">
        <button class="filter-tab active" onclick="setFilter('all', this)">all</button>
        <button class="filter-tab" onclick="setFilter('available', this)">available</button>
        <button class="filter-tab" onclick="setFilter('scheduled', this)">scheduled</button>
        <button class="filter-tab" onclick="setFilter('executing', this)">executing</button>
        <button class="filter-tab" onclick="setFilter('completed', this)">completed</button>
        <button class="filter-tab" onclick="setFilter('retryable', this)">retryable</button>
        <button class="filter-tab" onclick="setFilter('cancelled', this)">cancelled</button>
        <button class="filter-tab" onclick="setFilter('discarded', this)">discarded</button>
    </div>
    <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35" stroke-linecap="round"/>
        </svg>
        <input type="text" id="searchInput" placeholder="search worker or args..." oninput="renderTable()" />
    </div>
</div>

<!-- Table -->
<div class="table-wrapper">
    <div class="table-head">
        <div>ID</div>
        <div>State</div>
        <div>Queue</div>
        <div>Worker / Args</div>
        <div>Scheduled at</div>
        <div>Attempt</div>
        <div>Completed at</div>
    </div>
    <div id="tableBody">
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <path d="M8 21h8M12 17v4" stroke-linecap="round"/>
            </svg>
            Loading jobs...
        </div>
    </div>
    <div class="pagination" id="pagination" style="display:none;">
        <div class="pagination-info" id="paginationInfo"></div>
        <div style="display:flex; align-items:center; gap:12px;">
            <select class="per-page-select" id="perPage" onchange="currentPage=1; renderTable()">
                <option value="10">10 / page</option>
                <option value="20" selected>20 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
            </select>
            <div class="pagination-controls" id="pageControls"></div>
        </div>
    </div>
</div>

<div class="footer">
    <span id="lastRefresh">not loaded yet</span>
    <div class="auto-refresh-toggle">
        <span>auto refresh (5s)</span>
        <div class="toggle on" id="autoToggle" onclick="toggleAuto()">
            <div class="toggle-thumb"></div>
        </div>
    </div>
</div>

<script>
    let allJobs = [];
    let filtered = [];
    let currentFilter = 'all';
    let currentPage = 1;
    let autoRefresh = true;
    let timer = null;

    function setFilter(f, btn) {
        currentFilter = f;
        currentPage = 1;
        document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderTable();
    }

    function toggleAuto() {
        autoRefresh = !autoRefresh;
        const tog = document.getElementById('autoToggle');
        tog.classList.toggle('on', autoRefresh);
        if (autoRefresh) startTimer();
        else clearInterval(timer);
    }

    function startTimer() {
        clearInterval(timer);
        timer = setInterval(loadJobs, 5000);
    }

    function formatTime(t) {
        if (!t) return '—';
        const d = new Date(t.replace(' ', 'T'));
        return d.toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function renderTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const perPage = parseInt(document.getElementById('perPage').value);

        filtered = allJobs.filter(j => {
            const matchFilter = currentFilter === 'all' || j.state === currentFilter;
            const matchSearch = !search ||
                j.worker.toLowerCase().includes(search) ||
                JSON.stringify(j.args).toLowerCase().includes(search) ||
                j.queue.toLowerCase().includes(search) ||
                String(j.id).includes(search);
            return matchFilter && matchSearch;
        });

        const total = filtered.length;
        const totalPages = Math.ceil(total / perPage) || 1;
        if (currentPage > totalPages) currentPage = 1;

        const start = (currentPage - 1) * perPage;
        const page = filtered.slice(start, start + perPage);

        const body = document.getElementById('tableBody');

        if (page.length === 0) {
            body.innerHTML = `<div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 12h8M12 8v8" stroke-linecap="round"/>
                </svg>
                No jobs found
            </div>`;
        } else {
            body.innerHTML = page.map(j => {
                const args = JSON.stringify(j.args || {});
                const workerShort = j.worker.replace('DomainOutreach.Workers.', '');
                return `<div class="job-row">
                    <div class="job-id">#${j.id}</div>
                    <div><span class="badge ${j.state}"><span class="badge-dot"></span>${j.state}</span></div>
                    <div><span class="queue-tag">${j.queue}</span></div>
                    <div class="worker-cell">
                        <div class="worker-name">${workerShort}</div>
                        <div class="worker-args">${args}</div>
                    </div>
                    <div class="time-cell">${formatTime(j.scheduled_at)}</div>
                    <div class="attempt-cell"><span>${j.attempt}</span>/${j.max_attempts}</div>
                    <div class="time-cell">${formatTime(j.completed_at)}</div>
                </div>`;
            }).join('');
        }

        const pag = document.getElementById('pagination');
        const info = document.getElementById('paginationInfo');
        const controls = document.getElementById('pageControls');
        pag.style.display = 'flex';

        const showing = total === 0 ? 0 : start + 1;
        const showingEnd = Math.min(start + perPage, total);
        info.textContent = `showing ${showing}–${showingEnd} of ${total}`;

        let btns = '';
        btns += `<button class="page-btn" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;

        let pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages = [1, 2];
            if (currentPage > 4) pages.push('...');
            for (let i = Math.max(3, currentPage-1); i <= Math.min(totalPages-2, currentPage+1); i++) pages.push(i);
            if (currentPage < totalPages - 3) pages.push('...');
            pages.push(totalPages - 1, totalPages);
            pages = [...new Set(pages)];
        }

        pages.forEach(p => {
            if (p === '...') btns += `<span style="padding: 0 4px; color: var(--text-dim); font-size: 12px;">…</span>`;
            else btns += `<button class="page-btn ${p===currentPage?'active':''}" onclick="goPage(${p})">${p}</button>`;
        });

        btns += `<button class="page-btn" onclick="goPage(${currentPage+1})" ${currentPage===totalPages?'disabled':''}>›</button>`;
        controls.innerHTML = btns;
    }

    function goPage(p) {
        const perPage = parseInt(document.getElementById('perPage').value);
        const totalPages = Math.ceil(filtered.length / perPage) || 1;
        if (p < 1 || p > totalPages) return;
        currentPage = p;
        renderTable();
    }

    async function loadJobs() {
        const btn = document.getElementById('refreshBtn');
        btn.classList.add('spinning');

        try {
            const res = await fetch('/oban-status');
            const data = await res.json();

            document.getElementById('s-available').textContent = data.summary.available ?? 0;
            document.getElementById('s-scheduled').textContent = data.summary.scheduled ?? 0;
            document.getElementById('s-executing').textContent = data.summary.executing ?? 0;
            document.getElementById('s-completed').textContent = data.summary.completed ?? 0;
            document.getElementById('s-retryable').textContent = data.summary.retryable ?? 0;
            document.getElementById('s-cancelled').textContent = data.summary.cancelled ?? 0;
            document.getElementById('s-discarded').textContent = data.summary.discarded ?? 0;

            allJobs = data.jobs || [];
            renderTable();
            document.getElementById('lastRefresh').textContent = 'last refresh: ' + new Date().toLocaleTimeString();
        } catch (e) {
            document.getElementById('tableBody').innerHTML = `<div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4M12 16h.01" stroke-linecap="round"/>
                </svg>
                Could not load jobs. Make sure Laravel is running.
            </div>`;
        }

        btn.classList.remove('spinning');
    }

    startTimer();
    loadJobs();
</script>
</body>
</html>