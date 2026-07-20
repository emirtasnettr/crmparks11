@php
    $vardiyaActive = request()->routeIs('courier-portal.dashboard', 'courier-portal.shifts.*');
    $earningsActive = request()->routeIs('courier-portal.earnings');
    $profileActive = request()->routeIs('courier-portal.profile');
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm"
    aria-label="Kurye menü"
>
    <div class="mx-auto grid h-16 max-w-lg grid-cols-3 sm:max-w-4xl">
        <a
            href="{{ route('courier-portal.dashboard') }}"
            @class([
                'flex flex-col items-center justify-center gap-0.5 text-xs font-medium transition-colors',
                'text-primary-600' => $vardiyaActive,
                'text-gray-500 hover:text-gray-800' => ! $vardiyaActive,
            ])
        >
            <x-ui.icon name="clock" class="h-6 w-6" />
            <span>Vardiya</span>
        </a>

        <a
            href="{{ route('courier-portal.earnings') }}"
            @class([
                'flex flex-col items-center justify-center gap-0.5 text-xs font-medium transition-colors',
                'text-primary-600' => $earningsActive,
                'text-gray-500 hover:text-gray-800' => ! $earningsActive,
            ])
        >
            <x-ui.icon name="earning" class="h-6 w-6" />
            <span>Kazançlarım</span>
        </a>

        <a
            href="{{ route('courier-portal.profile') }}"
            @class([
                'flex flex-col items-center justify-center gap-0.5 text-xs font-medium transition-colors',
                'text-primary-600' => $profileActive,
                'text-gray-500 hover:text-gray-800' => ! $profileActive,
            ])
        >
            <x-ui.icon name="user" class="h-6 w-6" />
            <span>Profil</span>
        </a>
    </div>
</nav>
