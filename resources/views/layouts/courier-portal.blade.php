<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vardiyalarım') — {{ $branding['system_name'] ?? config('crmlog.name') }}</title>
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
>
    @include('layouts.partials.courier-portal-nav')

    <main class="mx-auto min-h-screen max-w-lg px-4 pt-[4.25rem] pb-[calc(4.5rem+env(safe-area-inset-bottom,0px))] sm:max-w-4xl sm:px-6">
        @include('layouts.partials.flash-messages')

        @yield('content')
    </main>

    @include('layouts.partials.courier-portal-bottom-nav')

    <div
        x-show="toast"
        x-cloak
        x-transition
        class="fixed bottom-24 left-4 right-4 z-[60] mx-auto max-w-sm rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg sm:left-auto sm:right-6"
        x-text="toast"
    ></div>

    @stack('scripts')
</body>
</html>
