@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'accent' => 'primary',
    'exclVat' => null,
])

@php
    $accents = [
        'primary' => 'border-l-primary-600',
        'success' => 'border-l-emerald-500',
        'warning' => 'border-l-amber-500',
        'danger' => 'border-l-red-500',
        'violet' => 'border-l-violet-500',
        'blue' => 'border-l-blue-500',
    ];

    $iconColors = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-600/10 dark:text-primary-400',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-600/10 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-600/10 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-600 dark:bg-red-600/10 dark:text-red-400',
        'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-600/10 dark:text-violet-400',
        'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-600/10 dark:text-blue-400',
    ];

    $valueText = is_scalar($value) ? (string) $value : '';
    $autoExclVat = str_contains($valueText, '₺') && ! str_contains($valueText, 'KDV dahil');
    $showExclVat = $exclVat ?? $autoExclVat;
    $caption = $subtitle ?? ($showExclVat ? 'KDV hariç' : null);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 border-l-4 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800 ' . ($accents[$accent] ?? $accents['primary'])]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0 space-y-1">
            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">{{ $title }}</p>
            <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $value }}</p>
            @if ($caption)
                <p class="text-[11px] font-normal leading-none text-gray-400 dark:text-slate-500">{{ $caption }}</p>
            @endif
        </div>
        @if ($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconColors[$accent] ?? $iconColors['primary'] }}">
                <x-ui.icon :name="$icon" class="h-5 w-5" />
            </div>
        @endif
    </div>
</div>
