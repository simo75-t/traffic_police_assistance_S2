<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة مدير الشرطة')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --pm-blue-950: #0e2233;
            --pm-blue-900: #143a52;
            --pm-blue-800: #1c5773;
            --pm-blue-700: #256d8c;
            --pm-blue-600: #2f7fa0;
            --pm-blue-100: #e8f3f8;
            --pm-blue-50: #f4f9fc;

            --pm-gold: #e0b44c;
            --pm-gold-soft: #f2d48a;

            --pm-bg: #f4f7fb;
            --pm-paper: #ffffff;
            --pm-surface: #f7fbff;
            --pm-border: rgba(15, 23, 42, 0.08);

            --pm-text: #0f172a;
            --pm-muted: #64748b;

            --pm-success: #16a34a;
            --pm-warning: #f59e0b;
            --pm-danger: #dc2626;

            --pm-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: "Cairo", sans-serif;
            color: var(--pm-text);
            background:
                radial-gradient(circle at top center, rgba(37, 109, 140, 0.08), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #eef3f9 100%);
            overflow-x: hidden;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 260px;
            grid-template-areas: "content sidebar";
            gap: 0;
            align-items: start;
        }

        .sidebar {
            grid-area: sidebar;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            padding: 12px 14px 18px;
            color: #ffffff;
            background:
                radial-gradient(circle at top, rgba(37, 109, 140, 0.18), transparent 28%),
                linear-gradient(180deg, #143a52 0%, #1c5773 55%, #0e2233 100%);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand {
            margin-bottom: 20px;
            padding: 18px 16px 20px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .brand-badge {
            width: 90px;
            height: 90px;
            margin: 0 auto 14px;
            border-radius: 28px;
            display: grid;
            place-items: center;
            color: var(--pm-gold-soft);
            border: 2px solid rgba(242, 212, 138, 0.78);
            background:
                radial-gradient(circle at top, rgba(242, 212, 138, 0.14), transparent 55%),
                rgba(255, 255, 255, 0.04);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.45;
        }

        .brand h1 {
            margin: 0 0 4px;
            font-size: 1.35rem;
        }

        .brand p {
            margin: 0;
            line-height: 1.8;
            opacity: 0.86;
            font-size: 0.88rem;
        }

        .nav-group {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.05);
            transition: 0.18s ease;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            transform: translateX(-2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #e0b44c, #c99a2f);
            color: #0f172a;
            box-shadow: 0 8px 20px rgba(224, 180, 76, 0.35);
            transform: translateX(-3px);
        }

        .nav-link.active::before {
            content: "";
            position: absolute;
            right: -14px;
            top: 12px;
            bottom: 12px;
            width: 5px;
            border-radius: 999px 0 0 999px;
            background: #ffffff;
        }

        .nav-link--highlight {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .nav-link--highlight.active {
            background: linear-gradient(135deg, #e0b44c, #c99a2f);
        }

        .nav-link--muted {
            opacity: 0.84;
        }

        .nav-link__icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(255, 255, 255, 0.09);
            font-size: 1rem;
        }

        .nav-link.active .nav-link__icon {
            background: rgba(255, 255, 255, 0.22);
        }

        .logout-form {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
        }

        .logout-button {
            width: 100%;
            border: 0;
            border-radius: 18px;
            padding: 14px 16px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.09);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .content {
            grid-area: content;
            padding: 12px 14px 20px;
            min-width: 0;
        }

        .content-shell {
            min-height: calc(100vh - 24px);
            border-radius: 30px;
            background: var(--pm-paper);
            border: 1px solid rgba(15, 23, 42, 0.05);
            box-shadow: var(--pm-shadow);
            overflow: hidden;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            background:
                radial-gradient(circle at top center, rgba(37, 109, 140, 0.05), transparent 45%),
                #ffffff;
        }

        .topbar__left,
        .topbar__right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar__title {
            text-align: right;
        }

        .topbar__title strong {
            display: block;
            font-size: 1.5rem;
            color: var(--pm-text);
        }

        .topbar__title span {
            color: var(--pm-muted);
            font-size: 0.95rem;
        }

        .topbar__icon,
        .topbar__avatar {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            color: var(--pm-blue-800);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }

        .topbar__avatar {
            border-radius: 50%;
            color: var(--pm-gold);
            font-weight: 800;
        }

        .content-inner {
            padding: 0 18px 22px;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin: 22px 0 18px;
            padding: 0 4px;
        }

        .page-header h2 {
            margin: 0 0 4px;
            font-size: 1.95rem;
        }

        .page-header p {
            margin: 0;
            color: var(--pm-muted);
        }

        .surface {
            background: var(--pm-paper);
            border: 1px solid var(--pm-border);
            border-radius: 24px;
            box-shadow: var(--pm-shadow);
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
            color: #ffffff;
            background: var(--pm-success);
        }

        .flash-danger {
            color: #ffffff;
            background: var(--pm-danger);
        }

        .stats-grid,
        .actions-grid,
        .detail-grid,
        .map-meta-grid {
            display: grid;
            gap: 18px;
        }

        .stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .actions-grid {
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            margin-top: 20px;
        }

        .detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .map-meta-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .stat-card,
        .action-card,
        .detail-card,
        .map-meta-card {
            border-radius: 24px;
            border: 1px solid var(--pm-border);
            background: linear-gradient(180deg, #ffffff, var(--pm-surface));
            box-shadow: var(--pm-shadow);
        }

        .stat-card,
        .action-card {
            padding: 22px;
        }

        .detail-card,
        .map-meta-card {
            padding: 20px;
        }

        .stat-card small,
        .map-card-label {
            display: block;
            margin-bottom: 10px;
            color: var(--pm-muted);
            font-size: 0.92rem;
            font-weight: 700;
        }

        .stat-card strong,
        .map-meta-card strong {
            color: var(--pm-text);
            font-size: 1.95rem;
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
            color: #ffffff;
            background: linear-gradient(135deg, var(--pm-blue-700), var(--pm-blue-900));
        }

        .btn-secondary {
            color: var(--pm-blue-900);
            background: #eef6fb;
        }

        .btn-warning {
            color: #132238;
            background: linear-gradient(135deg, #f2d48a, var(--pm-gold));
        }

        .filter-bar {
            display: grid;
            grid-template-columns: 220px 1fr auto auto;
            gap: 12px;
        }

        .map-panel {
            min-height: 420px;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--pm-border);
            background: #f7fbff;
        }

        select,
        input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid var(--pm-border);
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            text-align: right;
            border-bottom: 1px solid rgba(16, 37, 63, 0.08);
            vertical-align: top;
        }

        th {
            color: var(--pm-muted);
            font-size: 0.92rem;
            font-weight: 800;
            background: #f7fbff;
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
                grid-template-areas:
                    "content"
                    "sidebar";
            }

            .sidebar {
                position: relative;
                height: auto;
                overflow: visible;
            }

            .content {
                padding-top: 0;
            }

            .content-shell {
                min-height: auto;
            }

            .stats-grid,
            .actions-grid,
            .detail-grid,
            .map-meta-grid,
            .filter-bar {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: stretch;
            }

            .topbar__left,
            .topbar__right {
                justify-content: space-between;
            }
        }

        @media (max-width: 640px) {
            .content,
            .sidebar {
                padding-left: 10px;
                padding-right: 10px;
            }

            .content-inner {
                padding: 0 12px 18px;
            }

            .page-header h2 {
                font-size: 1.55rem;
            }
        }

 
        nav[role="navigation"] a[rel="prev"]::after {
    content: "←";
}

nav[role="navigation"] a[rel="next"]::after {
    content: "→";
}

nav[role="navigation"] svg {
    display: none;
}

nav[role="navigation"] {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

nav[role="navigation"] .flex {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: center;
}

nav[role="navigation"] a,
nav[role="navigation"] span {
    padding: 6px 10px;
    font-size: 13px;
    border-radius: 8px;
    border: 1px solid var(--pm-border);
    background: #fff;
    color: var(--pm-text);
    font-weight: 600;
    min-width: 32px;
    text-align: center;
}

nav[role="navigation"] a:hover {
    background: var(--pm-blue-100);
}

nav[role="navigation"] .active span {
    background: var(--pm-blue-700);
    color: #fff;
    border: none;
    font-weight: 700;
}

nav[role="navigation"] a[rel="prev"],
nav[role="navigation"] a[rel="next"] {
    font-weight: 700;
    background: #f1f5f9;
}

nav[role="navigation"] {
    direction: rtl;
}
    </style>

    @yield('head')
</head>

<body>
    <div class="page-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge">شرطة<br>سوريا</div>
                <h1>مدير الشرطة</h1>
                <p>لوحة متابعة يومية للمخالفات، الخريطة الحرارية، وتوصيات الانتشار الميداني.</p>
            </div>

            <nav class="nav-group">
                <a class="nav-link {{ request()->routeIs('policemanager.home') ? 'active' : '' }}" href="{{ route('policemanager.home') }}">
                    <span>لوحة التحكم</span>
                    <span class="nav-link__icon">⌂</span>
                </a>

                <a class="nav-link {{ request()->routeIs('policemanager.violations.index') ? 'active' : '' }}" href="{{ route('policemanager.violations.index') }}">
                    <span>المخالفات</span>
                    <span class="nav-link__icon">▦</span>
                </a>

                <a class="nav-link {{ request()->routeIs('policemanager.violations.map') ? 'active' : '' }}" href="{{ route('policemanager.violations.map') }}">
                    <span>بلاغات المواطنين</span>
                    <span class="nav-link__icon">⌖</span>
                </a>

                <a class="nav-link nav-link--highlight {{ request()->routeIs('policemanager.violations.heatmap') ? 'active' : '' }}" href="{{ route('policemanager.violations.heatmap') }}">
                    <span>التحليل الحراري</span>
                    <span class="nav-link__icon">▥</span>
                </a>

                <a class="nav-link {{ request()->routeIs('policemanager.appeals.*') ? 'active' : '' }}" href="{{ route('policemanager.appeals.index') }}">
                    <span>الاعتراضات</span>
                    <span class="nav-link__icon">≍</span>
                </a>

                <a class="nav-link nav-link--muted" href="javascript:void(0)" aria-disabled="true">
                    <span>الإعدادات</span>
                    <span class="nav-link__icon">⚙</span>
                </a>
            </nav>

            <form class="logout-form" action="{{ route('policemanager.logout') }}" method="POST">
                @csrf
                <button class="logout-button" type="submit">تسجيل الخروج</button>
            </form>
        </aside>

        <main class="content">
            <div class="content-shell">
                <div class="content-inner">
                    <header class="page-header">
                        <div>
                            <h2>@yield('page_title', 'مدير الشرطة')</h2>
                            <p>@yield('page_description', 'متابعة مؤشرات القرار اليومية')</p>
                        </div>
                    </header>

                    @if (session('success'))
                        <div class="flash flash-success">{{ session('success') }}</div>
                    @endif

                    @if ($errors->has('login'))
                        <div class="flash flash-danger">{{ $errors->first('login') }}</div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>
    </div>

    

    @yield('scripts')

</body>
</html>