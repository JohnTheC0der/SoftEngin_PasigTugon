<?php
// Session guard — only a signed-in super_admin may open this page.
// Must run before any HTML is output, so this block stays at the very top of the file.
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
    <title>Pasig Tugon! - Super Admin Settings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gantari:wght@400;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="icon" type="image/x-icon" href="PasigTugon_Logo.png">

    <style>
        :root {
            --sky-blue: #38A2EF;
            --table-bg: #6BA3E3;
            --table-header: #083D8F;
            --modal-blue: #0B3175;
            --accent-cyan: #38bdf8;
            --card-glass: rgba(15, 23, 42, 0.65);
            --card-border: rgba(255, 255, 255, 0.15);
            --white: #ffffff;
            --badge-blue: #2563eb;
            --btn-done: #10B981;
            --btn-delete: #EF4444;
            --ink: #052350;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Gantari', sans-serif;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
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
            width: 220px;
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

        .dropdown-item:hover,
        .dropdown-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-cyan);
        }

        .dropdown-item.logout {
            color: #f87171;
            margin-top: 6px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 12px;
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* TAB BAR (sits under the navbar) */
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

        @media (max-width: 600px) {
            .tab-bar {
                padding: 0 16px;
            }

            .tab-link {
                padding: 16px 18px;
            }
        }

        /* ── LAYOUT ── */
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
            gap: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 1200px;
        }

        .card-header-title {
            font-size: 2.2rem;
            font-weight: 900;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.01em;
        }

        .card-subtitle {
            font-size: 0.8rem;
            font-weight: 800;
            color: #93C5FD;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 4px;
        }

        .header-divider {
            border: none;
            border-bottom: 2px solid var(--accent-cyan);
            opacity: 0.8;
            margin-top: -8px;
        }

        .inner-box {
            background: rgba(107, 163, 227, 0.95);
            border-radius: 24px;
            padding: 1.8rem;
            color: #0A3C91;
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.2);
        }

        .settings_grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        /* ── SETTINGS CARDS ── */
        .settings_card {
            background: #E0F2FE;
            border: 2px solid var(--table-header);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .card_header {
            background: var(--table-header);
            color: var(--white);
            padding: 16px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card_header h3 {
            font-size: 1.05rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .card_body {
            padding: 24px 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 18px;
            color: var(--ink);
        }

        .card_footer {
            padding: 0 22px 22px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form_group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form_label {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--table-header);
        }

        .form_input,
        .form_select {
            width: 100%;
            padding: 12px 16px;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--ink);
            background: #fff;
            border: 2px solid #93C5FD;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s ease;
        }

        .form_select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 22 14' fill='none'%3E%3Cpath d='M2 2L11 11L20 2' stroke='%23083D8F' stroke-width='3.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 42px;
        }

        .form_input:focus,
        .form_select:focus {
            border-color: var(--sky-blue);
            box-shadow: 0 0 0 4px rgba(56, 162, 239, 0.25);
        }

        .form_input::placeholder {
            font-weight: 600;
            opacity: 0.45;
        }

        .form_hint {
            font-size: 0.72rem;
            font-weight: 600;
            color: #475569;
            line-height: 1.4;
        }

        .backup_status {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: #fff;
            border: 2px solid #93C5FD;
            border-radius: 14px;
        }

        .backup_status_dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--btn-done);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25);
            flex-shrink: 0;
        }

        .backup_status_text {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--table-header);
        }

        .backup_status_date {
            font-size: 1rem;
            font-weight: 800;
            color: var(--ink);
        }

        .backup_list { list-style: none; display: flex; flex-direction: column; gap: 6px; }
        .backup_list li {
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
            padding: 8px 12px; background: #fff; border: 2px solid #BFDBFE; border-radius: 10px;
            font-size: 0.8rem; font-weight: 700; color: var(--ink);
        }
        .type_pill {
            font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em;
            padding: 2px 9px; border-radius: 50px; background: #DBEAFE; color: #1E3A8A;
        }
        .type_pill.auto { background: #D1FAE5; color: #065F46; }
        .backup_status_dot.warn { background: #F59E0B; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25); }
        .card_footer .swiss_btn { padding: 11px 10px; font-size: 0.78rem; letter-spacing: 0.02em; }
        .swiss_btn:disabled { opacity: 0.55; cursor: wait; transform: none; filter: none; }
        .modal_input {
            width: 100%; margin-top: 14px; padding: 12px 16px; border-radius: 12px;
            border: 2px solid rgba(255, 255, 255, 0.25); background: rgba(255, 255, 255, 0.08);
            color: #fff; font-size: 0.95rem; font-weight: 600; outline: none;
        }
        .modal_input:focus { border-color: var(--accent-cyan); }
        .modal_input::placeholder { color: rgba(255, 255, 255, 0.4); }

        /* ── PILL BUTTONS ── */
        .swiss_btn {
            flex: 1;
            padding: 11px 22px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: var(--table-header);
            color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease;
        }

        .swiss_btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.15);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .swiss_btn:active {
            transform: translateY(0);
        }

        .swiss_btn.btn_secondary {
            background: var(--sky-blue);
            color: #072B60;
        }

        /* ── MODALS ── */
        .modal_overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(3, 10, 26, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal_overlay.active {
            display: flex;
            opacity: 1;
        }

        .modal_panel {
            width: 440px;
            max-width: 90vw;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(24px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
            transform: scale(0.95) translateY(10px);
            animation: zoomIn 0.2s ease;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.92);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal_overlay.active .modal_panel {
            transform: none;
        }

        .modal_header_bar {
            background: linear-gradient(135deg, rgba(14, 72, 161, 0.8), rgba(7, 29, 73, 0.95));
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal_header_bar.danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(153, 27, 27, 0.95));
        }

        .modal_header_title {
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal_close_btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.8);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal_close_btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
            transform: rotate(90deg);
        }

        .modal_body {
            padding: 28px 24px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
        }

        .modal_body strong {
            font-weight: 800;
            color: var(--accent-cyan);
        }

        .modal_highlight {
            background: rgba(56, 162, 239, 0.15);
            border: 1px solid rgba(56, 162, 239, 0.4);
            border-radius: 12px;
            padding: 12px;
            margin-top: 16px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #93C5FD;
        }

        .modal_footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }

        .swiss_btn_modal {
            flex: 1;
            padding: 12px 18px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            border: 1px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .swiss_btn_modal:active {
            transform: scale(0.97);
        }

        .swiss_btn_modal.cancel {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.85);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .swiss_btn_modal.cancel:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #fff;
        }

        .swiss_btn_modal.confirm {
            background: var(--badge-blue);
            color: #fff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .swiss_btn_modal.confirm:hover {
            background: #3b82f6;
            transform: translateY(-1px);
        }

        .swiss_btn_modal.confirm_danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
        }

        .swiss_btn_modal.confirm_danger:hover {
            background: linear-gradient(135deg, #f87171, #ef4444);
            transform: translateY(-1px);
        }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            max-width: 340px;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            color: #fff;
            padding: 14px 22px;
            border: 1px solid var(--card-border);
            border-left: 5px solid var(--btn-done);
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.toast-error {
            border-left-color: var(--btn-delete);
        }

        @media (max-width: 900px) {
            .navbar {
                padding: 16px 20px;
            }

            .outer-card {
                padding: 1.5rem;
            }

            .inner-box {
                padding: 1.2rem;
            }

            .card-header-title {
                font-size: 1.7rem;
            }

            .settings_grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div id="toast" class="toast"></div>

    <!-- RESTORE CONFIRM MODAL -->
    <div class="modal_overlay" id="restoreModal">
        <div class="modal_panel">
            <div class="modal_header_bar danger">
                <h2 class="modal_header_title">Confirm Restore</h2>
                <button class="modal_close_btn" onclick="closeModal('restoreModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal_body">
                <p>This replaces <strong>all current data</strong> (barangays, admin accounts and concerns) with the
                    last backup. Anything recorded <strong>after</strong> that backup will be lost, and you will be
                    logged out.</p>
                <div class="modal_highlight">Last Backup: <span id="restoreDate">—</span></div>
                <input type="password" id="restorePassword" class="modal_input"
                    placeholder="Enter your password to confirm" autocomplete="current-password">
            </div>
            <div class="modal_footer">
                <button class="swiss_btn_modal cancel" onclick="closeModal('restoreModal')">Cancel</button>
                <button class="swiss_btn_modal confirm_danger" id="restoreConfirmBtn" onclick="confirmRestore()">Yes, Restore</button>
            </div>
        </div>
    </div>

    <!-- LOGOUT CONFIRM MODAL -->
    <div class="modal_overlay" id="logoutModal">
        <div class="modal_panel">
            <div class="modal_header_bar danger">
                <h2 class="modal_header_title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                        <line x1="12" y1="9" x2="12" y2="13" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    Log Out
                </h2>
                <button class="modal_close_btn" onclick="closeModal('logoutModal')" aria-label="Close">&times;</button>
            </div>
            <div class="modal_body">Are you sure you want to securely log out of the super admin portal?</div>
            <div class="modal_footer">
                <button class="swiss_btn_modal cancel" onclick="closeModal('logoutModal')">Cancel</button>
                <button class="swiss_btn_modal confirm_danger" onclick="executeLogout()">Log Out</button>
            </div>
        </div>
    </div>

    <!-- NAVBAR -->
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
                <a href="Super_Admin_Dashboard.php" class="dropdown-item">Dashboard</a>
                <a href="Super_Admin_Setting_Page.php" class="dropdown-item active">Settings</a>
                <a href="#" class="dropdown-item logout" id="logoutBtn">Log Out</a>
            </div>
        </div>
    </nav>

    <!-- MAIN -->
    <main class="main-wrapper">
        <div class="outer-card">
            <h1 class="card-header-title">Settings</h1>
            <p class="card-subtitle">Super Admin · Account credentials &amp; database maintenance</p>
            <hr class="header-divider">

            <div class="inner-box">
                <div class="settings_grid">

                    <!-- CARD 1: ACCOUNT CREDENTIALS -->
                    <div class="settings_card">
                        <div class="card_header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <h3>Account Credentials</h3>
                        </div>
                        <div class="card_body">
                            <div class="form_group">
                                <label class="form_label">Current Password</label>
                                <input type="password" id="currentPass" class="form_input"
                                    placeholder="Enter current password">
                            </div>
                            <div class="form_group">
                                <label class="form_label">New Password</label>
                                <input type="password" id="newPass" class="form_input" placeholder="Enter new password">
                                <span class="form_hint">Must be at least 8 characters, with an uppercase letter, a
                                    number, and a symbol.</span>
                            </div>
                            <div class="form_group">
                                <label class="form_label">Confirm New Password</label>
                                <input type="password" id="confirmPass" class="form_input"
                                    placeholder="Re-enter new password">
                            </div>
                        </div>
                        <div class="card_footer">
                            <button class="swiss_btn" onclick="updatePassword()">Update Password</button>
                        </div>
                    </div>

                    <!-- CARD 2: DATABASE BACKUP -->
                    <div class="settings_card">
                        <div class="card_header">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            <h3>Database Backup</h3>
                        </div>
                        <div class="card_body">
                            <div class="backup_status">
                                <div class="backup_status_dot" id="backupDot"></div>
                                <div>
                                    <div class="backup_status_text">Last Backup</div>
                                    <div class="backup_status_date" id="lastBackupText">Loading…</div>
                                </div>
                            </div>
                            <div class="form_group">
                                <label class="form_label">Auto-Backup Interval</label>
                                <select id="backupInterval" class="form_select">
                                    <option value="1">Everyday</option>
                                    <option value="2">Every 2 Days</option>
                                    <option value="3">Every 3 Days</option>
                                    <option value="5">Every 5 Days</option>
                                    <option value="7">Every 7 Days</option>
                                </select>
                                <span class="form_hint">Automatic backups run when a super admin opens this site after the
                                    interval has passed, or on a schedule if the server's Task Scheduler runs
                                    <code>run-scheduled-backup.php</code>. The 10 newest backups are kept.</span>
                            </div>
                            <div class="form_group">
                                <label class="form_label">Recent Backups</label>
                                <ul class="backup_list" id="backupList"></ul>
                            </div>
                        </div>
                        <div class="card_footer">
                            <button class="swiss_btn" id="saveIntervalBtn" onclick="saveInterval()">Save Interval</button>
                            <button class="swiss_btn" id="backupNowBtn" onclick="backupNow()">Back Up Now</button>
                            <button class="swiss_btn btn_secondary" onclick="openRestore()">Restore Last Backup</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        // Modals
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function openExitModal() {
            openModal('logoutModal');
        }

        function executeLogout() {
            // logout.php destroys the PHP session, then redirects to the login page itself
            window.location.href = '../src/logout.php';
        } // ← keep your original logout target

        // Navbar dropdown
        const dropdown = document.getElementById('navDropdown');
        document.getElementById('menuToggleBtn').addEventListener('click', e => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => dropdown.classList.remove('show'));
        document.getElementById('logoutBtn').addEventListener('click', e => {
            e.preventDefault();
            dropdown.classList.remove('show');
            openExitModal();
        });

        // Close modals on backdrop click
        document.querySelectorAll('.modal_overlay').forEach(o =>
            o.addEventListener('click', e => {
                if (e.target === o) o.classList.remove('active');
            }));

        // Toast
        function showToast(msg, isError) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.toggle('toast-error', !!isError);
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function updatePassword() {
            const cur = currentPass.value,
                n = newPass.value,
                c = confirmPass.value;

            if (!cur || !n || !c) return showToast('Please fill in all fields.', true);
            if (n !== c) return showToast('New passwords do not match.', true);

            // Client-side check for instant feedback — the real enforcement happens
            // server-side in change-password.php, since this check alone can be bypassed.
            const hasUppercase = /[A-Z]/.test(n);
            const hasNumber = /[0-9]/.test(n);
            const hasSymbol = /[^A-Za-z0-9]/.test(n);
            if (n.length < 8 || !hasUppercase || !hasNumber || !hasSymbol) {
                return showToast('Password must be 8+ characters with an uppercase letter, a number, and a symbol.', true);
            }

            // Adjust this path if your folder structure differs —
            // this assumes public/Admin_Setting_Page.php and src/change-password.php as siblings.
            fetch('../src/change-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'current_password=' + encodeURIComponent(cur) +
                        '&new_password=' + encodeURIComponent(n) +
                        '&confirm_password=' + encodeURIComponent(c)
                })
                .then(res => res.json())
                .then(data => {
                    showToast(data.message, !data.success);
                    if (data.success) {
                        currentPass.value = '';
                        newPass.value = '';
                        confirmPass.value = '';
                    }
                })
                .catch(() => showToast('Could not reach the server. Please try again.', true));
        }

        // ── DATABASE BACKUP ──
        let lastBackup = null;

        function fmtDate(raw) {
            const d = new Date(String(raw).replace(' ', 'T'));
            return isNaN(d) ? raw : d.toLocaleString('en-US',
                { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        function loadBackupStatus() {
            fetch('../src/get-backup-status.php')
                .then(r => r.json())
                .then(d => {
                    if (d.error) { document.getElementById('lastBackupText').textContent = 'Unavailable'; return showToast(d.error, true); }
                    lastBackup = d.last;
                    const text = document.getElementById('lastBackupText');
                    const dot = document.getElementById('backupDot');
                    if (d.last) {
                        text.textContent = fmtDate(d.last.created_at) + (d.last.type === 'auto' ? ' (auto)' : '');
                        dot.classList.toggle('warn', !d.last.file_ok);
                        if (!d.last.file_ok) showToast('The last backup file is missing from the server.', true);
                    } else {
                        text.textContent = 'No backup on record';
                        dot.classList.add('warn');
                    }
                    document.getElementById('backupInterval').value = String(d.interval_days);
                    const list = document.getElementById('backupList');
                    list.innerHTML = '';
                    if (!d.recent.length) list.innerHTML = '<li>No backups yet</li>';
                    d.recent.forEach(b => {
                        const li = document.createElement('li');
                        const left = document.createElement('span');
                        left.textContent = fmtDate(b.created_at);
                        const right = document.createElement('span');
                        right.innerHTML = '<span class="type_pill ' + (b.type === 'auto' ? 'auto' : '') + '">' + b.type + '</span> '
                            + Math.max(1, Math.round(b.size / 1024)) + ' KB';
                        li.append(left, right);
                        list.appendChild(li);
                    });
                    if (d.auto_ran) showToast('An automatic backup was just created.');
                })
                .catch(() => showToast('Could not reach the server.', true));
        }

        function backupAction(params, btnId) {
            const btn = btnId ? document.getElementById(btnId) : null;
            if (btn) btn.disabled = true;
            return fetch('../src/backup-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(params).toString()
            })
                .then(r => r.json())
                .catch(() => ({ success: false, message: 'Could not reach the server. Please try again.' }))
                .finally(() => { if (btn) btn.disabled = false; });
        }

        function saveInterval() {
            backupAction({ action: 'save_interval', interval_days: document.getElementById('backupInterval').value }, 'saveIntervalBtn')
                .then(d => showToast(d.message, !d.success));
        }

        function backupNow() {
            showToast('Creating backup…');
            backupAction({ action: 'create' }, 'backupNowBtn').then(d => {
                showToast(d.message, !d.success);
                if (d.success) loadBackupStatus();
            });
        }

        function openRestore() {
            if (!lastBackup) return showToast('There is no backup to restore yet.', true);
            document.getElementById('restoreDate').textContent = fmtDate(lastBackup.created_at);
            document.getElementById('restorePassword').value = '';
            openModal('restoreModal');
        }

        function confirmRestore() {
            const pw = document.getElementById('restorePassword').value;
            if (!pw) return showToast('Enter your password to confirm.', true);
            backupAction({ action: 'restore', password: pw }, 'restoreConfirmBtn').then(d => {
                if (!d.success) return showToast(d.message, true);
                closeModal('restoreModal');
                showToast(d.message);
                setTimeout(() => { window.location.href = 'Admin_Log_In.html'; }, 1800);
            });
        }

        loadBackupStatus();

    </script>
</body>

</html>