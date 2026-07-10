@php
    $preview = $notificationHeader ?? ['unread_count' => 0, 'items' => []];
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
        title="Bildirimler"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if (($preview['unread_count'] ?? 0) > 0)
            <span class="absolute right-1 top-1 inline-flex min-h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {{ $preview['unread_count'] > 9 ? '9+' : $preview['unread_count'] }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        @click.outside="open = false"
        x-transition
        x-cloak
        class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-800 sm:w-96"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-slate-700">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Bildirimler</p>
            @can('notification.view')
                <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Tümünü Gör</a>
            @endcan
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($preview['items'] as $item)
                <a
                    href="{{ $item['action_url'] ?? route('notifications.index') }}"
                    class="block border-b border-gray-100 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:hover:bg-slate-700/50"
                >
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['title'] }}</p>
                    <p class="mt-1 line-clamp-2 text-xs text-gray-600 dark:text-slate-300">{{ $item['message'] }}</p>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-slate-400">{{ $item['created_at_formatted'] }}</p>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-slate-400">
                    Okunmamış bildirim yok.
                </div>
            @endforelse
        </div>
    </div>
</div>
