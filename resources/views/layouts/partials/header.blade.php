<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/80 px-4 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-800/80 sm:px-6">
    <button
        @click="open = !open"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-700"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <div class="flex flex-1 items-center gap-4">
        @include('layouts.partials.global-search')
    </div>

    <div class="flex items-center gap-2">
        <button
            @click="toggle()"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
            title="Tema değiştir"
        >
            <svg x-show="theme !== 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        @include('layouts.partials.notification-bell')

        <div x-data="{ open: false }" class="relative">
            <button
                @click="open = !open"
                class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 dark:hover:bg-slate-700"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
                    {{ auth()->user()->initials() }}
                </div>
                <span class="hidden text-sm font-medium text-gray-700 dark:text-slate-300 sm:block">{{ auth()->user()->name }}</span>
            </button>

            <div
                x-show="open"
                @click.outside="open = false"
                x-transition
                x-cloak
                class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-800"
            >
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Çıkış Yap
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
