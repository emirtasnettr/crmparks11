<header class="fixed inset-x-0 top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur-sm">
    <div class="mx-auto flex h-14 max-w-lg items-center px-4 sm:max-w-4xl sm:px-6">
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
    </div>
</header>
