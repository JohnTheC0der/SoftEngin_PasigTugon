<?php
// Citizen-only page. require_page_role() re-checks the database on every load: the person must be
// signed in as a citizen. Staff and super admins are sent to their own home page; everyone else
// goes to Sign_In.html.
// Must run before any HTML is output, so this block stays at the very top of the file.
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';
require_page_role($pdo, 'citizen');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasig Tugon! - My Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="PasigTugon_Logo.png">

    <style>
        :root {
            --accent-cyan: #38bdf8;
            --card-glass: rgba(15, 23, 42, 0.65);
            --card-border: rgba(255, 255, 255, 0.15);
            --white: #ffffff;
            --badge-blue: #2563eb;
            --table-header: #083D8F;
            --ink: #052350;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(14, 72, 161, 0.35), rgba(15, 23, 42, 0.75)),
                url('background.png') center/cover no-repeat fixed;
            background-color: #0f172a;
            color: var(--white);
            display: flex;
            flex-direction: column;
        }

        /* NAVBAR */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(4, 39, 120, 0.753);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--card-border);
            position: relative;
            z-index: 50;
        }

        .brand-title {
            font-weight: 900;
            font-size: 1.6rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .brand-title .blue-text {
            color: var(--accent-cyan);
        }

        .menu-container {
            position: relative;
        }

        .menu-toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--card-border);
            color: var(--white);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .menu-toggle-btn:hover {
            background: var(--badge-blue);
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 54px;
            width: 200px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 12px;
            display: none;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .dropdown-menu.show {
            display: flex;
        }

        .dropdown-item {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
        }

        .dropdown-item.logout {
            color: #f87171;
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* TAB BAR */
        .tab-bar {
            display: flex;
            gap: 8px;
            padding: 0 40px;
            background: rgba(4, 39, 120, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--card-border);
            position: relative;
            z-index: 40;
            overflow-x: auto;
        }

        .tab-link {
            padding: 18px 28px;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }

        .tab-link:hover {
            color: var(--white);
            background: rgba(255, 255, 255, 0.06);
        }

        .tab-link.active {
            color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.1);
            border-bottom-color: var(--accent-cyan);
        }

        /* LAYOUT */
        .main-wrapper {
            flex: 1;
            padding: 2.5rem 1.5rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .outer-card {
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 1200px;
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-header-title {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .card-subtitle {
            font-size: 0.8rem;
            font-weight: 800;
            color: #93C5FD;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 6px;
        }

        .header-divider {
            border: none;
            border-bottom: 2px solid var(--accent-cyan);
            opacity: 0.8;
        }

        .submit-link {
            background: var(--accent-cyan);
            color: #072B60;
            font-weight: 800;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .submit-link:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
        }

        .inner-box {
            background: rgba(107, 163, 227, 0.95);
            border-radius: 24px;
            padding: 1.5rem 1.8rem 1.6rem;
            color: #0A3C91;
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.2);
        }

        /* TABLE */
        .table-container {
            border: 2px solid var(--table-header);
            border-radius: 16px;
            overflow: hidden;
            background: #6BA3E3;
        }

        .table-scroll {
            max-height: 560px;
            overflow: auto;
        }

        .concerns-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .concerns-table thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: var(--table-header);
            color: #fff;
            padding: 15px 18px;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .concerns-table tbody tr {
            border-bottom: 1px solid rgba(8, 61, 143, 0.2);
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .concerns-table tbody tr:last-child {
            border-bottom: none;
        }

        .concerns-table tbody tr:hover {
            background: #5A95D6;
        }

        .concerns-table td {
            padding: 14px 18px;
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--ink);
            vertical-align: middle;
        }

        .case-cell {
            white-space: nowrap;
            font-weight: 900;
        }

        .concern-text {
            display: block;
            max-width: 360px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .concern-meta {
            display: block;
            margin-top: 3px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #1E3A8A;
            opacity: 0.75;
        }

        .badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            background: #93C5FD;
            color: #1E3A8A;
        }

        .badge.p-high {
            background: #FECACA;
            color: #991B1B;
        }

        .badge.p-medium {
            background: #FED7AA;
            color: #9A3412;
        }

        .badge.p-low {
            background: #BBF7D0;
            color: #166534;
        }

        /* EXPECTED RESPONSE TIME (live countdown) */
        .eta {
            min-width: 190px;
        }

        .eta-time {
            display: block;
            font-size: 1.05rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.01em;
        }

        .eta-sub {
            display: block;
            margin-top: 2px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #1E3A8A;
            opacity: 0.8;
        }

        .eta-bar {
            height: 6px;
            margin-top: 6px;
            border-radius: 6px;
            background: rgba(8, 61, 143, 0.2);
            overflow: hidden;
        }

        .eta-bar>span {
            display: block;
            height: 100%;
            width: 100%;
            border-radius: 6px;
            background: #059669;
            transition: width 1s linear;
        }

        .eta.ok .eta-time {
            color: #065F46;
        }

        .eta.warn .eta-time {
            color: #92400E;
        }

        .eta.warn .eta-bar>span {
            background: #D97706;
        }

        .eta.late .eta-time {
            color: #B91C1C;
        }

        .eta.late .eta-bar>span {
            background: #DC2626;
            width: 100%;
        }

        .eta.closed .eta-time {
            color: #334155;
        }

        .eta.closed .eta-bar>span {
            width: 100%;
            background: #94A3B8;
        }

        .state-cell {
            text-align: center;
            padding: 44px 20px !important;
            font-weight: 800;
            color: #083D8F;
            cursor: default;
        }

        .state-cell a {
            display: inline-block;
            margin-top: 14px;
            background: #083D8F;
            color: #fff;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 50px;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .table-footer-info {
            margin-top: 1.1rem;
            font-size: 0.8rem;
            font-weight: 800;
            color: #083D8F;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* DETAIL MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(5, 15, 35, 0.75);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            background: #0B3175;
            width: 100%;
            max-width: 560px;
            max-height: 92vh;
            overflow-y: auto;
            border-radius: 28px;
            padding: 2.2rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .modal-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close-btn:hover {
            background: #EF4444;
        }

        .modal-title {
            font-size: 1.6rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(56, 162, 239, 0.4);
            padding-bottom: 12px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
            margin-bottom: 1rem;
        }

        .detail-grid .full {
            grid-column: 1 / -1;
        }

        .detail-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 800;
            color: #93C5FD;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }

        .detail-value {
            font-weight: 800;
            font-size: 1rem;
        }

        .modal-text-content {
            background: #E0F2FE;
            color: #032B69;
            border-radius: 18px;
            padding: 1.2rem;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.6;
            max-height: 260px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        @media (max-width: 900px) {
            .navbar {
                padding: 16px 20px;
            }

            .tab-bar {
                padding: 0 16px;
            }

            .tab-link {
                padding: 16px 18px;
            }

            .outer-card {
                padding: 1.4rem;
            }

            .inner-box {
                padding: 1rem;
            }

            .card-header-title {
                font-size: 1.7rem;
            }

            .concerns-table th,
            .concerns-table td {
                padding: 10px 12px;
            }

            .concern-text {
                max-width: 190px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="brand-title">
            <span class="blue-text">PASIG</span> <span class="white-text">TUGON!</span>
        </div>
        <div class="menu-container">
            <button class="menu-toggle-btn" id="menuToggleBtn" aria-label="Toggle Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="dropdown-menu" id="navDropdown">
                <a href="../src/logout.php" class="dropdown-item logout">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="tab-bar">
        <a href="Citizen_Dashboard_Page.php" class="tab-link active">My Dashboard</a>
        <a href="Citizen_Concern_Station.php" class="tab-link">Concern Station</a>
    </div>

    <main class="main-wrapper">
        <div class="outer-card">
            <div class="card-top-row">
                <div>
                    <h1 class="card-header-title">My Concerns</h1>
                    <p class="card-subtitle" id="cardSubtitle">Concerns you have submitted · tracked by case number</p>
                </div>
                <a class="submit-link" href="Citizen_Concern_Station.php">+ Submit a Concern</a>
            </div>
            <hr class="header-divider">

            <div class="inner-box">
                <div class="table-container">
                    <div class="table-scroll">
                        <table class="concerns-table">
                            <thead>
                                <tr>
                                    <th>Case No.</th>
                                    <th>Concern</th>
                                    <th>Sector</th>
                                    <th>Priority</th>
                                    <th>Expected Response Time</th>
                                </tr>
                            </thead>
                            <tbody id="concernsBody">
                                <tr>
                                    <td colspan="5" class="state-cell">LOADING...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="table-footer-info" id="footerInfo"></div>
            </div>
        </div>
    </main>

    <!-- Concern details (read-only) -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-card">
            <button class="modal-close-btn" id="closeDetailBtn" aria-label="Close">✕</button>
            <h2 class="modal-title" id="detailTitle">Case</h2>
            <div class="detail-grid">
                <div><span class="detail-label">Barangay</span><span class="detail-value" id="dBarangay">-</span></div>
                <div><span class="detail-label">Submitted</span><span class="detail-value" id="dSubmitted">-</span></div>
                <div><span class="detail-label">Sector</span><span class="detail-value" id="dSector">-</span></div>
                <div><span class="detail-label">Priority</span><span class="detail-value" id="dPriority">-</span></div>
                <div class="full"><span class="detail-label">Expected Response</span><span class="detail-value" id="dEta">-</span></div>
            </div>
            <div class="modal-text-content" id="dText"></div>
        </div>
    </div>

    <script>
        // Escapes text from the database before it is injected as HTML (prevents stored XSS).
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : text;
            return div.innerHTML;
        }

        let concerns = [];     // rows from get-my-concerns.php
        let fetchedAt = 0;     // browser time (ms) when the rows were fetched
        let openCase = null;

        // Seconds left for a concern right now. The server sends `remaining_seconds` measured at the
        // moment of the fetch (using the database clock), and we count down from there, so a wrong
        // clock on this computer cannot make the timer wrong.
        function remainingNow(c) {
            return c.remaining_seconds - Math.floor((Date.now() - fetchedAt) / 1000);
        }

        const pad = n => String(n).padStart(2, '0');

        function formatDuration(totalSeconds) {
            const s = Math.abs(totalSeconds);
            const hours = Math.floor(s / 3600);
            return hours + 'h ' + pad(Math.floor((s % 3600) / 60)) + 'm ' + pad(s % 60) + 's';
        }

        function dueText(c) {
            const due = new Date((c.submitted_ts + c.response_hours * 3600) * 1000);
            return 'Due ' + due.toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
            });
        }

        // Returns what to display for a concern's countdown at this moment
        function etaState(c) {
            if (c.remaining_seconds === null) {
                return { cls: 'closed', time: 'Closed', pct: 100, sub: 'Handled by your barangay' };
            }
            const left = remainingNow(c);
            const total = c.response_hours * 3600;
            if (left <= 0) {
                return { cls: 'late', time: 'Overdue ' + formatDuration(left), pct: 100, sub: dueText(c) };
            }
            const pct = Math.max(0, Math.min(100, (left / total) * 100));
            return { cls: pct <= 25 ? 'warn' : 'ok', time: formatDuration(left), pct: pct, sub: dueText(c) };
        }

        function renderTable() {
            const $body = document.getElementById('concernsBody');
            if (concerns.length === 0) {
                $body.innerHTML = '<tr><td colspan="5" class="state-cell">YOU HAVEN\'T SUBMITTED ANY CONCERNS YET<br>' +
                    '<a href="Citizen_Concern_Station.php">Submit your first concern</a></td></tr>';
                document.getElementById('footerInfo').textContent = '';
                return;
            }
            $body.innerHTML = concerns.map(c => {
                const st = etaState(c);
                return '<tr data-case="' + c.case_number + '">' +
                    '<td class="case-cell">#' + c.case_number + '</td>' +
                    '<td><span class="concern-text">' + escapeHtml(c.text) + '</span>' +
                    '<span class="concern-meta">' + escapeHtml(c.barangay) + ' · ' + escapeHtml(formatSubmitted(c)) + '</span></td>' +
                    '<td>' + escapeHtml(c.sector) + '</td>' +
                    '<td><span class="badge p-' + escapeHtml(String(c.priority).toLowerCase()) + '">' + escapeHtml(c.priority) + '</span></td>' +
                    '<td class="eta ' + st.cls + '" data-eta="' + c.case_number + '">' +
                    '<span class="eta-time">' + escapeHtml(st.time) + '</span>' +
                    '<span class="eta-bar"><span style="width:' + st.pct + '%"></span></span>' +
                    '<span class="eta-sub">' + escapeHtml(st.sub) + '</span></td>' +
                    '</tr>';
            }).join('');
            document.getElementById('footerInfo').textContent =
                concerns.length + (concerns.length === 1 ? ' concern' : ' concerns') +
                ' · Response windows: High 36 hrs, Medium 72 hrs, Low 168 hrs';
        }

        function formatSubmitted(c) {
            return new Date(c.submitted_ts * 1000).toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit'
            });
        }

        // Runs every second: only touches the countdown cells, so rows never flicker
        function tick() {
            concerns.forEach(c => {
                const cell = document.querySelector('td[data-eta="' + c.case_number + '"]');
                if (!cell) return;
                const st = etaState(c);
                cell.className = 'eta ' + st.cls;
                cell.querySelector('.eta-time').textContent = st.time;
                cell.querySelector('.eta-bar > span').style.width = st.pct + '%';
                cell.querySelector('.eta-sub').textContent = st.sub;
            });
            if (openCase !== null) {
                const c = concerns.find(x => x.case_number === openCase);
                if (c) document.getElementById('dEta').textContent = etaState(c).time + ' · ' + etaState(c).sub;
            }
        }

        function loadConcerns() {
            fetch('../src/get-my-concerns.php', { credentials: 'same-origin' })
                .then(res => {
                    if (res.status === 401 || res.status === 403) {
                        window.location.href = 'Sign_In.html';
                        throw new Error('signed out');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    concerns = data.concerns || [];
                    if (data.citizen_name) {
                        document.getElementById('cardSubtitle').textContent =
                            'Welcome, ' + data.citizen_name + ' · tracked by case number';
                    }
                    fetchedAt = Date.now();
                    renderTable();
                })
                .catch(err => {
                    if (err.message === 'signed out') return;
                    if (concerns.length === 0) {
                        document.getElementById('concernsBody').innerHTML =
                            '<tr><td colspan="5" class="state-cell">COULD NOT LOAD YOUR CONCERNS<br>Refresh the page to try again.</td></tr>';
                    }
                });
        }

        // Concern details
        function openDetail(caseNumber) {
            const c = concerns.find(x => x.case_number === caseNumber);
            if (!c) return;
            openCase = caseNumber;
            document.getElementById('detailTitle').textContent = 'Case #' + c.case_number;
            document.getElementById('dBarangay').textContent = c.barangay;
            document.getElementById('dSubmitted').textContent = formatSubmitted(c);
            document.getElementById('dSector').textContent = c.sector;
            document.getElementById('dPriority').textContent = c.priority;
            document.getElementById('dText').textContent = c.text;
            tick();
            document.getElementById('detailModal').classList.add('active');
        }

        function closeDetail() {
            openCase = null;
            document.getElementById('detailModal').classList.remove('active');
        }

        document.getElementById('concernsBody').addEventListener('click', e => {
            const row = e.target.closest('tr[data-case]');
            if (row) openDetail(parseInt(row.dataset.case, 10));
        });
        document.getElementById('closeDetailBtn').addEventListener('click', closeDetail);
        document.getElementById('detailModal').addEventListener('click', e => {
            if (e.target === e.currentTarget) closeDetail();
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeDetail();
        });

        // Menu
        const dropdown = document.getElementById('navDropdown');
        document.getElementById('menuToggleBtn').addEventListener('click', e => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => dropdown.classList.remove('show'));

        loadConcerns();
        setInterval(tick, 1000);
        // Pick up changes made by barangay staff (e.g. a priority change moves the due time)
        setInterval(loadConcerns, 60000);
    </script>
</body>

</html>
