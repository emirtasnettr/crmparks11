@props([
    'total' => 0,
    'page' => 1,
    'perPage' => 25,
    'lastPage' => 1,
])

@php
    $from = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
    $to = min($page * $perPage, $total);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 border-t border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700']) }}>
    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-slate-400">
        <span>{{ $perPage }} kayıt göster</span>
        @if ($total > 0)
            <span class="hidden sm:inline">&middot;</span>
            <span class="hidden sm:inline">{{ $from }}–{{ $to }} / {{ $total }}</span>
        @endif
    </div>

    <div class="flex items-center gap-1">
        @if ($page > 1)
            <a href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}"
               class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700">
                Önceki
            </a>
        @else
            <span class="cursor-not-allowed rounded-lg px-3 py-1.5 text-sm font-medium text-gray-300 dark:text-slate-600">
                Önceki
            </span>
        @endif

        @for ($i = 1; $i <= $lastPage; $i++)
            <a href="{{ request()->fullUrlWithQuery(['page' => $i]) }}"
               @class([
                   'flex h-8 w-8 items-center justify-center rounded-lg text-sm font-medium transition-colors',
                   'bg-primary-600 text-white' => $i === $page,
                   'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700' => $i !== $page,
               ])>
                {{ $i }}
            </a>
        @endfor

        @if ($page < $lastPage)
            <a href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}"
               class="rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700">
                Sonraki
            </a>
        @else
            <span class="cursor-not-allowed rounded-lg px-3 py-1.5 text-sm font-medium text-gray-300 dark:text-slate-600">
                Sonraki
            </span>
        @endif
    </div>
</div>
