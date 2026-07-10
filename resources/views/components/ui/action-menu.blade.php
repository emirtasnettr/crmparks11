@props([
    'items' => [],
    'width' => 'w-48',
])

@php
    $toneClasses = [
        'default' => 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700',
        'danger' => 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20',
        'warning' => 'text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20',
    ];
@endphp

<div x-data="actionMenu()" class="relative inline-flex">
    <button
        x-ref="trigger"
        type="button"
        @click.stop.prevent="toggle()"
        class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
        aria-label="İşlemler"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-ref="menu"
            x-show="open"
            x-cloak
            @click.stop
            @keydown.escape.window="close()"
            :style="menuStyle"
            class="fixed z-[200] {{ $width }} max-h-[min(70vh,24rem)] overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-xl dark:border-slate-600 dark:bg-slate-800"
        >
            @foreach ($items as $item)
                @php
                    $tone = $toneClasses[$item['tone'] ?? 'default'] ?? $toneClasses['default'];
                @endphp

                @if (($item['type'] ?? '') === 'divider')
                    <div class="my-1 border-t border-gray-200 dark:border-slate-700"></div>
                    @continue
                @endif

                @if (($item['type'] ?? '') === 'link' || ! empty($item['href']))
                    <a
                        href="{{ $item['href'] }}"
                        @click="close()"
                        class="flex w-full px-4 py-2 text-left text-sm {{ $tone }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @elseif (($item['type'] ?? '') === 'dispatch' || ! empty($item['dispatch']))
                    <button
                        type="button"
                        @click="close(); $dispatch('{{ $item['dispatch'] }}', {{ \Illuminate\Support\Js::from($item['payload'] ?? []) }})"
                        class="flex w-full px-4 py-2 text-left text-sm {{ $tone }}"
                    >
                        {{ $item['label'] }}
                    </button>
                @else
                    <button
                        type="button"
                        @click="close(); $dispatch('crmlog-action', {{ \Illuminate\Support\Js::from([
                            'label' => $item['label'],
                            'action' => $item['action'] ?? 'generic',
                            'message' => $item['message'] ?? null,
                            'confirm' => $item['confirm'] ?? null,
                            'id' => $item['id'] ?? null,
                            'modal' => $item['modal'] ?? null,
                            'url' => $item['url'] ?? null,
                            'method' => $item['method'] ?? 'POST',
                        ]) }})"
                        class="flex w-full px-4 py-2 text-left text-sm {{ $tone }}"
                    >
                        {{ $item['label'] }}
                    </button>
                @endif
            @endforeach
        </div>
    </template>
</div>
