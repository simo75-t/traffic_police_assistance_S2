<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Police Manager Login')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --pm-navy: #0f2742;
            --pm-blue: #1f5f8b;
            --pm-gold: #d4a93a;
            --pm-cream: #f6f2e9;
            --pm-ink: #1d2939;
            --pm-danger: #b42318;
            --pm-border: rgba(15, 39, 66, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Cairo", sans-serif;
            color: var(--pm-ink);
            background:
                radial-gradient(circle at top left, rgba(212, 169, 58, 0.25), transparent 28%),
                linear-gradient(135deg, #0b1b2d 0%, #153956 48%, #1f5f8b 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .auth-shell {
            width: 100%;
            max-width: 380px;
            background: rgba(246, 242, 233, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(6, 17, 29, 0.32);
            overflow: hidden;
        }

 

        .auth-head {
    padding: 20px 22px 12px;
            color: white;
            background: linear-gradient(135deg, rgba(15, 39, 66, 0.96), rgba(31, 95, 139, 0.92));
        }
        .auth-head h1 {
            margin: 0 0 6px;
            font-size: 1.7rem;
        }

        .auth-head p {
            margin: 0;
            opacity: 0.9;
            line-height: 1.7;
        }

       .auth-body {
    padding: 20px 22px 22px;
}

        .alert {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 0.95rem;
        }

        .alert-danger {
            color: #fff;
            background: var(--pm-danger);
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }

 
        input {
            width: 100%;
            border: 1px solid var(--pm-border);
            border-radius: 14px;
            padding: 10px 12px;
            font: inherit;
            background: #fff;
            margin-bottom: 14px;
        }

        input:focus {
            outline: 3px solid rgba(31, 95, 139, 0.18);
            border-color: var(--pm-blue);
        }

        .btn-primary {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 14px 18px;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--pm-gold), #b68418);
            box-shadow: 0 14px 24px rgba(212, 169, 58, 0.24);
        }

        .helper-text {
            margin-top: 16px;
            color: rgba(29, 41, 57, 0.78);
            font-size: 0.92rem;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <main class="auth-shell">
        <section class="auth-head">
            <h1>@yield('heading', 'Police Manager Access')</h1>
            <p>@yield('subheading', 'Secure access for police manager tools and review workflows.')</p>
        </section>

        <section class="auth-body">
            @yield('content')
        </section>
    </main>
</body>
</html>
