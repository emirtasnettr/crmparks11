<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur-sm">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
            aria-label="Menüyü aç"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <button
            type="button"
            x-show="sidebarCollapsed"
            x-cloak
            @click.prevent="setSidebarCollapsed(false)"
            class="hidden rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:inline-flex"
            aria-label="Menüyü aç"
            title="Menüyü aç"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            @can('shift_planning.view')
                <a
                    href="{{ route('shift-planning.index') }}"
                    class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 {{ request()->routeIs('shift-planning.index', 'shift-planning.store', 'shift-planning.update', 'shift-planning.assign-couriers', 'shift-planning.destroy') ? 'ring-2 ring-gray-300 ring-offset-2' : '' }}"
                >
                    Vardiya
                </a>
            @endcan

            @can('report.view')
                <a
                    href="{{ route('radar') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 {{ request()->routeIs('radar') ? 'ring-2 ring-gray-300 ring-offset-2' : '' }}"
                >
                    <span class="live-record-icon inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-red-600" aria-hidden="true"></span>
                    Canlı Operasyon
                </a>
            @endcan

            @include('layouts.partials.global-search')

            @include('layouts.partials.notification-bell')

            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center rounded-full p-0.5 hover:bg-gray-100"
                    title="{{ auth()->user()->name }}"
                    aria-label="{{ auth()->user()->name }}"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
                        {{ auth()->user()->initials() }}
                    </div>
                </button>

                <div
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    x-cloak
                    class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Çıkış Yap
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
