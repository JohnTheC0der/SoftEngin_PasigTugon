<?php
// Super-admin-only page. require_page_role() re-checks the database on every load: the person
// must be signed in as an active super_admin. Citizens and staff are sent to their own home page;
// everyone else goes to Sign_In.html.
// Must run before any HTML is output, so this block stays at the very top of the file.
require_once __DIR__ . '/../src/db_connect.php';
require_once __DIR__ . '/../src/auth.php';
require_page_role($pdo, 'super_admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasig Tugon! - Super Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gantari:wght@400;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="PasigTugon_Logo.png">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="response-timer.js"></script>

    <style>
        :root {
            --primary-blue: #0e48a1;
            --dark-blue: #071d49;
            --accent-cyan: #38bdf8;
            --card-glass: rgba(15, 23, 42, 0.65);
            --card-border: rgba(255, 255, 255, 0.15);
            --white: #ffffff;
            --badge-blue: #2563eb;
            --table-header: #083D8F;
            --table-bg: #6BA3E3;
            --sky-blue: #38A2EF;
            --btn-done: #10B981;
            --btn-delete: #EF4444;
            --btn-warn: #F59E0B;
            --ink: #052350;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Gantari', sans-serif;
        }

        body {
            min-height: 100vh;
            background: url('background.png') center/cover no-repeat fixed;
            color: var(--white);
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
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

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
        }

        .brand-title {
            font-weight: 900;
            font-size: 1.6rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1;
        }

        .brand-title .blue-text {
            color: var(--accent-cyan);
        }

        .brand-sub {
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #93C5FD;
            margin-top: 4px;
        }

        .super-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #1c1003;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            padding: 4px 12px;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 14px;
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
            width: 210px;
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
            background: none;
            border: none;
            text-align: left;
            width: 100%;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-cyan);
        }

        .dropdown-item.logout {
            color: #f87171;
            margin-top: 6px;
            padding-top: 6px;
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* ── TAB NAV ── */
        .tab-nav {
            display: flex;
            gap: 4px;
            padding: 16px 40px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(4, 39, 120, 0.4);
            backdrop-filter: blur(10px);
        }

        .tab-btn {
            padding: 10px 22px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: var(--transition);
            border-radius: 8px 8px 0 0;
        }

        .tab-btn:hover {
            color: rgba(255, 255, 255, 0.85);
        }

        .tab-btn.active {
            color: var(--accent-cyan);
            border-bottom-color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.08);
        }

        /* ── MAIN LAYOUT ── */
        .main-wrapper {
            flex: 1;
            padding: 2rem 2.5rem;
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── STAT CARDS ROW ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-icon.blue {
            background: rgba(37, 99, 235, 0.25);
        }

        .stat-icon.green {
            background: rgba(16, 185, 129, 0.25);
        }

        .stat-icon.amber {
            background: rgba(245, 158, 11, 0.25);
        }

        .stat-icon.red {
            background: rgba(239, 68, 68, 0.25);
        }

        .stat-text-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            color: var(--white);
        }

        .stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.55);
        }

        /* ── OUTER CARD ── */
        .outer-card {
            background: var(--card-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-header-title {
            font-size: 1.9rem;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .card-subtitle {
            font-size: 0.75rem;
            font-weight: 700;
            color: #93C5FD;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 4px;
        }

        .header-divider {
            border: none;
            border-bottom: 2px solid var(--accent-cyan);
            opacity: 0.7;
        }

        /* ── SEARCH + FILTER BAR ── */
        .toolbar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .search-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            color: var(--white);
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            transition: var(--transition);
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .search-input:focus {
            border-color: var(--accent-cyan);
            background: rgba(255, 255, 255, 0.12);
        }

        .filter-select {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            color: var(--white);
            font-size: 0.88rem;
            font-weight: 700;
            outline: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select option {
            background: #0f172a;
            color: var(--white);
        }

        .filter-select:focus {
            border-color: var(--accent-cyan);
        }

        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.12);
        }

        .action-btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: var(--badge-blue);
            color: var(--white);
        }

        .btn-success {
            background: var(--btn-done);
            color: var(--white);
        }

        .btn-danger {
            background: var(--btn-delete);
            color: var(--white);
        }

        .btn-warn {
            background: var(--btn-warn);
            color: #1c1003;
        }

        /* ── TABLE ── */
        .inner-box {
            background: rgba(107, 163, 227, 0.92);
            border-radius: 20px;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead tr {
            background: var(--table-header);
        }

        .data-table th {
            padding: 14px 16px;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.9);
            text-align: left;
            white-space: nowrap;
        }

        .data-table th:first-child {
            padding-left: 20px;
        }

        .data-table th:last-child {
            text-align: center;
        }

        .data-table tbody tr {
            border-bottom: 1px solid rgba(8, 61, 143, 0.15);
            transition: background 0.15s ease;
        }

        .data-table tbody tr:last-child {
            border-bottom: none;
        }

        .data-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.18);
        }

        .data-table td {
            padding: 13px 16px;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--ink);
            vertical-align: middle;
        }

        .data-table td:first-child {
            padding-left: 20px;
        }

        .data-table td:last-child {
            text-align: center;
        }

        .data-table .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--ink);
            font-weight: 700;
            opacity: 0.6;
        }

        /* Inline row action buttons */
        .row-btn {
            padding: 6px 14px;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .row-btn:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        .row-btn.approve {
            background: var(--btn-done);
            color: #fff;
        }

        .row-btn.reject {
            background: var(--btn-delete);
            color: #fff;
        }

        .row-btn.archive {
            background: #64748b;
            color: #fff;
        }

        .row-btn.restore {
            background: var(--badge-blue);
            color: #fff;
        }

        .row-btn.view {
            background: var(--table-header);
            color: #fff;
        }

        .btn-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ── STATUS PILLS ── */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .pill::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .pill.pending {
            background: rgba(245, 158, 11, 0.2);
            color: #92400e;
        }

        .pill.approved {
            background: rgba(16, 185, 129, 0.2);
            color: #065f46;
        }

        .pill.rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #7f1d1d;
        }

        .pill.active {
            background: rgba(37, 99, 235, 0.2);
            color: #1e3a8a;
        }

        .pill.archived {
            background: rgba(100, 116, 139, 0.2);
            color: #1e293b;
        }

        /* ── PAGINATION ── */
        .pagination-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: rgba(8, 61, 143, 0.08);
            border-top: 1px solid rgba(8, 61, 143, 0.2);
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--ink);
        }

        .page-btns {
            display: flex;
            gap: 6px;
        }

        .page-btn {
            width: 32px;
            height: 32px;
            border: 2px solid rgba(8, 61, 143, 0.3);
            border-radius: 8px;
            background: transparent;
            color: var(--ink);
            font-weight: 800;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--table-header);
            color: #fff;
            border-color: var(--table-header);
        }

        /* ── TAB PANEL ── */
        .tab-panel {
            display: none;
            flex-direction: column;
            gap: 20px;
        }

        .tab-panel.active {
            display: flex;
        }

        /* ── MODALS ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(3, 10, 26, 0.75);
            backdrop-filter: blur(12px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-panel {
            width: 460px;
            max-width: 92vw;
            max-height: 90vh;
            overflow-y: auto;
            background: rgba(15, 23, 42, 0.92);
            backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: 22px;
            overflow-x: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.2s ease;
        }

        .modal-panel.wide {
            width: 620px;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, rgba(14, 72, 161, 0.85), rgba(7, 29, 73, 0.95));
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.85), rgba(153, 27, 27, 0.95));
        }

        .modal-header.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.85), rgba(5, 76, 53, 0.95));
        }

        .modal-header.amber {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.85), rgba(120, 53, 15, 0.95));
        }

        .modal-title {
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.75);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 28px 24px;
        }

        .modal-body.centered {
            text-align: center;
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        .modal-body h3 {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--white);
            margin-bottom: 8px;
        }

        .modal-body p {
            font-size: 0.88rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .modal-highlight {
            color: var(--accent-cyan);
            font-weight: 800;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .swiss-btn {
            flex: 1;
            padding: 11px 22px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            transition: var(--transition);
        }

        .swiss-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .swiss-btn:active {
            transform: translateY(0);
        }

        .swiss-btn.primary {
            background: var(--badge-blue);
            color: #fff;
        }

        .swiss-btn.success {
            background: var(--btn-done);
            color: #fff;
        }

        .swiss-btn.danger {
            background: var(--btn-delete);
            color: #fff;
        }

        .swiss-btn.neutral {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* ── FORM GROUPS (Create Super Admin modal) ── */
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 16px;
        }

        .form-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #93C5FD;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.08);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: var(--white);
            font-size: 0.95rem;
            font-weight: 600;
            outline: none;
            transition: var(--transition);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
            font-weight: 500;
        }

        .form-input:focus {
            border-color: var(--accent-cyan);
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
        }

        .form-hint {
            font-size: 0.7rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.4);
            line-height: 1.4;
        }

        .form-error {
            font-size: 0.72rem;
            font-weight: 700;
            color: #fca5a5;
            display: none;
        }

        .form-error.show {
            display: block;
        }

        /* ── EXPECTED RESPONSE TIME (live countdown) ── */
        .eta { white-space: nowrap; min-width: 150px; }
        .eta-time { display: block; font-size: 0.88rem; font-weight: 900; font-variant-numeric: tabular-nums; }
        .eta-sub { display: block; margin-top: 1px; font-size: 0.68rem; font-weight: 700; color: #1E3A8A; opacity: 0.8; }
        .eta.ok .eta-time { color: #065F46; }
        .eta.warn .eta-time { color: #92400E; }
        .eta.late .eta-time { color: #B91C1C; }
        .eta.closed .eta-time { color: #334155; }
        .cm-value.eta-ok { color: #6EE7B7; }
        .cm-value.eta-warn { color: #FCD34D; }
        .cm-value.eta-late { color: #FCA5A5; }
        .cm-value.eta-closed { color: #CBD5E1; }
        .cm-sub { display: block; margin-top: 2px; font-size: 0.75rem; font-weight: 700; color: #93C5FD; }
        .cm-value.small { font-size: 0.9rem; word-break: break-all; }
        .name-cell { font-weight: 800; }
        .sub-line { display: block; font-size: 0.72rem; font-weight: 700; opacity: 0.7; }
        .modal-panel.xl { width: 1120px; max-width: 96vw; overflow-y: auto; }
        #ctHistoryTbody td { white-space: nowrap; padding: 11px 12px; }
        #citizenModal .data-table th { padding: 12px; }
        #citizenModal .concern-snippet { max-width: 170px; }
        .history-box { margin-top: 18px; }
        .history-title { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: #93C5FD; margin-bottom: 10px; }

        /* ── TOAST NOTIFICATION ── */
        .toast-container {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--white);
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            max-width: 340px;
            backdrop-filter: blur(20px);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast.success {
            background: rgba(16, 185, 129, 0.9);
        }

        .toast.error {
            background: rgba(239, 68, 68, 0.9);
        }

        .toast.info {
            background: rgba(37, 99, 235, 0.9);
        }

        /* ── LOADING SPINNER ── */
        .spinner {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading-row td {
            text-align: center;
            padding: 40px;
        }

        /* ── OVERVIEW / CONCERNS TABS ── */
        .ov-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .ov-box {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            padding: 18px 20px;
        }

        .ov-box h3 {
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #93C5FD;
            margin-bottom: 14px;
        }

        .rank-row {
            display: grid;
            grid-template-columns: 112px 1fr 34px;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .rank-row .rank-pos {
            color: rgba(255, 255, 255, 0.5);
            margin-right: 6px;
        }

        .rank-track {
            height: 10px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.12);
            overflow: hidden;
        }

        .rank-track>span {
            display: block;
            height: 100%;
            border-radius: 10px;
        }

        .rank-count {
            text-align: right;
            font-weight: 900;
        }

        .ov-empty {
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
        }

        .kw-cloud {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .kw-tag {
            background: rgba(56, 189, 248, 0.16);
            border: 1px solid rgba(56, 189, 248, 0.35);
            color: #BAE6FD;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .data-table td.num,
        .data-table th.num {
            text-align: center;
        }

        .data-table .case-no {
            font-weight: 900;
            white-space: nowrap;
        }

        .data-table .concern-snippet {
            display: block;
            max-width: 230px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .table-scroll-x {
            overflow-x: auto;
        }

        .barangay-link {
            cursor: pointer;
            font-weight: 800;
            color: var(--table-header);
            text-decoration: underline;
            text-decoration-style: dotted;
        }

        .pill.p-high {
            background: rgba(239, 68, 68, 0.22);
            color: #7f1d1d;
        }

        .pill.p-medium {
            background: rgba(245, 158, 11, 0.25);
            color: #78350f;
        }

        .pill.p-low {
            background: rgba(16, 185, 129, 0.22);
            color: #065f46;
        }

        .readonly-note {
            font-size: 0.78rem;
            font-weight: 700;
            color: #FDE68A;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 12px;
            padding: 10px 14px;
        }

        .cm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 16px;
            margin-bottom: 16px;
        }

        .cm-grid .full {
            grid-column: 1 / -1;
        }

        .cm-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #93C5FD;
            margin-bottom: 3px;
        }

        .cm-value {
            font-weight: 800;
            font-size: 0.95rem;
        }

        .cm-text {
            background: #E0F2FE;
            color: #032B69;
            border-radius: 14px;
            padding: 16px;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.6;
            max-height: 240px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 14px;
        }

        @media (max-width: 1000px) {
            .ov-grid {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .main-wrapper {
                padding: 1.2rem;
            }

            .tab-nav {
                padding: 12px 16px 0;
                overflow-x: auto;
            }

            .tab-btn {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>

    <!-- ══════════════════════════════ NAVBAR ══════════════════════════════ -->
    <nav class="navbar">
        <div class="brand-title">
            <span class="blue-text">PASIG</span> <span class="white-text">TUGON!</span>
        </div>
        <div class="menu-container">
            <button class="menu-toggle-btn" id="menuBtn" aria-label="Toggle Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="Super_Admin_Setting_Page.php" class="dropdown-item">Settings</a>
                <a href="#" class="dropdown-item logout" id="logoutBtn">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- ══════════════════════════════ TAB NAV ══════════════════════════════ -->
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="overview">
            Overview
        </button>
        <button class="tab-btn" data-tab="concerns">
            Concerns
        </button>
        <button class="tab-btn" data-tab="citizens">
            Citizens
        </button>
        <button class="tab-btn" data-tab="pending">
            Pending Approvals
        </button>
        <button class="tab-btn" data-tab="barangays">
            Active Barangay Staff
        </button>
    </div>

    <!-- ══════════════════════════════ MAIN CONTENT ══════════════════════════════ -->
    <div class="main-wrapper">

        <!-- QUICK STATS -->
        <div class="stats-row" id="statsRow" style="display:none;">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <!-- Buildings / Barangay Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="16" height="20" x="4" y="2" rx="2" ry="2" />
                        <path d="M9 22v-4h6v4" />
                        <path d="M8 6h.01" />
                        <path d="M16 6h.01" />
                        <path d="M12 6h.01" />
                        <path d="M12 10h.01" />
                        <path d="M12 14h.01" />
                        <path d="M16 10h.01" />
                        <path d="M16 14h.01" />
                        <path d="M8 10h.01" />
                        <path d="M8 14h.01" />
                    </svg>
                </div>
                <div class="stat-text-group">
                    <div class="stat-value" id="statTotal">—</div>
                    <div class="stat-label">Total Barangays</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon amber">
                    <!-- Hourglass / Pending Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 22h14" />
                        <path d="M5 2h14" />
                        <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22" />
                        <path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                    </svg>
                </div>
                <div class="stat-text-group">
                    <div class="stat-value" id="statPending">—</div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <!-- Check Circle / Approved Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <path d="m9 11 3 3L22 4" />
                    </svg>
                </div>
                <div class="stat-text-group">
                    <div class="stat-value" id="statApproved">—</div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <!-- Archive Box Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="5" x="2" y="3" rx="1" />
                        <path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" />
                        <path d="M10 12h4" />
                    </svg>
                </div>
                <div class="stat-text-group">
                    <div class="stat-value" id="statArchived">—</div>
                    <div class="stat-label">Archived Accounts</div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: OVERVIEW (all barangays, read-only) ═══ -->
        <div class="tab-panel active" id="tab-overview">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon blue">📋</div>
                    <div class="stat-text-group">
                        <div class="stat-value" id="ovActive">—</div>
                        <div class="stat-label">Active Concerns</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🚨</div>
                    <div class="stat-text-group">
                        <div class="stat-value" id="ovHigh">—</div>
                        <div class="stat-label">High-Priority Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">🏘️</div>
                    <div class="stat-text-group">
                        <div class="stat-value" id="ovBarangays">—</div>
                        <div class="stat-label">Barangays With Active Concerns</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">🗄️</div>
                    <div class="stat-text-group">
                        <div class="stat-value" id="ovArchived">—</div>
                        <div class="stat-label">Archived Concerns</div>
                    </div>
                </div>
            </div>

            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Concerns Overview</div>
                        <div class="card-subtitle">All barangays combined · active concerns only · view only</div>
                    </div>
                </div>
                <hr class="header-divider">
                <div class="ov-grid">
                    <div class="ov-box">
                        <h3>Sectors Ranked</h3>
                        <div id="ovSectors"></div>
                    </div>
                    <div class="ov-box">
                        <h3>Priorities Ranked</h3>
                        <div id="ovPriorities"></div>
                    </div>
                    <div class="ov-box">
                        <h3>Common Keywords</h3>
                        <div class="kw-cloud" id="ovKeywords"></div>
                    </div>
                </div>
            </div>

            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">By Barangay</div>
                        <div class="card-subtitle">Active concerns per barangay, busiest first · click a barangay to open its concerns</div>
                    </div>
                </div>
                <hr class="header-divider">
                <div class="inner-box">
                    <div class="table-scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Barangay</th>
                                    <th class="num">Active</th>
                                    <th class="num">High</th>
                                    <th class="num">Medium</th>
                                    <th class="num">Low</th>
                                    <th class="num">Health</th>
                                    <th class="num">Infrastructure</th>
                                    <th class="num">Crime</th>
                                    <th class="num">Environment</th>
                                    <th class="num">Archived</th>
                                </tr>
                            </thead>
                            <tbody id="ovBarangayTbody">
                                <tr class="loading-row">
                                    <td colspan="10">
                                        <div class="spinner"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Latest Concerns</div>
                        <div class="card-subtitle">The 8 most recent concerns from any barangay</div>
                    </div>
                </div>
                <hr class="header-divider">
                <div class="inner-box">
                    <div class="table-scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Case No.</th>
                                    <th>Barangay</th>
                                    <th>Date</th>
                                    <th style="text-align:left;">Concern</th>
                                </tr>
                            </thead>
                            <tbody id="ovRecentTbody">
                                <tr class="loading-row">
                                    <td colspan="4">
                                        <div class="spinner"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: CONCERNS (all barangays, read-only) ═══ -->
        <div class="tab-panel" id="tab-concerns">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">All Concerns</div>
                        <div class="card-subtitle">Every barangay · ongoing and archived</div>
                    </div>
                </div>
                <div class="readonly-note">View only. Archiving and changing a concern's sector or priority is done by each barangay's staff.</div>
                <hr class="header-divider">

                <div class="toolbar">
                    <div class="search-wrap">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input class="search-input" type="text" id="cSearch"
                            placeholder="Search concern, keyword, case no. or citizen…">
                    </div>
                    <select class="filter-select" id="cStatus" aria-label="Filter by status">
                        <option value="ongoing">Active (Ongoing)</option>
                        <option value="archived">Archived</option>
                        <option value="all">All Concerns</option>
                    </select>
                    <select class="filter-select" id="cBarangay" aria-label="Filter by barangay">
                        <option value="">All Barangays</option>
                    </select>
                    <select class="filter-select" id="cSector" aria-label="Filter by sector">
                        <option value="">All Sectors</option>
                        <option>Health</option>
                        <option>Infrastructure</option>
                        <option>Crime</option>
                        <option>Environment</option>
                    </select>
                    <select class="filter-select" id="cPriority" aria-label="Filter by priority">
                        <option value="">All Priorities</option>
                        <option>High</option>
                        <option>Medium</option>
                        <option>Low</option>
                    </select>
                </div>

                <div class="inner-box">
                    <div class="table-scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Case No.</th>
                                    <th>Barangay</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Sector</th>
                                    <th>Priority</th>
                                    <th>Response Time</th>
                                    <th style="text-align:left;">Concern</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="concernsTbody">
                                <tr class="loading-row">
                                    <td colspan="9">
                                        <div class="spinner"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-row" id="concernsPagRow" style="display:none;">
                        <span id="concernsPagInfo"></span>
                        <div class="page-btns" id="concernsPagBtns"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: PENDING APPROVALS ═══ -->
        <!-- ═══ TAB: CITIZENS (read-only) ═══ -->
        <div class="tab-panel" id="tab-citizens">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Citizens</div>
                        <div class="card-subtitle">Every registered citizen · their details and concern history</div>
                    </div>
                </div>
                <div class="readonly-note">View only. Citizen accounts and their concerns are not changed from here.</div>
                <hr class="header-divider">

                <div class="toolbar">
                    <div class="search-wrap">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input class="search-input" type="text" id="citSearch" placeholder="Search citizen name or email…">
                    </div>
                    <select class="filter-select" id="citFilter" aria-label="Filter citizens">
                        <option value="">All Citizens</option>
                        <option value="with">With concerns</option>
                        <option value="none">No concerns yet</option>
                    </select>
                </div>

                <div class="inner-box">
                    <div class="table-scroll-x">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="text-align:left;">Full Name</th>
                                    <th style="text-align:left;">Email</th>
                                    <th>Registered</th>
                                    <th>Last Sign-in</th>
                                    <th>Concerns</th>
                                    <th>Ongoing</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="citizensTbody">
                                <tr class="loading-row"><td colspan="8"><div class="spinner"></div></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-row" id="citPagRow" style="display:none;">
                        <span id="citPagInfo"></span>
                        <div class="page-btns" id="citPagBtns"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-panel" id="tab-pending">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Pending Approvals</div>
                        <div class="card-subtitle">Barangay staff awaiting your review</div>
                    </div>
                </div>
                <hr class="header-divider">

                <div class="toolbar">
                    <div class="search-wrap">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input class="search-input" type="text" id="searchPending"
                            placeholder="Search barangay or username…">
                    </div>
                </div>

                <div class="inner-box">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Barangay</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="pendingTbody">
                            <tr class="loading-row">
                                <td colspan="7">
                                    <div class="spinner"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="pagination-row" id="pendingPagRow" style="display:none;">
                        <span id="pendingPagInfo"></span>
                        <div class="page-btns" id="pendingPagBtns"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: ACTIVE BARANGAY STAFF ═══ -->
        <div class="tab-panel" id="tab-barangays">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Active Barangay Staff</div>
                        <div class="card-subtitle">Manage accounts across all Pasig City barangays</div>
                    </div>
                </div>
                <hr class="header-divider">

                <div class="toolbar">
                    <div class="search-wrap">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>
                        <input class="search-input" type="text" id="searchBarangays"
                            placeholder="Search barangay or staff…">
                    </div>
                    <select class="filter-select" id="filterStatus">
                        <option value="ACTIVE">Active</option>
                        <option value="ARCHIVED">Archived</option>
                    </select>
                </div>

                <div class="inner-box">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Barangay</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="barangaysTbody">
                            <tr class="loading-row">
                                <td colspan="7">
                                    <div class="spinner"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="pagination-row" id="barangaysPagRow" style="display:none;">
                        <span id="barangaysPagInfo"></span>
                        <div class="page-btns" id="barangaysPagBtns"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════ MODALS ══════════════════════════════ -->

    <!-- Confirmation Action Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-panel">
            <div class="modal-header" id="confirmModalHeader">
                <div class="modal-title" id="confirmModalTitle">Confirm Action</div>
                <button class="modal-close" id="closeConfirmModalBtn">&times;</button>
            </div>
            <div class="modal-body centered">
                <div class="modal-icon" id="confirmModalIcon">⚠️</div>
                <h3 id="confirmModalHeading">Are you sure?</h3>
                <p id="confirmModalMessage">This action cannot be undone.</p>
                <div class="modal-actions">
                    <button class="swiss-btn neutral" id="cancelConfirmBtn">Cancel</button>
                    <button class="swiss-btn primary" id="actionConfirmBtn">Proceed</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Concern card (read-only: no archive / re-classify controls exist for super admins) -->
    <div class="modal-overlay" id="concernModal">
        <div class="modal-panel wide">
            <div class="modal-header">
                <div class="modal-title" id="cmTitle">Concern</div>
                <button class="modal-close" id="closeConcernModalBtn" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="cm-grid">
                    <div><span class="cm-label">Barangay</span><span class="cm-value" id="cmBarangay">-</span></div>
                    <div><span class="cm-label">Submitted</span><span class="cm-value" id="cmDate">-</span></div>
                    <div><span class="cm-label">Status</span><span class="cm-value" id="cmStatus">-</span></div>
                    <div><span class="cm-label">Model Confidence</span><span class="cm-value" id="cmConf">-</span></div>
                    <div><span class="cm-label">Sector</span><span class="cm-value" id="cmSector">-</span></div>
                    <div><span class="cm-label">Priority</span><span class="cm-value" id="cmPriority">-</span></div>
                    <div><span class="cm-label">Citizen Name</span><span class="cm-value" id="cmCitizenName">-</span></div>
                    <div><span class="cm-label">Citizen Email</span><span class="cm-value small" id="cmCitizenEmail">-</span></div>
                    <div class="full"><span class="cm-label">Expected Response Time</span><span class="cm-value" id="cmEta">-</span><span class="cm-sub" id="cmEtaSub"></span></div>
                    <div class="full"><span class="cm-label">Keywords</span><span class="cm-value" id="cmKeywords">-</span></div>
                </div>
                <div class="cm-text" id="cmText"></div>
                <div class="readonly-note">View only. This concern is managed by the barangay's staff.</div>
            </div>
        </div>
    </div>

    <!-- Citizen details + concern history (read-only) -->
    <div class="modal-overlay" id="citizenModal">
        <div class="modal-panel xl">
            <div class="modal-header">
                <div class="modal-title" id="ctTitle">Citizen</div>
                <button class="modal-close" id="closeCitizenModalBtn" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="cm-grid">
                    <div><span class="cm-label">Full Name</span><span class="cm-value" id="ctName">-</span></div>
                    <div><span class="cm-label">Email</span><span class="cm-value small" id="ctEmail">-</span></div>
                    <div><span class="cm-label">Registered</span><span class="cm-value" id="ctJoined">-</span></div>
                    <div><span class="cm-label">Last Sign-in</span><span class="cm-value" id="ctLast">-</span></div>
                    <div><span class="cm-label">Concerns Submitted</span><span class="cm-value" id="ctTotal">-</span></div>
                    <div><span class="cm-label">Account</span><span class="cm-value" id="ctActive">-</span></div>
                </div>
                <div class="history-box">
                    <div class="history-title">Concern History</div>
                    <div class="inner-box">
                        <div class="table-scroll-x">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Case No.</th>
                                        <th>Barangay</th>
                                        <th>Submitted</th>
                                        <th>Status</th>
                                        <th>Sector</th>
                                        <th>Priority</th>
                                        <th>Response Time</th>
                                        <th style="text-align:left;">Concern</th>
                                    </tr>
                                </thead>
                                <tbody id="ctHistoryTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // All barangay staff rows as last fetched from the server — tabs/filters/search
        // all operate on this one array client-side rather than re-fetching each time.
        let allStaff = [];
        let pendingConfirmAction = null; // { action, userId, label } — set right before the confirm modal opens

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : text;
            return div.innerHTML;
        }

        function formatDate(raw) {
            if (!raw) return '—';
            const d = new Date(raw.replace(' ', 'T'));
            return isNaN(d) ? raw : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function showToast(message, type) {
            const toast = $('<div class="toast ' + (type || 'success') + '">' + escapeHtml(message) + '</div>');
            $('#toastContainer').append(toast);
            setTimeout(() => toast.fadeOut(300, () => toast.remove()), 3500);
        }

        // Adjust these paths if your folder structure differs —
        // this assumes public/Super_Admin_Dashboard.php and src/*.php as siblings.
        // ══════════ ALL-BARANGAY CONCERNS (read-only) ══════════
        const SECTOR_COLORS = { Health: '#ef4444', Infrastructure: '#2563eb', Crime: '#f59e0b', Environment: '#10b981' };
        const PRIORITY_COLORS = { High: '#ef4444', Medium: '#f59e0b', Low: '#10b981' };
        const PAGE_SIZE = 15;
        let allConcerns = [];
        let concernsPage = 1;

        function renderRanking(selector, rows, colors) {
            const $el = $(selector).empty();
            if (!rows.length) {
                $el.append('<div class="ov-empty">No active concerns yet</div>');
                return;
            }
            const max = Math.max.apply(null, rows.map(r => r.total));
            rows.forEach((r, i) => {
                $el.append(
                    '<div class="rank-row">' +
                    '<span><span class="rank-pos">' + (i + 1) + '.</span>' + escapeHtml(r.label) + '</span>' +
                    '<div class="rank-track"><span style="width:' + Math.max(4, (r.total / max) * 100) + '%;background:' + (colors[r.label] || '#94a3b8') + '"></span></div>' +
                    '<span class="rank-count">' + r.total + '</span></div>'
                );
            });
        }

        function loadOverview() {
            $.ajax({ url: '../src/get-super-admin-overview.php', method: 'GET', dataType: 'json' })
                .done(function (d) {
                    if (d.error) { showToast(d.error, 'error'); return; }
                    $('#ovActive').text(d.total_active);
                    $('#ovHigh').text(d.total_high);
                    $('#ovArchived').text(d.total_archived);
                    $('#ovBarangays').text(d.barangays.filter(b => b.active > 0).length);

                    renderRanking('#ovSectors', d.sector_ranking, SECTOR_COLORS);
                    renderRanking('#ovPriorities', d.priority_ranking, PRIORITY_COLORS);

                    const $kw = $('#ovKeywords').empty();
                    if (!d.common_keywords.length) $kw.append('<div class="ov-empty">No keywords yet</div>');
                    d.common_keywords.forEach(k => $kw.append('<span class="kw-tag">' + escapeHtml(k) + '</span>'));

                    const $b = $('#ovBarangayTbody').empty();
                    if (!d.barangays.length) {
                        $b.append('<tr class="empty-row"><td colspan="10">No barangays yet</td></tr>');
                    }
                    d.barangays.forEach(b => {
                        $b.append(
                            '<tr><td><span class="barangay-link" data-barangay="' + escapeHtml(b.name) + '">' + escapeHtml(b.name) + '</span></td>' +
                            '<td class="num"><strong>' + b.active + '</strong></td>' +
                            '<td class="num">' + b.priority.High + '</td><td class="num">' + b.priority.Medium + '</td><td class="num">' + b.priority.Low + '</td>' +
                            '<td class="num">' + b.sector.Health + '</td><td class="num">' + b.sector.Infrastructure + '</td>' +
                            '<td class="num">' + b.sector.Crime + '</td><td class="num">' + b.sector.Environment + '</td>' +
                            '<td class="num">' + b.archived + '</td></tr>'
                        );
                    });

                    const $r = $('#ovRecentTbody').empty();
                    if (!d.recent_concerns.length) {
                        $r.append('<tr class="empty-row"><td colspan="4">No concerns yet</td></tr>');
                    }
                    d.recent_concerns.forEach(c => {
                        $r.append(
                            '<tr><td class="case-no">#' + c.id + '</td><td>' + escapeHtml(c.barangay) + '</td><td>' + escapeHtml(c.date) + '</td>' +
                            '<td style="text-align:left;"><span class="concern-snippet">' + escapeHtml(c.text) + '</span></td></tr>'
                        );
                    });
                })
                .fail(function () { showToast('Could not load the overview.', 'error'); });
        }

        function loadConcerns() {
            $.ajax({ url: '../src/get-super-admin-concerns.php', method: 'GET', dataType: 'json' })
                .done(function (d) {
                    if (d.error) { showToast(d.error, 'error'); return; }
                    allConcerns = d.concerns || [];
                    ConcernTimer.markFetched();

                    // barangay dropdown = every barangay that has at least one concern
                    const keep = $('#cBarangay').val();
                    const names = Array.from(new Set(allConcerns.map(c => c.barangay))).sort();
                    const $sel = $('#cBarangay').empty().append('<option value="">All Barangays</option>');
                    names.forEach(n => $sel.append($('<option>').val(n).text(n)));
                    if (names.indexOf(keep) !== -1) $sel.val(keep);

                    renderConcerns();
                })
                .fail(function () {
                    $('#concernsTbody').html('<tr class="empty-row"><td colspan="9">Could not load concerns</td></tr>');
                });
        }

        function filteredConcerns() {
            const status = $('#cStatus').val();
            const barangay = $('#cBarangay').val();
            const sector = $('#cSector').val();
            const priority = $('#cPriority').val();
            const terms = $('#cSearch').val().toLowerCase().split(/\s+/).map(s => s.replace(/^#/, '')).filter(Boolean);
            return allConcerns.filter(c => {
                if (status !== 'all' && c.status !== status) return false;
                if (barangay && c.barangay !== barangay) return false;
                if (sector && c.sector !== sector) return false;
                if (priority && c.priority !== priority) return false;
                const hay = (c.text + ' ' + (c.keywords || '') + ' ' + c.id + ' ' + c.barangay + ' ' + (c.citizen_name || '') + ' ' + (c.citizen_email || '')).toLowerCase();
                return terms.every(t => hay.includes(t));
            });
        }

        function renderConcerns() {
            const rows = filteredConcerns();
            const pages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            if (concernsPage > pages) concernsPage = pages;
            const start = (concernsPage - 1) * PAGE_SIZE;
            const $tbody = $('#concernsTbody').empty();

            if (!rows.length) {
                $tbody.append('<tr class="empty-row"><td colspan="9">No concerns match these filters</td></tr>');
                $('#concernsPagRow').hide();
                return;
            }

            rows.slice(start, start + PAGE_SIZE).forEach(c => {
                const statusPill = c.status === 'archived'
                    ? '<span class="pill archived">Archived</span>'
                    : '<span class="pill active">Ongoing</span>';
                const prio = c.priority ? '<span class="pill p-' + escapeHtml(c.priority.toLowerCase()) + '">' + escapeHtml(c.priority) + '</span>' : '—';
                $tbody.append(
                    '<tr><td class="case-no">#' + c.id + '</td><td>' + escapeHtml(c.barangay) + '</td><td>' + escapeHtml(c.date) + '</td>' +
                    '<td>' + statusPill + '</td><td>' + escapeHtml(c.sector || '—') + '</td><td>' + prio + '</td>' +
                    ConcernTimer.cellHtml(c, escapeHtml) +
                    '<td style="text-align:left;"><span class="concern-snippet">' + escapeHtml(c.text) + '</span></td>' +
                    '<td><button class="row-btn view" data-id="' + c.id + '">View</button></td></tr>'
                );
            });

            $('#concernsPagRow').show();
            $('#concernsPagInfo').text('Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, rows.length) + ' of ' + rows.length);
            const $btns = $('#concernsPagBtns').empty();
            for (let p = 1; p <= pages; p++) {
                if (pages > 7 && p !== 1 && p !== pages && Math.abs(p - concernsPage) > 1) {
                    if (Math.abs(p - concernsPage) === 2) $btns.append('<span style="align-self:center;">…</span>');
                    continue;
                }
                $btns.append($('<button class="page-btn">').toggleClass('active', p === concernsPage).text(p).attr('data-page', p));
            }
        }

        function openConcernCard(id) {
            const c = allConcerns.find(x => x.id === id);
            if (!c) return;
            $('#cmTitle').text('Case #' + c.id);
            $('#cmBarangay').text(c.barangay);
            openCmId = c.id;
            $('#cmDate').text(ConcernTimer.dateTime(c.submitted_ts, true));
            $('#cmCitizenName').text(c.citizen_name || 'Anonymous');
            $('#cmCitizenEmail').text(c.citizen_email || '— (sent before citizen accounts)');
            $('#cmStatus').text(c.status === 'archived' ? 'Archived' : 'Ongoing');
            $('#cmConf').text(c.confidence === null ? '—' : c.confidence.toFixed(2));
            $('#cmSector').text(c.sector || '—');
            $('#cmPriority').text(c.priority || '—');
            $('#cmKeywords').text(c.keywords || '—');
            $('#cmText').text(c.text);
            $('#concernModal').addClass('active');
            tickTimers();
        }

        let openCmId = null;

        // ── Live countdowns (concerns table, concern card, citizen history) ──
        function tickTimers() {
            ConcernTimer.tick(allConcerns, $('#concernModal').hasClass('active') ? openCmId : null, {
                time: document.getElementById('cmEta'),
                sub: document.getElementById('cmEtaSub')
            });
            if ($('#citizenModal').hasClass('active') && openCitizen) {
                ConcernTimer.tick(openCitizen.concerns, null, null, 'h');
            }
        }
        setInterval(tickTimers, 1000);

        // ── CITIZENS TAB (read-only) ──
        let allCitizens = [];
        let openCitizen = null;
        let citPage = 1;

        function loadCitizens() {
            $.ajax({ url: '../src/get-super-admin-citizens.php', method: 'GET', dataType: 'json' })
                .done(function (d) {
                    if (d.error) { showToast(d.error, 'error'); return; }
                    allCitizens = d.citizens || [];
                    ConcernTimer.markFetched();
                    renderCitizens();
                })
                .fail(function () {
                    $('#citizensTbody').html('<tr class="empty-row"><td colspan="8">Could not load citizens</td></tr>');
                });
        }

        function filteredCitizens() {
            const f = $('#citFilter').val();
            const terms = $('#citSearch').val().toLowerCase().split(/\s+/).filter(Boolean);
            return allCitizens.filter(c => {
                if (f === 'with' && c.total_concerns === 0) return false;
                if (f === 'none' && c.total_concerns > 0) return false;
                const hay = (c.name + ' ' + c.email).toLowerCase();
                return terms.every(t => hay.includes(t));
            });
        }

        function renderCitizens() {
            const rows = filteredCitizens();
            const pages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            if (citPage > pages) citPage = pages;
            const start = (citPage - 1) * PAGE_SIZE;
            const $tbody = $('#citizensTbody').empty();

            if (!rows.length) {
                $tbody.append('<tr class="empty-row"><td colspan="8">No citizens found</td></tr>');
                $('#citPagRow').hide();
                return;
            }

            rows.slice(start, start + PAGE_SIZE).forEach((c, i) => {
                $tbody.append(
                    '<tr><td>' + (start + i + 1) + '</td>' +
                    '<td class="name-cell" style="text-align:left;">' + escapeHtml(c.name) + (c.is_active ? '' : ' <span class="pill archived">Deactivated</span>') + '</td>' +
                    '<td style="text-align:left;">' + escapeHtml(c.email) + '</td>' +
                    '<td>' + formatDate(c.created_at) + '</td>' +
                    '<td>' + (c.last_login_at ? formatDate(c.last_login_at) : '—') + '</td>' +
                    '<td>' + c.total_concerns + '</td><td>' + c.ongoing + '</td>' +
                    '<td><button class="row-btn view" data-id="' + c.user_id + '">View</button></td></tr>'
                );
            });

            $('#citPagRow').show();
            $('#citPagInfo').text('Showing ' + (start + 1) + '–' + Math.min(start + PAGE_SIZE, rows.length) + ' of ' + rows.length);
            const $btns = $('#citPagBtns').empty();
            for (let p = 1; p <= pages; p++) {
                if (pages > 7 && p !== 1 && p !== pages && Math.abs(p - citPage) > 1) {
                    if (Math.abs(p - citPage) === 2) $btns.append('<span style="align-self:center;">…</span>');
                    continue;
                }
                $btns.append($('<button class="page-btn">').toggleClass('active', p === citPage).text(p).attr('data-page', p));
            }
        }

        function openCitizenCard(userId) {
            const c = allCitizens.find(x => x.user_id === userId);
            if (!c) return;
            openCitizen = c;
            $('#ctTitle').text(c.name);
            $('#ctName').text(c.name);
            $('#ctEmail').text(c.email);
            $('#ctJoined').text(formatDate(c.created_at));
            $('#ctLast').text(c.last_login_at ? formatDate(c.last_login_at) : 'Never');
            $('#ctTotal').text(c.total_concerns + ' (' + c.ongoing + ' ongoing, ' + c.archived + ' archived)');
            $('#ctActive').text(c.is_active ? 'Active' : 'Deactivated');
            const $h = $('#ctHistoryTbody').empty();
            if (!c.concerns.length) {
                $h.append('<tr class="empty-row"><td colspan="8">This citizen has not submitted any concerns yet</td></tr>');
            }
            c.concerns.forEach(h => {
                const statusPill = h.status === 'archived' ? '<span class="pill archived">Archived</span>' : '<span class="pill active">Ongoing</span>';
                const prio = h.priority ? '<span class="pill p-' + escapeHtml(h.priority.toLowerCase()) + '">' + escapeHtml(h.priority) + '</span>' : '—';
                $h.append(
                    '<tr><td class="case-no">#' + h.id + '</td><td>' + escapeHtml(h.barangay) + '</td>' +
                    '<td>' + escapeHtml(ConcernTimer.dateTime(h.submitted_ts)) + '</td><td>' + statusPill + '</td>' +
                    '<td>' + escapeHtml(h.sector || '—') + '</td><td>' + prio + '</td>' +
                    ConcernTimer.cellHtml(h, escapeHtml, 'h') +
                    '<td style="text-align:left;"><span class="concern-snippet">' + escapeHtml(h.text) + '</span></td></tr>'
                );
            });
            $('#citizenModal').addClass('active');
        }

        function loadData() {
            $('#pendingTbody, #barangaysTbody').html('<tr class="loading-row"><td colspan="7"><div class="spinner"></div></td></tr>');

            $.ajax({
                url: '../src/get-super-admin-data.php',
                method: 'GET',
                dataType: 'json'
            }).done(function (response) {
                if (response.error) {
                    showToast(response.error, 'error');
                    return;
                }
                allStaff = response.barangay_staff || [];
                renderStats();
                renderPendingTable();
                renderBarangaysTable();
            }).fail(function () {
                showToast('Could not reach the server.', 'error');
            });
        }

        function renderStats() {
            const distinctBarangays = new Set(allStaff.map(a => a.barangay_name)).size;
            const pending = allStaff.filter(a => a.approval_status === 'pending' && a.is_active).length;
            const approved = allStaff.filter(a => a.approval_status === 'approved' && a.is_active).length;
            const archived = allStaff.filter(a => !a.is_active).length;

            $('#statTotal').text(distinctBarangays);
            $('#statPending').text(pending);
            $('#statApproved').text(approved);
            $('#statArchived').text(archived);
        }

        // ── TAB 1: PENDING APPROVALS ──
        // Shows staff genuinely awaiting review — excludes archived staff, whose
        // barangay also shows 'pending' (that just means the slot is open again).
        function renderPendingTable() {
            const search = $('#searchPending').val().toLowerCase().trim();
            let rows = allStaff.filter(a => a.approval_status === 'pending' && a.is_active);

            if (search) {
                rows = rows.filter(a =>
                    a.barangay_name.toLowerCase().includes(search) ||
                    a.username.toLowerCase().includes(search)
                );
            }

            const $tbody = $('#pendingTbody').empty();

            if (rows.length === 0) {
                $tbody.append('<tr class="empty-row"><td colspan="7">No pending approvals</td></tr>');
                return;
            }

            rows.forEach((a, i) => {
                $tbody.append(`
                    <tr>
                        <td>${i + 1}</td>
                        <td>${escapeHtml(a.barangay_name)}</td>
                        <td>${escapeHtml(a.username)}</td>
                        <td>${escapeHtml(a.email)}</td>
                        <td>${formatDate(a.created_at)}</td>
                        <td><span class="pill pending">Pending</span></td>
                        <td>
                            <div class="btn-group">
                                <button class="row-btn approve" data-user-id="${a.user_id}" data-barangay="${escapeHtml(a.barangay_name)}">Approve</button>
                                <button class="row-btn reject" data-user-id="${a.user_id}" data-barangay="${escapeHtml(a.barangay_name)}">Reject</button>
                            </div>
                        </td>
                    </tr>
                `);
            });
        }

        // ── ACTIVE BARANGAY STAFF (with Active/Archived filter) ──
        function renderBarangaysTable() {
            const search = $('#searchBarangays').val().toLowerCase().trim();
            const filter = $('#filterStatus').val(); // 'ACTIVE' | 'ARCHIVED'

            let rows = allStaff.filter(a =>
                filter === 'ARCHIVED' ? !a.is_active : (a.is_active && a.approval_status === 'approved')
            );

            if (search) {
                rows = rows.filter(a =>
                    a.barangay_name.toLowerCase().includes(search) ||
                    a.username.toLowerCase().includes(search)
                );
            }

            const $tbody = $('#barangaysTbody').empty();

            if (rows.length === 0) {
                $tbody.append('<tr class="empty-row"><td colspan="7">No ' + (filter === 'ARCHIVED' ? 'archived' : 'active') + ' staff accounts found</td></tr>');
                return;
            }

            rows.forEach((a, i) => {
                const pillClass = a.is_active ? 'active' : 'archived';
                const pillLabel = a.is_active ? 'Active' : 'Archived';
                const actionBtn = a.is_active
                    ? `<button class="row-btn archive" data-user-id="${a.user_id}" data-barangay="${escapeHtml(a.barangay_name)}">Archive</button>`
                    : `<button class="row-btn restore" data-user-id="${a.user_id}" data-barangay="${escapeHtml(a.barangay_name)}">Restore</button>`;

                $tbody.append(`
                    <tr>
                        <td>${i + 1}</td>
                        <td>${escapeHtml(a.barangay_name)}</td>
                        <td>${escapeHtml(a.username)}</td>
                        <td>${escapeHtml(a.email)}</td>
                        <td>${formatDate(a.created_at)}</td>
                        <td><span class="pill ${pillClass}">${pillLabel}</span></td>
                        <td><div class="btn-group">${actionBtn}</div></td>
                    </tr>
                `);
            });
        }

        // ── CONFIRM MODAL ──
        const CONFIRM_CONFIG = {
            approve: { icon: '✅', header: '', title: 'Approve Staff', heading: 'Approve this staff account?', btnClass: 'success', msg: b => `This grants the staff account for <span class="modal-highlight">${b}</span> full access to their barangay dashboard.` },
            reject: { icon: '🚫', header: 'danger', title: 'Reject Staff', heading: 'Reject this registration?', btnClass: 'danger', msg: b => `The registration for <span class="modal-highlight">${b}</span> will be marked rejected.` },
            archive: { icon: '📦', header: 'amber', title: 'Archive Staff', heading: 'Archive this staff account?', btnClass: 'danger', msg: b => `This deactivates the account and frees up <span class="modal-highlight">${b}</span> for a new staff member to register.` },
            restore: { icon: '♻️', header: '', title: 'Restore Staff', heading: 'Restore this staff account?', btnClass: 'success', msg: b => `This reactivates the account for <span class="modal-highlight">${b}</span>, if the barangay is still unoccupied.` }
        };

        function openConfirm(action, userId, barangayName) {
            const cfg = CONFIRM_CONFIG[action];
            pendingConfirmAction = { action, userId };

            $('#confirmModalHeader').attr('class', 'modal-header' + (cfg.header ? ' ' + cfg.header : ''));
            $('#confirmModalTitle').text(cfg.title);
            $('#confirmModalIcon').text(cfg.icon);
            $('#confirmModalHeading').text(cfg.heading);
            $('#confirmModalMessage').html(cfg.msg(barangayName));
            $('#actionConfirmBtn').attr('class', 'swiss-btn ' + cfg.btnClass);
            $('#confirmModal').addClass('active');
        }

        function runPendingAction() {
            if (!pendingConfirmAction) return;
            const { action, userId } = pendingConfirmAction;

            $.ajax({
                url: '../src/super-admin-action.php',
                method: 'POST',
                data: { action: action, user_id: userId },
                dataType: 'json'
            }).done(function (response) {
                $('#confirmModal').removeClass('active');
                showToast(response.message, response.success ? 'success' : 'error');
                if (response.success) loadData();
            }).fail(function () {
                $('#confirmModal').removeClass('active');
                showToast('Could not reach the server.', 'error');
            });
        }

        $(document.body).ready(function () {
            loadData();
            loadOverview();
            loadConcerns();

            // Dropdown Menu Toggle
            $('#menuBtn').on('click', function (e) {
                e.stopPropagation();
                $('#dropdownMenu').toggleClass('show');
            });

            $(document).on('click', function () {
                $('#dropdownMenu').removeClass('show');
            });

            // Logout
            $('#logoutBtn').on('click', function (e) {
                e.preventDefault();
                window.location.href = '../src/logout.php';
            });

            // Tab Navigation Switching
            $('.tab-btn').on('click', function () {
                const targetTab = $(this).data('tab');
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                $('.tab-panel').removeClass('active');
                $('#tab-' + targetTab).addClass('active');

                // Account counters belong to the two account tabs only
                $('#statsRow').toggle(targetTab === 'pending' || targetTab === 'barangays');
                // Concern tabs always show fresh data
                if (targetTab === 'overview') loadOverview();
                if (targetTab === 'concerns') loadConcerns();
                if (targetTab === 'citizens') loadCitizens();
            });

            // Concerns tab: filters, paging, view button (read-only)
            $('#cSearch').on('input', function () { concernsPage = 1; renderConcerns(); });
            $('#cStatus, #cBarangay, #cSector, #cPriority').on('change', function () { concernsPage = 1; renderConcerns(); });
            $('#concernsPagBtns').on('click', '.page-btn', function () {
                concernsPage = parseInt($(this).attr('data-page'), 10);
                renderConcerns();
            });
            $('#concernsTbody').on('click', '.row-btn.view', function () {
                openConcernCard(parseInt($(this).attr('data-id'), 10));
            });
            // Citizens tab: search, filter, paging, view history (read-only)
            $('#citSearch').on('input', function () { citPage = 1; renderCitizens(); });
            $('#citFilter').on('change', function () { citPage = 1; renderCitizens(); });
            $('#citPagBtns').on('click', '.page-btn', function () {
                citPage = parseInt($(this).attr('data-page'), 10);
                renderCitizens();
            });
            $('#citizensTbody').on('click', '.row-btn.view', function () {
                openCitizenCard(parseInt($(this).attr('data-id'), 10));
            });
            $('#closeCitizenModalBtn').on('click', function () { $('#citizenModal').removeClass('active'); });
            $('#citizenModal').on('click', function (e) { if (e.target === this) $(this).removeClass('active'); });

            $('#closeConcernModalBtn').on('click', function () { $('#concernModal').removeClass('active'); });
            $('#concernModal').on('click', function (e) { if (e.target === this) $(this).removeClass('active'); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape') $('#concernModal, #citizenModal').removeClass('active'); });

            // Overview → click a barangay to open its (active) concerns
            $('#ovBarangayTbody').on('click', '.barangay-link', function () {
                const name = $(this).attr('data-barangay');
                $('#cStatus').val('ongoing');
                $('#cSector, #cPriority').val('');
                $('#cSearch').val('');
                $('.tab-btn[data-tab="concerns"]').trigger('click');
                setTimeout(function () { $('#cBarangay').val(name); concernsPage = 1; renderConcerns(); }, 400);
            });

            // Search + filter (live, client-side against the already-fetched data)
            $('#searchPending').on('input', renderPendingTable);
            $('#searchBarangays').on('input', renderBarangaysTable);
            $('#filterStatus').on('change', renderBarangaysTable);

            // Row action buttons (event-delegated since rows are rendered dynamically)
            $('#pendingTbody').on('click', '.row-btn.approve', function () {
                openConfirm('approve', $(this).data('user-id'), $(this).data('barangay'));
            });
            $('#pendingTbody').on('click', '.row-btn.reject', function () {
                openConfirm('reject', $(this).data('user-id'), $(this).data('barangay'));
            });
            $('#barangaysTbody').on('click', '.row-btn.archive', function () {
                openConfirm('archive', $(this).data('user-id'), $(this).data('barangay'));
            });
            $('#barangaysTbody').on('click', '.row-btn.restore', function () {
                openConfirm('restore', $(this).data('user-id'), $(this).data('barangay'));
            });

            // Confirm modal controls
            $('#actionConfirmBtn').on('click', runPendingAction);
            $('#closeConfirmModalBtn, #cancelConfirmBtn').on('click', function () {
                $('#confirmModal').removeClass('active');
                pendingConfirmAction = null;
            });
        });
    </script>
</body>

</html>