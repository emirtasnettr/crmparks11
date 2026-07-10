<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Giriş') — {{ $branding['system_name'] ?? config('crmlog.name') }}</title>
    @if (! empty($branding['favicon_url']))
        <link rel="icon" href="{{ $branding['favicon_url'] }}" type="image/png">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        document.documentElement.classList.remove('dark');
        try { localStorage.removeItem('theme'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --brand: #16B24B;
            --brand-dark: #0f8f3a;
            --brand-deep: #0a1f12;
            --brand-soft: #e8f8ee;
        }

        .font-display { font-family: 'Sora', ui-sans-serif, system-ui, sans-serif; }
        .font-body { font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif; }

        @keyframes login-fade-up {
            from { opacity: 0; transform: translateY(22px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes login-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes login-drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(18px, -22px) scale(1.08); }
            66% { transform: translate(-14px, 12px) scale(0.96); }
        }
        @keyframes login-drift-alt {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, 16px) rotate(8deg); }
        }
        @keyframes login-spin-slow {
            to { transform: rotate(360deg); }
        }
        @keyframes login-pulse-ring {
            0% { transform: scale(0.85); opacity: 0.55; }
            70% { transform: scale(1.25); opacity: 0; }
            100% { transform: scale(1.25); opacity: 0; }
        }
        @keyframes login-float-y {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-14px); }
        }
        @keyframes login-shimmer {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes login-dash {
            to { stroke-dashoffset: -240; }
        }
        @keyframes login-particle {
            0%, 100% { transform: translateY(0) scale(1); opacity: 0.35; }
            50% { transform: translateY(-28px) scale(1.4); opacity: 0.85; }
        }

        .login-fade-up { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .login-fade-up-1 { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both; }
        .login-fade-up-2 { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.16s both; }
        .login-fade-up-3 { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.24s both; }
        .login-fade-up-4 { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.32s both; }
        .login-fade-up-5 { animation: login-fade-up 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.4s both; }
        .login-fade-in { animation: login-fade-in 1s ease both; }

        .login-orb { animation: login-drift 12s ease-in-out infinite; }
        .login-orb-alt { animation: login-drift-alt 16s ease-in-out infinite; }
        .login-float { animation: login-float-y 5.5s ease-in-out infinite; }
        .login-spin { animation: login-spin-slow 28s linear infinite; }
        .login-spin-rev { animation: login-spin-slow 40s linear infinite reverse; }

        .login-ring::before,
        .login-ring::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            border: 1.5px solid rgba(22, 178, 75, 0.35);
            animation: login-pulse-ring 3.2s ease-out infinite;
        }
        .login-ring::after { animation-delay: 1.1s; }

        .login-gradient-shift {
            background: linear-gradient(125deg, #07150d 0%, #0d2a18 35%, #16B24B 78%, #3dd46a 100%);
            background-size: 180% 180%;
            animation: login-shimmer 14s ease infinite;
        }

        .login-mesh {
            background-image:
                radial-gradient(circle at 20% 30%, rgba(22, 178, 75, 0.28), transparent 42%),
                radial-gradient(circle at 80% 20%, rgba(61, 212, 106, 0.18), transparent 38%),
                radial-gradient(circle at 60% 80%, rgba(22, 178, 75, 0.22), transparent 45%);
        }

        .login-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
            background-size: 56px 56px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
        }

        .login-path {
            stroke-dasharray: 12 10;
            animation: login-dash 8s linear infinite;
        }

        .login-dot {
            animation: login-particle 4s ease-in-out infinite;
        }
        .login-dot:nth-child(2) { animation-delay: 0.6s; }
        .login-dot:nth-child(3) { animation-delay: 1.2s; }
        .login-dot:nth-child(4) { animation-delay: 1.8s; }
        .login-dot:nth-child(5) { animation-delay: 2.4s; }
        .login-dot:nth-child(6) { animation-delay: 0.9s; }

        .login-btn {
            background: linear-gradient(135deg, #16B24B 0%, #12a043 55%, #0f8f3a 100%);
            background-size: 160% 160%;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-position 0.4s ease;
        }
        .login-btn:hover {
            background-position: 100% 50%;
            transform: translateY(-1px);
            box-shadow: 0 18px 40px -16px rgba(22, 178, 75, 0.65);
        }
        .login-btn:active { transform: translateY(0); }

        .login-panel {
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }
        .login-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 28px 70px -28px rgba(10, 40, 22, 0.35);
        }

        @media (prefers-reduced-motion: reduce) {
            .login-fade-up,
            .login-fade-up-1,
            .login-fade-up-2,
            .login-fade-up-3,
            .login-fade-up-4,
            .login-fade-up-5,
            .login-orb,
            .login-orb-alt,
            .login-float,
            .login-spin,
            .login-spin-rev,
            .login-gradient-shift,
            .login-path,
            .login-dot,
            .login-ring::before,
            .login-ring::after {
                animation: none !important;
            }
        }
    </style>
</head>
<body class="font-body min-h-screen antialiased">
    @yield('content')
</body>
</html>
