<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-navy: #10243d;
            --admin-blue: #1a5d87;
            --admin-gold: #d7a93c;
            --admin-bg: #f3f6f8;
            --admin-card: #ffffff;
            --admin-border: rgba(16, 36, 61, 0.08);
            --admin-text: #1c2733;
            --admin-muted: #667085;
            --admin-success: #067647;
            --admin-danger: #b42318;
        }

        body {
            font-family: "Cairo", sans-serif;
            background:
                radial-gradient(circle at top right, rgba(215, 169, 60, 0.12), transparent 24%),
                linear-gradient(180deg, #eef4f8 0%, #f8fafc 100%);
            color: var(--admin-text);
        }

        .content-wrapper {
            background: transparent;
        }

        .content-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 24px;
            box-shadow: 0 18px 45px rgba(16, 36, 61, 0.08);
            overflow: hidden;
        }

        .content-card-body {
            padding: 24px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .page-subtitle {
            color: var(--admin-muted);
            margin-bottom: 24px;
        }

        .admin-alert {
            border: 0;
            border-radius: 16px;
            padding: 14px 18px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .admin-alert-success {
            background: var(--admin-success);
            color: white;
        }

        .admin-alert-danger {
            background: var(--admin-danger);
            color: white;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">

<div class="wrapper">
    @include('admin.partials.sidebar')

    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                @if (session('success'))
                    <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
                @endif

                @if ($errors->has('login'))
                    <div class="admin-alert admin-alert-danger">{{ $errors->first('login') }}</div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    @include('admin.partials.footer')
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
@yield('scripts')
</body>
</html>
