<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $branding['system_name'] ?? config('crmlog.name') }}</title>
    @if (! empty($branding['favicon_url']))
        <link rel="icon" href="{{ $branding['favicon_url'] }}" type="image/png">
    @endif
    <script>
        document.documentElement.classList.remove('dark');
        try { localStorage.removeItem('theme'); } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="min-h-screen bg-gray-50"
    x-data="topNav"
    @crmlog-action.window="handleCrmlogAction($event.detail)"
    @keydown.escape.window="mobileOpen = false"
>
    <div class="flex min-h-screen flex-col">
        @include('layouts.partials.top-nav')

        <main class="flex-1 p-4 sm:p-6 lg:p-8">
            @include('layouts.partials.flash-messages')

            @yield('content')
        </main>
    </div>

    <div
        x-show="toast"
        x-cloak
        x-transition
        class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg"
        x-text="toast"
    ></div>

    @stack('scripts')
</body>
</html>
