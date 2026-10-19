<?php
// Citizen-only page: only a signed-in citizen can submit a concern (it is linked to their account).
// Staff and super admins are sent to their own home page; everyone else goes to Sign_In.html.
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
    <title>Pasig Tugon! - Citizen Concern Station</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

    <!-- jQuery and jQuery UI base -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://code.jquery.com/ui/1.14.2/jquery-ui.js"></script>

    <link rel="icon" type="image/x-icon" href="PasigTugon_Logo.png">

    <style>
        :root {
            --primary-blue: #0b45ac;
            --primary-blue-hover: #083483;
            --accent-cyan: #38bdf8;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --bg-gradient-start: #f8fafc;
            --bg-gradient-end: #e2e8f0;
            --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            --transition: all 0.25s ease-in-out;
            --success-green: #10b981;
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
            background: linear-gradient(180deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }

        .main-container {
            width: 100%;
            max-width: 800px;
            padding: 40px 24px 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }

        /* TOP NAVIGATION: tabs shared with the Citizen Dashboard */
        .top-nav {
            width: 100%;
            align-self: stretch;
            position: relative;
            z-index: 50;
        }

        .top-nav .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 40px;
            background: #0b3a85;
            color: #fff;
        }

        .top-nav .nav-brand {
            font-weight: 900;
            font-size: 1.5rem;
            letter-spacing: -0.02em;
            text-transform: uppercase;
        }

        .top-nav .nav-brand span {
            color: var(--accent-cyan);
        }

        .top-nav .menu-container {
            position: relative;
        }

        .top-nav .menu-toggle-btn {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .top-nav .menu-toggle-btn:hover {
            background: #2563eb;
        }

        .top-nav .dropdown-menu {
            position: absolute;
            right: 0;
            top: 52px;
            width: 190px;
            background: #0f172a;
            border-radius: 14px;
            padding: 10px;
            display: none;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .top-nav .dropdown-menu.show {
            display: block;
        }

        .top-nav .dropdown-item {
            display: block;
            color: #f87171;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .top-nav .dropdown-item:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
        }

        .top-nav .tab-bar {
            display: flex;
            gap: 8px;
            padding: 0 40px;
            background: #082c68;
            overflow-x: auto;
        }

        .top-nav .tab-link {
            padding: 16px 26px;
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

        .top-nav .tab-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }

        .top-nav .tab-link.active {
            color: var(--accent-cyan);
            background: rgba(56, 189, 248, 0.1);
            border-bottom-color: var(--accent-cyan);
        }

        @media (max-width: 600px) {
            .top-nav .navbar {
                padding: 14px 20px;
            }

            .top-nav .tab-bar {
                padding: 0 12px;
            }

            .top-nav .tab-link {
                padding: 14px 16px;
            }
        }

        /* HEADER & LOGO */
        .city-seal {
            width: 100px;
            /* Enlarges the width to 600px */
            height: auto;
            object-fit: contain;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }

        .brand-title {
            font-weight: 900;
            font-size: clamp(3rem, 6vw, 4.2rem);
            letter-spacing: -0.02em;
            text-transform: uppercase;
            line-height: 1;
            text-align: center;
            margin-bottom: 8px;
        }

        .brand-title .highlight {
            color: var(--accent-cyan);
            text-shadow: 0 2px 10px rgba(56, 189, 248, 0.3);
        }

        .brand-title .dark-text {
            color: var(--text-dark);
        }

        .sub-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 36px;
        }

        /* FORM CONTAINER */
        .concern-card {
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* DROPDOWN */
        .select-wrapper {
            position: relative;
            width: 100%;
        }

        .custom-select {
            width: 100%;
            padding: 14px 20px;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            background-color: var(--primary-blue);
            border: none;
            border-radius: 12px;
            appearance: none;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(11, 69, 172, 0.25);
        }

        .custom-select:hover,
        .custom-select:focus {
            background-color: var(--primary-blue-hover);
            outline: none;
            box-shadow: 0 6px 16px rgba(11, 69, 172, 0.35);
        }

        .select-arrow {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 7px solid #ffffff;
            pointer-events: none;
        }

        /* TEXTAREA */
        .textarea-container {
            position: relative;
        }

        .concern-input {
            width: 100%;
            min-height: 180px;
            padding: 18px 20px;
            font-family: inherit;
            font-size: 1rem;
            color: var(--text-dark);
            background-color: #f8fafc;
            border: 2px solid var(--primary-blue);
            border-radius: 14px;
            resize: vertical;
            outline: none;
            transition: var(--transition);
        }

        .concern-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .concern-input:focus {
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(11, 69, 172, 0.15);
        }

        /* BACK BUTTON */
        .btn-back {
            display: block;
            width: 100%;
            padding: 14px;
            margin-top: 4px;
            text-align: center;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--primary-blue);
            background: transparent;
            border: 2px solid var(--primary-blue);
            border-radius: 30px;
            transition: var(--transition);
        }

        .btn-back:hover {
            background: rgba(11, 69, 172, 0.08);
        }

        /* CAPTCHA */
        .captcha-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .captcha-row img {
            border-radius: 12px;
            border: 2px solid var(--primary-blue);
            max-width: 100%;
            height: auto;
        }

        .captcha-refresh {
            background: var(--primary-blue);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 1.3rem;
            cursor: pointer;
            flex-shrink: 0;
        }

        .captcha-input {
            width: 100%;
            padding: 14px 18px;
            font-family: inherit;
            font-size: 1rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--text-dark);
            background-color: #f8fafc;
            border: 2px solid var(--primary-blue);
            border-radius: 14px;
            outline: none;
        }

        /* SUBMIT BUTTON */
        .btn-submit {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background-color: var(--primary-blue);
            border: none;
            border-radius: 30px;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 8px 20px rgba(11, 69, 172, 0.3);
            margin-top: 8px;
        }

        .btn-submit:hover {
            background-color: var(--primary-blue-hover);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(11, 69, 172, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* SKYLINE VECTOR BACKGROUND */
        .skyline-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 180px;
            background-color: var(--primary-blue);
            mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 200" preserveAspectRatio="none"><path fill="%23000" d="M0,200 L0,150 L20,150 L20,130 L40,130 L40,170 L60,170 L60,110 L90,110 L90,200 L110,200 L110,60 L130,60 L130,140 L160,140 L160,120 L190,120 L190,180 L210,180 L210,100 L240,100 L240,200 L270,200 L270,160 L300,160 L300,130 L320,130 L320,190 L360,190 L360,150 L390,150 L390,120 L420,120 L420,170 L460,170 L460,140 L490,140 L490,200 L530,200 L530,110 L560,110 L560,160 L600,160 L600,130 L630,130 L630,200 L670,200 L670,100 L700,100 L700,170 L740,170 L740,140 L780,140 L780,180 L820,180 L820,120 L860,120 L860,160 L890,160 L890,190 L930,190 L930,140 L970,140 L970,110 L1010,110 L1010,170 L1050,170 L1050,130 L1090,130 L1090,180 L1130,180 L1130,150 L1170,150 L1170,190 L1200,190 L1200,200 Z"/></svg>');
            -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 200" preserveAspectRatio="none"><path fill="%23000" d="M0,200 L0,150 L20,150 L20,130 L40,130 L40,170 L60,170 L60,110 L90,110 L90,200 L110,200 L110,60 L130,60 L130,140 L160,140 L160,120 L190,120 L190,180 L210,180 L210,100 L240,100 L240,200 L270,200 L270,160 L300,160 L300,130 L320,130 L320,190 L360,190 L360,150 L390,150 L390,120 L420,120 L420,170 L460,170 L460,140 L490,140 L490,200 L530,200 L530,110 L560,110 L560,160 L600,160 L600,130 L630,130 L630,200 L670,200 L670,100 L700,100 L700,170 L740,170 L740,140 L780,140 L780,180 L820,180 L820,120 L860,120 L860,160 L890,160 L890,190 L930,190 L930,140 L970,140 L970,110 L1010,110 L1010,170 L1050,170 L1050,130 L1090,130 L1090,180 L1130,180 L1130,150 L1170,150 L1170,190 L1200,190 L1200,200 Z"/></svg>');
            mask-size: cover;
            -webkit-mask-size: cover;
            mask-repeat: no-repeat;
            -webkit-mask-repeat: no-repeat;
            z-index: 1;
        }


        /* JQUERY UI DIALOG OVERRIDES & CUSTOMIZATION */
        .ui-widget-overlay {
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(4px);
            opacity: 1 !important;
        }

        .ui-dialog {
            border: none !important;
            border-radius: 20px !important;
            padding: 0 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            overflow: hidden;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            background: #ffffff !important;
        }

        /* Dialog Titlebar */
        .ui-dialog .ui-dialog-titlebar {
            background: #ffffff !important;
            border: none !important;
            padding: 24px 24px 0 24px !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ui-dialog .ui-dialog-title {
            font-weight: 700 !important;
            font-size: 1.1rem !important;
            color: var(--text-dark) !important;
            margin: 0 !important;
            float: none !important;
            width: 100%;
            text-align: center;
        }

        .ui-dialog .ui-dialog-titlebar-close {
            border: none !important;
            background: #f1f5f9 !important;
            border-radius: 50% !important;
            width: 32px !important;
            height: 32px !important;
            position: absolute !important;
            right: 16px !important;
            top: 16px !important;
            margin: 0 !important;
            cursor: pointer;
            transition: var(--transition);
        }

        .ui-dialog .ui-dialog-titlebar-close:hover {
            background: #e2e8f0 !important;
        }

        /* Dialog Content Container */
        .custom-dialog-content {
            padding: 16px 24px 24px 24px !important;
            text-align: center;
        }

        .dialog-icon-wrapper {
            width: 64px;
            height: 64px;
            background-color: #ecfdf5;
            color: var(--success-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .dialog-icon-wrapper-unnable {
            width: 64px;
            height: 64px;
            background-color: #ecfdf5;
            color: red;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .dialog-icon-wrapper svg {
            width: 36px;
            height: 36px;
        }

        .dialog-title {
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .dialog-description {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Dialog Button Bar */
        .ui-dialog .ui-dialog-buttonpane {
            background: #ffffff !important;
            border: none !important;
            padding: 0 24px 24px 24px !important;
            margin: 0 !important;
        }

        .ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset {
            float: none !important;
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .ui-dialog .ui-dialog-buttonpane button {
            width: 100% !important;
            padding: 12px 24px !important;
            font-family: inherit !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            color: #ffffff !important;
            background-color: var(--primary-blue) !important;
            border: none !important;
            border-radius: 12px !important;
            cursor: pointer;
            transition: var(--transition) !important;
            margin: 0 !important;
            box-shadow: 0 4px 12px rgba(11, 69, 172, 0.2) !important;
        }

        .ui-dialog .ui-dialog-buttonpane button:hover {
            background-color: var(--primary-blue-hover) !important;
        }

        @media (max-width: 600px) {
            .concern-card {
                padding: 24px;
            }

            .sub-title {
                font-size: 0.95rem;
            }

            .skyline-footer {
                height: 120px;
            }
        }
    </style>
</head>

<body>

    <div class="top-nav">
        <nav class="navbar">
            <div class="nav-brand"><span>PASIG</span> TUGON!</div>
            <div class="menu-container">
                <button class="menu-toggle-btn" id="menuToggleBtn" aria-label="Toggle Menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <div class="dropdown-menu" id="navDropdown">
                    <a href="../src/logout.php" class="dropdown-item">Log Out</a>
                </div>
            </div>
        </nav>
        <div class="tab-bar">
            <a href="Citizen_Dashboard_Page.php" class="tab-link">My Dashboard</a>
            <a href="Citizen_Concern_Station.php" class="tab-link active">Concern Station</a>
        </div>
    </div>

    <!-- SINGLE MODAL DIALOG ELEMENT -->
    <div id="submission-dialog" title="Pasig Tugon!" style="display:none;">
        <div class="custom-dialog-content">
            <div class="dialog-icon-wrapper">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"></path>
                </svg>
            </div>
            <h2 class="dialog-title">Concern Submitted!</h2>
            <p class="dialog-description" id="submission-text">Thank you for reporting your issue. Your submission has been routed to your
                designated barangay office.</p>
        </div>
    </div>

    <div id="submission-dialog-fail" title="Pasig Tugon!" style="display:none;">
        <div class="custom-dialog-content">
            <div class="dialog-icon-wrapper-unnable error">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h2 class="dialog-title">Unable to Submit</h2>
            <p class="dialog-description">Please make sure you have selected a barangay and written your concern before
                submitting.</p>
        </div>
    </div>

    <div id="submission-dialog-short" title="Pasig Tugon!" style="display:none;">
        <div class="custom-dialog-content">
            <div class="dialog-icon-wrapper-unnable error">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h2 class="dialog-title">Unable to Submit</h2>
            <p class="dialog-description">Please make sure your concern had complete before
                submitting.</p>
        </div>
    </div>

    <main class="main-container">
        <!-- City Seal Logo -->
        <img src="pasig_logo.webp" alt="Pasig City Seal" class="city-seal">

        <!-- Title Section -->
        <h1 class="brand-title">
            <span class="highlight">PASIG</span> <span class="dark-text">TUGON!</span>
        </h1>
        <p class="sub-title">Citizen Concern Station</p>

        <!-- Form Card Container -->
        <div class="concern-card">
            <form id="concern-form" class="form-group" action="#" method="POST">

                <!-- Barangay Selector -->
                <div class="select-wrapper">
                    <select class="custom-select" required>
                        <option value="" disabled selected>Select your barangay</option>
                        <option value="bagong-ilog">Bagong Ilog</option>
                        <option value="bambang">Bambang</option>
                        <option value="caniogan">Caniogan</option>
                        <option value="kapitolyo">Kapitolyo</option>
                        <option value="malinao">Malinao</option>
                        <option value="oranbo">Oranbo</option>
                        <option value="pinagbuhatan">Pinagbuhatan</option>
                        <option value="san-antonio">San Antonio</option>
                        <option value="san-joaquin">San Joaquin</option>
                        <option value="ugong">Ugong</option>
                    </select>
                    <div class="select-arrow"></div>
                </div>

                <!-- Textarea -->
                <div class="textarea-container">
                    <textarea class="concern-input" placeholder="Write your concern here..." required></textarea>
                </div>

                <!-- Honeypot: hidden from people, bots tend to fill it in -->
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <label>Website <input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <!-- CAPTCHA -->
                <div class="captcha-row">
                    <img id="captcha-img" src="../src/captcha.php" alt="Verification code" width="240" height="72">
                    <button type="button" id="captcha-refresh" class="captcha-refresh" aria-label="Get a new code">&#8635;</button>
                </div>
                <input type="text" id="captcha-input" class="captcha-input" maxlength="5"
                    placeholder="Type the code shown above" autocomplete="off" required>

                <!-- Submit Action Button -->
                <button type="submit" class="btn-submit">Submit</button>
                <a href="Citizen_Dashboard_Page.php" class="btn-back">Back to Dashboard</a>
            </form>
        </div>
    </main>

    <!-- Vector Skyline Bottom Decoration -->
    <div class="skyline-footer"></div>

    <script>
        $(document).ready(function () {
            // 1. Initialize all dialogs with a simple helper function
            function initDialog(selector) {
                $(selector).dialog({
                    autoOpen: false,
                    modal: true,
                    resizable: false,
                    draggable: false,
                    width: 420,
                    show: { effect: "fade", duration: 200 },
                    hide: { effect: "fadeOut", duration: 150 },
                    buttons: {
                        "OK": function () {
                            $(this).dialog("close");
                        }
                    }
                });
            }

            // Apply setup to all three dialogs
            initDialog("#submission-dialog");
            initDialog("#submission-dialog-fail");
            initDialog("#submission-dialog-short");

            // 2. Simple Gibberish Detector Function
            function isGibberish(text) {
                if (text.length < 15) return true;

                var tooManyConsonants = /[^aeiou\s\d]{5,}/i.test(text);
                if (tooManyConsonants) return true;

                var mixedNumbersAndLetters = /\d+[a-z]+\d+|[a-z]+\d+[a-z]+/i.test(text);
                if (mixedNumbersAndLetters) return true;

                if (text.length > 20 && !text.includes(" ")) return true;

                return false; // Valid text
            }

            // Only barangays with an approved admin can receive concerns: disable the rest
            $.getJSON("../src/get-barangays.php").done(function (res) {
                var available = res.available || [];
                $(".custom-select option").each(function () {
                    var v = $(this).val();
                    if (v && available.indexOf(v) === -1) {
                        $(this).prop("disabled", true).text($(this).text() + " (not yet available)");
                    }
                });
            });

            // The captcha code is single-use, so get a fresh image after every attempt
            function reloadCaptcha() {
                $("#captcha-img").attr("src", "../src/captcha.php?t=" + Date.now());
                $("#captcha-input").val("");
            }
            $("#captcha-refresh").on("click", reloadCaptcha);

            // Top menu (Log Out)
            $("#menuToggleBtn").on("click", function (e) {
                e.stopPropagation();
                $("#navDropdown").toggleClass("show");
            });
            $(document).on("click", function () { $("#navDropdown").removeClass("show"); });

            $("#concern-form").on("submit", function (e) {
                e.preventDefault();

                var barangay = $(".custom-select").val();
                var concern = $.trim($(".concern-input").val());
                var $form = $(this);
                var $submitBtn = $form.find(".btn-submit");

                if (!barangay) {
                    $("#submission-dialog-fail").dialog("open");
                    return;
                }

                if (isGibberish(concern)) {
                    $("#submission-dialog-short").dialog("open");
                    return;
                }

                var captcha = $.trim($("#captcha-input").val());
                if (!captcha) {
                    $("#submission-dialog-fail .dialog-description").text("Please type the verification code shown in the image.");
                    $("#submission-dialog-fail").dialog("open");
                    return;
                }

                $submitBtn.prop("disabled", true).text("Submitting...");

                // Adjust this path if your folder structure differs —
                // this assumes public/Citizen_Concern_Station.php and src/submit-concern.php as siblings.
                $.ajax({
                    url: "../src/submit-concern.php",
                    method: "POST",
                    data: { barangay: barangay, concern_text: concern, captcha: captcha, website: $("#website").val() },
                    dataType: "json"
                }).done(function (response) {
                    if (response.success) {
                        var text = "Thank you for reporting your issue. Your concern has been routed to your barangay office.";
                        if (response.case_number) {
                            text += " Your case number is #" + response.case_number + ". You can follow its progress in your dashboard.";
                        }
                        $("#submission-text").text(text);
                        $("#submission-dialog").dialog("option", "buttons", {
                            "View My Dashboard": function () { window.location.href = "Citizen_Dashboard_Page.php"; },
                            "Submit Another": function () { $(this).dialog("close"); }
                        });
                        $("#submission-dialog").dialog("open");
                        $form[0].reset();
                    } else {
                        $("#submission-dialog-fail .dialog-description").text(response.message);
                        $("#submission-dialog-fail").dialog("open");
                    }
                }).fail(function (xhr) {
                    // Signed out (or the session expired) while writing the concern
                    if (xhr.status === 401 || xhr.status === 403) {
                        window.location.href = "Sign_In.html";
                        return;
                    }
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ||
                        "Could not reach the server. Please check your connection and try again.";
                    $("#submission-dialog-fail .dialog-description").text(msg);
                    $("#submission-dialog-fail").dialog("open");
                }).always(function () {
                    $submitBtn.prop("disabled", false).text("Submit");
                    reloadCaptcha();
                });
            });

        });
    </script>

</body>

</html>