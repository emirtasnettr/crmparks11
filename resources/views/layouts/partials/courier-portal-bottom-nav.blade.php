@php
    $shiftsActive = request()->routeIs('courier-portal.dashboard', 'courier-portal.shifts.*');
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm"
    aria-label="Kurye menü"
>
    <div class="mx-auto grid h-16 max-w-lg grid-cols-2 sm:max-w-4xl">
        <a
            href="{{ route('courier-portal.dashboard') }}"
            @class([
                'flex flex-col items-center justify-center gap-0.5 text-xs font-medium transition-colors',
                'text-primary-600' => $shiftsActive,
                'text-gray-500 hover:text-gray-800' => ! $shiftsActive,
            ])
        >
            <x-ui.icon name="clock" class="h-6 w-6" />
            <span>Vardiyalarım</span>
        </a>

        <div x-data="{ open: false }" class="relative flex items-center justify-center">
            <button
                type="button"
                @click="open = !open"
                class="flex w-full flex-col items-center justify-center gap-0.5 text-xs font-medium text-gray-500 transition-colors hover:text-gray-800"
                :class="open ? 'text-primary-600' : ''"
                aria-label="Hesap"
            >
                <x-ui.icon name="user" class="h-6 w-6" />
                <span>Hesap</span>
            </button>

            <div
                x-show="open"
                @click.outside="open = false"
                x-transition
                x-cloak
                class="absolute bottom-[calc(100%+0.5rem)] right-2 w-52 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
            >
                <div class="border-b border-gray-100 px-4 py-2">
                    <p class="truncate text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Çıkış Yap
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
