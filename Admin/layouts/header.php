<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?> | Si-TANGKAL Kota Cimahi</title>

    <!-- Favicon -->
    <link href="../assets/img/cimahi.ico" rel="icon">
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Bootstrap 5 CSS -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* =========================================================
           ROOT VARIABLES & BASE
        ========================================================= */
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #064e3b;
            --sidebar-hover: #065f46;
            --sidebar-active: #047857;
            --sidebar-text: rgba(255,255,255,0.85);
            --sidebar-text-muted: rgba(255,255,255,0.50);
            --accent: #059669;
            --accent-light: #d1fae5;
            --page-bg: #f0f4f8;
            --card-bg: #ffffff;
            --text-primary: #1a2332;
            --text-muted: #6c7a8d;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 32px rgba(0,0,0,0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.2s ease;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--page-bg);
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        /* =========================================================
           SIDEBAR LAYOUT
        ========================================================= */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            overflow-y: auto;
            transition: var(--transition);
            scrollbar-width: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        /* =========================================================
           TOPBAR
        ========================================================= */
        .topbar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 0 1.5rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1030;
            box-shadow: var(--shadow-sm);
        }

        .topbar .page-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* =========================================================
           MAIN CONTENT AREA
        ========================================================= */
        .main-content {
            padding: 1.75rem;
            flex: 1;
        }

        /* =========================================================
           CARD STYLES
        ========================================================= */
        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            background: var(--card-bg);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        .stat-card {
            border: none;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
            position: relative;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card .card-body { padding: 1.25rem; }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* =========================================================
           TABLE STYLES
        ========================================================= */
        .table { font-size: 0.85rem; }
        .table thead th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }
        .table tbody tr {
            transition: background 0.15s ease;
            cursor: pointer;
        }
        .table tbody tr:hover { background: #f8fafc; }
        .table td { vertical-align: middle; border-color: var(--border-color); }

        /* =========================================================
           BADGE STYLES
        ========================================================= */
        .badge { font-size: 0.72rem; font-weight: 500; padding: 0.35em 0.65em; }

        /* =========================================================
           FORM STYLES
        ========================================================= */
        .form-control, .form-select {
            border-radius: var(--radius-sm);
            border-color: var(--border-color);
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15);
        }
        .form-label { font-weight: 500; font-size: 0.8rem; margin-bottom: 0.35rem; }

        /* =========================================================
           BUTTON OVERRIDES
        ========================================================= */
        .btn { font-size: 0.8rem; font-weight: 500; border-radius: var(--radius-sm); }
        .btn-success { background: var(--accent); border-color: var(--accent); }
        .btn-success:hover { background: #219a38; border-color: #219a38; }
        .btn-sm { padding: 0.3rem 0.65rem; }

        /* =========================================================
           ALERTS
        ========================================================= */
        .alert { border-radius: var(--radius-md); border: none; font-size: 0.85rem; }

        /* =========================================================
           RESPONSIVE — Mobile Overlay
        ========================================================= */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1039;
        }
        .sidebar-overlay.show { display: block; }

        @media (max-width: 991.98px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
                box-shadow: none;
            }
            .sidebar.show {
                left: 0;
                box-shadow: 4px 0 24px rgba(0,0,0,0.2);
            }
            .main-wrapper { margin-left: 0; }
        }
    </style>
</head>
<body>
