<header class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto flex h-16 max-w-lg items-center justify-between gap-3 px-4 sm:max-w-4xl sm:px-6">
        <a href="{{ route('courier-portal.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($branding['has_logo'] ?? false)
                <x-app.brand-logo size="sm" />
            @else
                <x-app.brand-logo size="md" />
                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ $branding['system_name'] ?? config('crmlog.name') }}
                </span>
            @endif
        </a>

        <div x-data="{ open: false }" class="relative shrink-0">
            <button
                type="button"
                @click="open = !open"
                class="flex items-center rounded-full p-0.5 hover:bg-gray-100"
                title="{{ auth()->user()->name }}"
                aria-label="{{ auth()->user()->name }}"
            >
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
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
</header>
