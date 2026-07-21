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
        try {
            if (localStorage.getItem('crmlog.sidebarCollapsed') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
    <style>
        .app-sidebar {
            width: 16rem;
        }
        html.sidebar-collapsed .app-sidebar {
            width: 0 !important;
            min-width: 0 !important;
            border-color: transparent !important;
        }
        .app-sidebar--animating {
            transition: width 200ms ease-in-out, border-color 200ms ease-in-out;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    class="min-h-screen bg-gray-50"
    x-data="topNav"
    @crmlog-action.window="handleCrmlogAction($event.detail)"
    @keydown.escape.window="mobileOpen = false"
>
    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.top-nav')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @include('layouts.partials.flash-messages')

                @yield('content')
            </main>
        </div>
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
