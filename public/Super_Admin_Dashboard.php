<?php
// Session guard — the only way into this page is logging in as a super_admin through
// Admin_Log_In.html (username + password). Anyone who loads this URL directly without
// a valid super_admin session is bounced straight back to the login page.
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    header('Location: Admin_Log_In.html');
    exit;
}
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
            overflow: hidden;
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
                <a href="#" class="dropdown-item logout" id="logoutBtn">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- ══════════════════════════════ TAB NAV ══════════════════════════════ -->
    <div class="tab-nav">
        <button class="tab-btn active" data-tab="pending">
            Pending Approvals
        </button>
        <button class="tab-btn" data-tab="barangays">
            Active Barangay Admins
        </button>
    </div>

    <!-- ══════════════════════════════ MAIN CONTENT ══════════════════════════════ -->
    <div class="main-wrapper">

        <!-- QUICK STATS -->
        <div class="stats-row" id="statsRow">
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

        <!-- ═══ TAB 1: PENDING APPROVALS ═══ -->
        <div class="tab-panel active" id="tab-pending">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Pending Approvals</div>
                        <div class="card-subtitle">Barangay admins awaiting your review</div>
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

        <!-- ═══ TAB 2: ALL BARANGAY ADMINS ═══ -->
        <div class="tab-panel" id="tab-barangays">
            <div class="outer-card">
                <div class="card-top-row">
                    <div>
                        <div class="card-header-title">Active Barangay Admins</div>
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
                            placeholder="Search barangay or admin…">
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

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // All barangay admin rows as last fetched from the server — tabs/filters/search
        // all operate on this one array client-side rather than re-fetching each time.
        let allAdmins = [];
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
                allAdmins = response.barangay_admins || [];
                renderStats();
                renderPendingTable();
                renderBarangaysTable();
            }).fail(function () {
                showToast('Could not reach the server.', 'error');
            });
        }

        function renderStats() {
            const distinctBarangays = new Set(allAdmins.map(a => a.barangay_name)).size;
            const pending = allAdmins.filter(a => a.approval_status === 'pending' && a.is_active).length;
            const approved = allAdmins.filter(a => a.approval_status === 'approved' && a.is_active).length;
            const archived = allAdmins.filter(a => !a.is_active).length;

            $('#statTotal').text(distinctBarangays);
            $('#statPending').text(pending);
            $('#statApproved').text(approved);
            $('#statArchived').text(archived);
        }

        // ── TAB 1: PENDING APPROVALS ──
        // Shows admins genuinely awaiting review — excludes archived admins, whose
        // barangay also shows 'pending' (that just means the slot is open again).
        function renderPendingTable() {
            const search = $('#searchPending').val().toLowerCase().trim();
            let rows = allAdmins.filter(a => a.approval_status === 'pending' && a.is_active);

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

        // ── TAB 2: ACTIVE BARANGAY ADMINS (with Active/Archived filter) ──
        function renderBarangaysTable() {
            const search = $('#searchBarangays').val().toLowerCase().trim();
            const filter = $('#filterStatus').val(); // 'ACTIVE' | 'ARCHIVED'

            let rows = allAdmins.filter(a =>
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
                $tbody.append('<tr class="empty-row"><td colspan="7">No ' + (filter === 'ARCHIVED' ? 'archived' : 'active') + ' admins found</td></tr>');
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
            approve: { icon: '✅', header: '', title: 'Approve Admin', heading: 'Approve this admin?', btnClass: 'success', msg: b => `This grants the admin for <span class="modal-highlight">${b}</span> full access to their barangay dashboard.` },
            reject: { icon: '🚫', header: 'danger', title: 'Reject Admin', heading: 'Reject this registration?', btnClass: 'danger', msg: b => `The registration for <span class="modal-highlight">${b}</span> will be marked rejected.` },
            archive: { icon: '📦', header: 'amber', title: 'Archive Admin', heading: 'Archive this admin?', btnClass: 'danger', msg: b => `This deactivates the account and frees up <span class="modal-highlight">${b}</span> for a new admin to register.` },
            restore: { icon: '♻️', header: '', title: 'Restore Admin', heading: 'Restore this admin?', btnClass: 'success', msg: b => `This reactivates the account for <span class="modal-highlight">${b}</span>, if the barangay is still unoccupied.` }
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