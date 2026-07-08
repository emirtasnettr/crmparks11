<!DOCTYPE html>
<html lang="tr" x-data="themeToggle" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $branding['system_name'] ?? config('crmlog.name') }}</title>
    @if (! empty($branding['favicon_url']))
        <link rel="icon" href="{{ $branding['favicon_url'] }}" type="image/png">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    use App\Core\Menu\SidebarMenu;

    $expandedGroups = SidebarMenu::initialExpandedGroups();
@endphp
<body
    class="min-h-screen"
    x-data="sidebar(@js($expandedGroups))"
    @crmlog-action.window="handleCrmlogAction($event.detail)"
>
    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex flex-1 flex-col lg:pl-64">
            @include('layouts.partials.header')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @include('layouts.partials.breadcrumb')
                @include('layouts.partials.flash-messages')

                @yield('content')
            </main>
        </div>
    </div>

    <div
        x-show="toast"
        x-cloak
        x-transition
        class="fixed bottom-6 right-6 z-[60] max-w-sm rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg dark:border-emerald-800/50 dark:bg-emerald-900/30 dark:text-emerald-200"
        x-text="toast"
    ></div>

    @stack('scripts')
</body>
</html>
