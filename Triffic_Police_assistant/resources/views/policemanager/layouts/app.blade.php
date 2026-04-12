<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Police Manager Dashboard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --pm-navy: #10253f;
            --pm-blue: #195d86;
            --pm-sky: #d9ebf5;
            --pm-gold: #d4a93a;
            --pm-surface: #fffdf8;
            --pm-paper: #ffffff;
            --pm-border: rgba(16, 37, 63, 0.1);
            --pm-text: #1d2939;
            --pm-muted: #667085;
            --pm-success: #067647;
            --pm-warning: #b54708;
            --pm-danger: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Cairo", sans-serif;
            color: var(--pm-text);
            background:
                radial-gradient(circle at top right, rgba(212, 169, 58, 0.14), transparent 24%),
                linear-gradient(180deg, #eff5f9 0%, #f9f5ed 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 280px 1fr;
        }

        .sidebar {
            padding: 28px 20px;
            color: white;
            background: linear-gradient(180deg, var(--pm-navy), #16385a 62%, var(--pm-blue));
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .brand {
            margin-bottom: 28px;
            padding: 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .brand h1 {
            margin: 0 0 6px;
            font-size: 1.5rem;
        }

        .brand p {
            margin: 0;
            line-height: 1.8;
            opacity: 0.88;
            font-size: 0.95rem;
        }

        .nav-link {
            display: block;
            padding: 14px 16px;
            margin-bottom: 10px;
            border-radius: 14px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.active,
        .nav-link:hover {
            background: linear-gradient(135deg, rgba(212, 169, 58, 0.92), rgba(180, 130, 18, 0.92));
            color: #132238;
        }

        .logout-form {
            margin-top: 28px;
        }

        .logout-button {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 14px 16px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            color: white;
            background: rgba(180, 35, 24, 0.9);
        }

        .content {
            padding: 28px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 24px;
        }

        .page-header h2 {
            margin: 0 0 6px;
            font-size: 2rem;
        }

        .page-header p {
            margin: 0;
            color: var(--pm-muted);
        }

        .surface {
            background: var(--pm-paper);
            border: 1px solid var(--pm-border);
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(16, 37, 63, 0.08);
        }

        .surface-body {
            padding: 24px;
        }

        .flash {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 14px;
            font-weight: 700;
        }

        .flash-success {
            color: white;
            background: var(--pm-success);
        }

        .flash-danger {
            color: white;
            background: var(--pm-danger);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .stat-card {
            padding: 22px;
            border-radius: 24px;
            color: white;
            background: linear-gradient(135deg, var(--pm-navy), var(--pm-blue));
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #124f74, #2f7aa5);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #9b6f12, #d4a93a);
            color: #132238;
        }

        .stat-card small {
            display: block;
            opacity: 0.88;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .stat-card strong {
            font-size: 2rem;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .action-card {
            padding: 22px;
            border-radius: 24px;
            border: 1px solid var(--pm-border);
            background: linear-gradient(180deg, #fff, var(--pm-surface));
        }

        .action-card h3,
        .detail-grid h3 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .action-card p,
        .detail-grid p {
            margin-top: 0;
            color: var(--pm-muted);
            line-height: 1.9;
        }

        .btn,
        select,
        input {
            font: inherit;
        }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 800;
            cursor: pointer;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, var(--pm-blue), var(--pm-navy));
        }

        .btn-secondary {
            color: var(--pm-navy);
            background: #eef4f8;
        }

        .btn-warning {
            color: #132238;
            background: linear-gradient(135deg, #efd28a, var(--pm-gold));
        }

        .filter-bar {
            display: grid;
            grid-template-columns: 220px 1fr auto auto;
            gap: 12px;
        }

        select,
        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid var(--pm-border);
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(16, 37, 63, 0.08);
            vertical-align: top;
        }

        th {
            color: var(--pm-muted);
            font-size: 0.92rem;
            font-weight: 800;
            background: #fbfdff;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .badge-pending {
            color: #7a2e0e;
            background: #fef0c7;
        }

        .badge-accepted {
            color: #065f46;
            background: #d1fadf;
        }

        .badge-rejected {
            color: #912018;
            background: #fee4e2;
        }

        .inline-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .detail-card {
            padding: 20px;
            border-radius: 20px;
            border: 1px solid var(--pm-border);
            background: linear-gradient(180deg, #fff, #fbfcfe);
        }

        .detail-label {
            display: block;
            margin-bottom: 8px;
            color: var(--pm-muted);
            font-size: 0.92rem;
            font-weight: 700;
        }

        .detail-value {
            font-size: 1.05rem;
            font-weight: 700;
            word-break: break-word;
        }

        .stack {
            display: grid;
            gap: 18px;
        }

        .empty-state {
            padding: 24px;
            text-align: center;
            color: var(--pm-muted);
        }

        @media (max-width: 1080px) {
            .page-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                height: auto;
            }

            .stats-grid,
            .actions-grid,
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .filter-bar {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <aside class="sidebar">
            <div class="brand">
                <h1>Police Manager</h1>
                <p>Review violations, handle appeals, and maintain the decision workflow from one place.</p>
            </div>

            <nav>
                <a class="nav-link {{ request()->routeIs('policemanager.home') ? 'active' : '' }}" href="{{ route('policemanager.home') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('policemanager.violations.index') ? 'active' : '' }}" href="{{ route('policemanager.violations.index') }}">Violations</a>
                <a class="nav-link {{ request()->routeIs('policemanager.violations.heatmap') ? 'active' : '' }}" href="{{ route('policemanager.violations.heatmap') }}">Heatmap</a>
                <a class="nav-link {{ request()->routeIs('policemanager.appeals.*') ? 'active' : '' }}" href="{{ route('policemanager.appeals.index') }}">Appeals</a>
            </nav>

            <form class="logout-form" action="{{ route('policemanager.logout') }}" method="POST">
                @csrf
                <button class="logout-button" type="submit">Logout</button>
            </form>
        </aside>

        <main class="content">
            <header class="page-header">
                <div>
                    <h2>@yield('page_title', 'Police Manager')</h2>
                    <p>@yield('page_description', 'Manage daily review and decision workflows.')</p>
                </div>
            </header>

            @if (session('success'))
                <div class="flash flash-success">{{ session('success') }}</div>
            @endif

            @if ($errors->has('login'))
                <div class="flash flash-danger">{{ $errors->first('login') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
