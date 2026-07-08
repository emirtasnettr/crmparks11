@if (!empty($breadcrumbs ?? null) || View::hasSection('breadcrumb'))
    <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500 dark:text-slate-400">
        @hasSection('breadcrumb')
            @yield('breadcrumb')
        @else
            @foreach ($breadcrumbs as $index => $crumb)
                @if ($index > 0)
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                @endif
                @if (!empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}" class="hover:text-gray-900 dark:hover:text-white">{{ $crumb['label'] }}</a>
                @else
                    <span class="font-medium text-gray-900 dark:text-white">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        @endif
    </nav>
@endif
