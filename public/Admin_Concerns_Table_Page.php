<?php
// Session guard — redirects to login if nobody is signed in.
// Must run before any HTML is output, so this block stays at the very top of the file.
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Admin_Log_In.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasig Tugon! - Concerns Collection</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Gantari:ital,wght@0,400;0,600;0,700;0,800;0,900;1,700&display=swap"
        rel="stylesheet">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- JS Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    <link rel="icon" type="image/x-icon" href="PasigTugon_Logo.png">

    <style>
        :root {
            --sky-blue: #38A2EF;
            --deep-navy: #0B4CA8;
            --outer-blue: #0A3C91;
            --table-bg: #6BA3E3;
            --table-header: #083D8F;
            --row-hover: #5A95D6;
            --selected-row: #4A85C7;
            --btn-cancel: #1E3A8A;
            --btn-done: #10B981;
            --btn-delete: #EF4444;
            --modal-blue: #0B3175;
            --primary-blue: #0e48a1;
            --dark-blue: #071d49;
            --accent-cyan: #38bdf8;
            --light-blue-bg: rgba(224, 242, 254, 0.85);
            --card-glass: rgba(15, 23, 42, 0.65);
            --card-border: rgba(255, 255, 255, 0.15);
            --white: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --badge-blue: #2563eb;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            --font-family: 'Gantari', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background: url('background.png') center/cover no-repeat fixed;
            color: var(--white);
            display: flex;
            flex-direction: column;
        }

        .ui-dialog {
            padding: 0;
            border-radius: 16px;
            overflow: hidden;
            z-index: 2000 !important;
            font-family: 'Inter', system-ui, sans-serif;
        }

        .ui-dialog .ui-dialog-titlebar {
            padding: 12px 16px;
            background: #0e48a1;
            color: #fff;
            border: none;
            border-radius: 0;
        }

        .ui-dialog .ui-dialog-content {
            padding: 12px 16px;
            color: #0f172a;
        }

        .ui-dialog .ui-dialog-buttonpane {
            padding: 8px 12px;
            margin: 0;
        }

        .ui-dialog .ui-dialog-buttonpane button {
            padding: 6px 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        .ui-widget-overlay {
            z-index: 1999 !important;
        }

        /* NAVBAR */
        .navbar,
        .navbar * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

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

        .brand-title .white-text {
            color: var(--white);
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
            opacity: 0;
            transform: translateY(-10px);
            transition: var(--transition);
        }

        .dropdown-menu.show {
            display: flex;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-item {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
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
            cursor: pointer;
        }

        .dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        /* MAIN LAYOUT */
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
            letter-spacing: 0.01em;
            color: #FFFFFF;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .header-divider {
            border: none;
            border-bottom: 2px solid var(--accent-cyan);
            margin: 1px 0 0.8rem 0;
            opacity: 0.8;
        }

        .card-subtitle {
            font-size: 0.8rem;
            font-weight: 800;
            color: #93C5FD;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1px;
        }

        /* INNER CONTENT BOX */
        .inner-box {
            background: rgba(107, 163, 227, 0.95);
            border-radius: 24px;
            padding: 1.5rem 1.8rem 1.8rem 1.8rem;
            color: #0A3C91;
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.2);
        }

        /* CONTROLS BAR */
        .controls-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            min-height: 48px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-dropdown-container {
            position: relative;
        }

        .filter-dropdown-btn {
            background-color: #38A2EF;
            color: #072B60;
            font-weight: 800;
            font-size: 1.05rem;
            padding: 10px 22px;
            border-radius: 50px;
            border: none;
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
        }

        .filter-dropdown-btn:hover {
            background-color: #60A5FA;
            transform: translateY(-1px);
        }

        .filter-menu {
            position: absolute;
            top: 115%;
            left: 0;
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            width: 190px;
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 40;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .filter-menu.show {
            display: flex;
        }

        .filter-option {
            padding: 12px 18px;
            color: #0A3C91;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.15s;
        }

        .filter-option:hover {
            background-color: #EFF6FF;
        }

        /* Action Buttons Container */
        .action-buttons {
            display: none;
            gap: 12px;
            align-items: center;
            animation: fadeIn 0.2s ease-in-out;
        }

        .btn-pill {
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 800;
            color: #FFFFFF;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-pill:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .btn-pill:active {
            transform: translateY(0);
        }

        .btn-cancel {
            background-color: var(--btn-cancel);
        }

        .btn-done {
            background-color: var(--btn-done);
        }

        .btn-delete {
            background-color: var(--btn-delete);
        }

        /* TABLE STYLES */
        .table-container {
            border: 2px solid #083D8F;
            border-radius: 16px;
            overflow: hidden;
            background-color: var(--table-bg);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        /* Only this inner region scrolls, so the header row stays pinned in view */
        .table-scroll {
            max-height: 560px;
            overflow-y: auto;
        }

        .concerns-table thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .btn-restore {
            background-color: #10B981;
        }

        .concerns-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .concerns-table thead tr {
            background-color: var(--table-header);
            color: #FFFFFF;
        }

        .concerns-table th {
            padding: 16px 20px;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .concerns-table th:nth-child(1) {
            width: 5%;
            text-align: center;
        }

        .concerns-table th:nth-child(2) {
            width: 15%;
        }

        .concerns-table th:nth-child(3) {
            width: 15%;
        }

        .concerns-table th:nth-child(4) {
            width: 65%;
        }

        .concerns-table tbody tr {
            border-bottom: 1px solid rgba(8, 61, 143, 0.2);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .concerns-table tbody tr:last-child {
            border-bottom: none;
        }

        .concerns-table tbody tr:hover {
            background-color: var(--row-hover);
        }

        .concerns-table tbody tr.selected {
            background-color: var(--selected-row);
        }

        .concerns-table td {
            padding: 14px 20px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #052350;
            vertical-align: middle;
        }

        .checkbox-cell {
            text-align: center;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border-radius: 6px;
            border: 2px solid #083D8F;
            background: #FFFFFF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .concerns-table tr.selected .custom-checkbox {
            background: #083D8F;
            border-color: #083D8F;
        }

        .concerns-table tr.selected .custom-checkbox::after {
            content: '✓';
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 900;
        }

        .status-badge {
            background-color: #93C5FD;
            color: #1E3A8A;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 800;
            display: inline-block;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .status-badge.archived {
            background-color: #CBD5E1;
            color: #334155;
        }

        .concern-cell-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .concern-text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 580px;
        }

        .btn-view-spec {
            background-color: #083D8F;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.85rem;
            padding: 6px 18px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-view-spec:hover {
            background-color: #031B42;
            transform: scale(1.05);
        }

        /* TABLE FOOTER */
        .table-footer-info {
            margin-top: 1.2rem;
            font-size: 0.8rem;
            font-weight: 800;
            color: #083D8F;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* MODAL STYLES */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(5, 15, 35, 0.75);
            backdrop-filter: blur(8px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.2s ease-out;
        }

        .modal-card {
            background-color: var(--modal-blue);
            width: 100%;
            max-width: 560px;
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
            color: #FFFFFF;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            font-size: 1.1rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .modal-close-btn:hover {
            background-color: #EF4444;
            transform: scale(1.1);
        }

        .modal-title {
            font-size: 1.8rem;
            font-weight: 900;
            color: #FFFFFF;
            letter-spacing: 0.02em;
            text-align: center;
            margin-bottom: 1.2rem;
            text-transform: uppercase;
            border-bottom: 2px solid rgba(56, 162, 239, 0.4);
            padding-bottom: 12px;
        }

        .modal-date-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0 4px;
        }

        .modal-date-label,
        .modal-date-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: #93C5FD;
        }

        .modal-text-content {
            background-color: #E0F2FE;
            color: #032B69;
            border-radius: 18px;
            padding: 1.4rem;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.6;
            min-height: 160px;
            max-height: 300px;
            overflow-y: auto;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.97);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* RESPONSIVE STYLES */
        @media (max-width: 900px) {
            .outer-card {
                padding: 1.5rem;
            }

            .inner-box {
                padding: 1.2rem;
            }

            .card-header-title {
                font-size: 1.7rem;
            }

            .controls-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: space-between;
                width: 100%;
            }

            .btn-pill {
                flex: 1;
                padding: 10px 12px;
                font-size: 0.85rem;
            }

            .concern-text-truncate {
                max-width: 200px;
            }

            .concerns-table th,
            .concerns-table td {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
        }

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

        /* Modal Window Container */
        .modal_panel {
            width: 440px;
            max-width: 90vw;
            background: rgba(15, 23, 42, 0.88);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--card-border, rgba(255, 255, 255, 0.15));
            border-radius: 20px;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.08) inset;
            transform: scale(0.95) translateY(10px);
            transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            animation: zoomIn 0.2s ease;
            color: var(--white, #ffffff);
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
            transform: scale(1) translateY(0);
        }

        /* Modal Header Options */
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
            border-bottom: 1px solid rgba(248, 113, 113, 0.3);
        }

        .modal_header_bar.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.9), rgba(6, 95, 70, 0.95));
            color: #ffffff;
            border-bottom: 1px solid rgba(52, 211, 153, 0.3);
        }

        .modal_header_title {
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 0;
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
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .modal_close_btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transform: rotate(90deg);
        }

        /* Modal Body Text */
        .modal_body {
            padding: 28px 24px;
            text-align: center;
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Modal Footer Action Row */
        .modal_footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }

        /* Button Variants */
        .swiss_btn_modal {
            flex: 1;
            padding: 12px 18px;
            font-family: inherit;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
            border: 1px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .swiss_btn_modal:active {
            transform: scale(0.97);
        }

        .swiss_btn_modal.cancel {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .swiss_btn_modal.cancel:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .swiss_btn_modal.confirm {
            background: var(--badge-blue, #2563eb);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .swiss_btn_modal.confirm:hover {
            background: #3b82f6;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
            transform: translateY(-1px);
        }

        .swiss_btn_modal.confirm_danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
        }

        .swiss_btn_modal.confirm_danger:hover {
            background: linear-gradient(135deg, #f87171, #ef4444);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <!-- LOGOUT MODAL -->
    <div id="logoutModal" class="modal_overlay">
        <div class="modal_panel">
            <div class="modal_header_bar danger">
                <h2 class="modal_header_title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z">
                        </path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    End Session
                </h2>
                <button class="modal_close_btn" onclick="closeModal('logoutModal')"
                    aria-label="Close Modal">&times;</button>
            </div>
            <div class="modal_body">
                Are you sure you want to securely log out of the admin portal?
            </div>
            <div class="modal_footer">
                <button class="swiss_btn_modal cancel" onclick="closeModal('logoutModal')">Cancel</button>
                <button class="swiss_btn_modal confirm_danger" onclick="executeLogout()">Log Out</button>
            </div>
        </div>
    </div>

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
                <a href="Admin_Dashboard_Page.php" class="dropdown-item">Dashboard</a>
                <a href="Admin_Concerns_Table_Page.php" class="dropdown-item">Concerns</a>
                <a href="Admin_Setting_Page.php" class="dropdown-item">Settings</a>
                <a href="#" class="dropdown-item logout" id="logoutBtn">Log Out</a>
            </div>
        </div>
    </nav>

    <main class="main-wrapper">
        <div class="outer-card">
            <h1 class="card-header-title">Concerns Collection</h1>
            <p class="card-subtitle">This table shows every concern in the database</p>
            <hr class="header-divider">
            <div class="inner-box">
                <div class="controls-bar">
                    <!-- Filter Dropdown Container -->
                    <div class="filter-dropdown-container">
                        <button class="filter-dropdown-btn" id="filterBtn">
                            <span id="currentFilterText">Ongoing</span>
                            <svg width="18" height="12" viewBox="0 0 22 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 2L11 11L20 2" stroke="#072B60" stroke-width="3.5" stroke-linecap="round" />
                            </svg>
                        </button>
                        <div class="filter-menu" id="filterMenu">
                            <div class="filter-option" onclick="setFilter('ongoing')">Ongoing</div>
                            <div class="filter-option" onclick="setFilter('archived')">Archived</div>
                            <div class="filter-option" onclick="setFilter('all')">All Concerns</div>
                        </div>
                    </div>

                    <!-- Action Buttons - Displayed ONLY when items are selected -->
                    <div class="action-buttons" id="actionButtons">
                        <button class="btn-pill btn-cancel" onclick="clearSelections()">Cancel</button>
                        <button class="btn-pill btn-done" id="archiveRestoreBtn" onclick="archiveOrRestoreSelected()">Archive</button>
                    </div>
                </div>

                <div class="table-container">
                    <div class="table-scroll">
                        <table class="concerns-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Concerns</th>
                                </tr>
                            </thead>
                            <tbody id="concernsTableBody">
                                <!-- Table rows injected via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-footer-info" id="tableFooterInfo">
                    SHOWING 0 OF 0 ENTRIES
                </div>
            </div>
        </div>
    </main>

    <!-- Concern Detail Modal -->
    <div class="modal-overlay" id="concernModal">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeModal()">✕</button>
            <h2 class="modal-title">Concern Card</h2>

            <div class="modal-date-row">
                <span class="modal-date-label">Date:</span>
                <span class="modal-date-value" id="modalDate">--/--/----</span>
            </div>

            <div class="modal-text-content" id="modalContent">
                Select a concern to view details.
            </div>
        </div>
    </div>

    <script>
        // Populated from the server via loadConcerns() — no more hardcoded sample data.
        let concernsData = [];
        let activeFilter = 'ongoing';

        // Escapes text pulled from the database before injecting it as HTML,
        // so a concern containing HTML/script tags can't execute as markup (stored XSS).
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        $(document).ready(function () {
            // Dropdown toggles
            $('#menuToggleBtn').on('click', function (e) {
                e.stopPropagation();
                $('#navDropdown').toggleClass('show');
            });

            $('#filterBtn').on('click', function (e) {
                e.stopPropagation();
                $('#filterMenu').toggleClass('show');
            });

            // Close dropdowns when clicking outside
            $(document).on('click', function () {
                $('#navDropdown, #filterMenu').removeClass('show');
            });

            // Table row click (toggle selection)
            $('#concernsTableBody').on('click', 'tr[data-id]', function (e) {
                if ($(e.target).hasClass('btn-view-spec')) return;
                const id = parseInt($(this).data('id'));
                const item = concernsData.find(c => c.id === id);
                if (item) {
                    item.selected = !item.selected;
                    renderTable();
                }
            });

            // "View" button click (open modal)
            $('#concernsTableBody').on('click', '.btn-view-spec', function (e) {
                e.stopPropagation();
                const id = parseInt($(this).data('id'));
                const item = concernsData.find(c => c.id === id);
                if (item) {
                    $('#modalDate').text(item.date);
                    $('#modalContent').text(item.text);
                    $('#concernModal').addClass('active');
                }
            });

            loadConcerns();
        });

        // Fetch concerns from the backend for the current filter.
        // Adjust this path if your folder structure differs —
        // this assumes public/Admin_Concerns_Table_Page.php and src/get-concerns.php as siblings.
        function loadConcerns() {
            $('#concernsTableBody').html(
                '<tr><td colspan="4" style="text-align:center;padding:40px;font-weight:800;color:#083D8F;">LOADING...</td></tr>'
            );

            $.ajax({
                url: '../src/get-concerns.php',
                method: 'GET',
                data: { filter: activeFilter },
                dataType: 'json'
            }).done(function (response) {
                concernsData = (response.concerns || []).map(c => Object.assign({ selected: false }, c));
                renderTable();
            }).fail(function () {
                $('#concernsTableBody').html(
                    '<tr><td colspan="4" style="text-align:center;padding:40px;font-weight:800;color:#083D8F;">COULD NOT LOAD CONCERNS</td></tr>'
                );
            });
        }

        // Set Filter
        function setFilter(filter) {
            activeFilter = filter;
            const labels = { ongoing: 'Ongoing', archived: 'Archived', all: 'All Concerns' };
            $('#currentFilterText').text(labels[filter] || 'Filter');
            loadConcerns();
        }

        function closeModal(id = 'concernModal') {
            document.getElementById(id).classList.remove('active');
        }

        // Action Handlers
        function clearSelections() {
            concernsData.forEach(c => c.selected = false);
            renderTable();
        }

        // Archives or restores whichever concerns are currently selected, depending
        // on the button's current mode (set in renderTable() based on selection status).
        function archiveOrRestoreSelected() {
            const selectedIds = concernsData.filter(c => c.selected).map(c => c.id);
            if (selectedIds.length === 0) return;

            const action = $('#archiveRestoreBtn').data('mode') === 'restore' ? 'restore' : 'archive';

            $.ajax({
                url: '../src/update-concern-status.php',
                method: 'POST',
                data: { ids: selectedIds, action: action },
                dataType: 'json'
            }).done(function (response) {
                if (response.success) {
                    loadConcerns();
                } else {
                    alert(response.message || 'Could not update the selected concerns.');
                }
            }).fail(function () {
                alert('Could not reach the server. Please try again.');
            });
        }

        // Render Table Function
        function renderTable() {
            const $tableBody = $('#concernsTableBody').empty();

            // Count total selected items and figure out whether the action button
            // should say "Archive" (gray) or "Restore" (green) based on what's selected.
            const selectedItems = concernsData.filter(c => c.selected);
            const selectedCount = selectedItems.length;

            if (selectedCount > 0) {
                $('#actionButtons').css('display', 'flex');
                const allArchived = selectedItems.every(c => c.status === 'archived');
                const $btn = $('#archiveRestoreBtn');
                if (allArchived) {
                    $btn.text('Restore').removeClass('btn-done').addClass('btn-restore').data('mode', 'restore');
                } else {
                    $btn.text('Archive').removeClass('btn-restore').addClass('btn-done').data('mode', 'archive');
                }
            } else {
                $('#actionButtons').css('display', 'none');
            }

            if (concernsData.length === 0) {
                $tableBody.append(`
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; font-weight: 800; color: #083D8F;">
                            NO CONCERNS FOUND
                        </td>
                    </tr>
                `);
                $('#tableFooterInfo').text('SHOWING 0 OF 0 ENTRIES');
            } else {
                const rows = concernsData.map(item => {
                    const showViewButton = item.selected && selectedCount === 1;
                    const badgeClass = item.status === 'archived' ? 'archived' : '';
                    const statusLabel = item.status === 'archived' ? 'Archived' : 'Ongoing';

                    return `
                        <tr class="${item.selected ? 'selected' : ''}" data-id="${item.id}">
                            <td class="checkbox-cell">
                                <div class="custom-checkbox"></div>
                            </td>
                            <td>${escapeHtml(item.date)}</td>
                            <td><span class="status-badge ${badgeClass}">${statusLabel}</span></td>
                            <td>
                                <div class="concern-cell-wrapper">
                                    <span class="concern-text-truncate">${escapeHtml(item.text)}</span>
                                    ${showViewButton ? `<button class="btn-view-spec" data-id="${item.id}">View</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                });

                $tableBody.append(rows.join(''));
                $('#tableFooterInfo').text(`SHOWING ${concernsData.length} OF ${concernsData.length} ENTRIES`);
            }
        }

        // Modal Handlers
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function executeLogout() {
            // logout.php destroys the PHP session, then redirects to the login page itself
            window.location.href = '../src/logout.php';
        }

        document.getElementById('logoutBtn').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('navDropdown').classList.remove('show');
            openModal('logoutModal');
        });

        // Close modal when clicking backdrop
        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal('logoutModal');
            }
        });

        document.getElementById('concernModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal('concernModal');
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal('concernModal');
                closeModal('logoutModal');
            }
        });


    </script>
</body>

</html>