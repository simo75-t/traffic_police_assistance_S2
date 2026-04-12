<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title', 'Admin Login')</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    :root {
      --admin-navy: #10243d;
      --admin-blue: #1a5d87;
      --admin-gold: #d7a93c;
      --admin-cream: #f7f2e8;
    }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Cairo", sans-serif;
      display: grid;
      place-items: center;
      padding: 24px;
      background:
        radial-gradient(circle at top left, rgba(215, 169, 60, 0.2), transparent 24%),
        linear-gradient(135deg, #0b1a2b 0%, #153857 50%, #1a5d87 100%);
    }
    .auth-box {
      width: 100%;
      max-width: 460px;
      background: rgba(247, 242, 232, 0.97);
      border-radius: 28px;
      box-shadow: 0 30px 80px rgba(7, 18, 31, 0.34);
      overflow: hidden;
      border: 1px solid rgba(255,255,255,0.25);
    }
    .auth-top {
      background: linear-gradient(135deg, var(--admin-navy), var(--admin-blue));
      color: white;
      padding: 28px 32px 20px;
    }
    .auth-body {
      padding: 30px 32px;
    }
  </style>
</head>
<body>
  <div class="auth-box">
    <div class="auth-top">
      @yield('auth_header')
    </div>
    <div class="auth-body">
      @yield('content')
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @yield('scripts')
</body>
</html>
