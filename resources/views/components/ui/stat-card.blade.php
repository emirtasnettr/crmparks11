@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'primary',
    'change' => null,
])

@php
    $colors = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-600/10 dark:text-primary-400',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-600/10 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-600 dark:bg-amber-600/10 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-600 dark:bg-red-600/10 dark:text-red-400',
        'secondary' => 'bg-gray-50 text-gray-600 dark:bg-slate-700 dark:text-slate-400',
    ];
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="flex items-start justify-between">
        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500 dark:text-slate-400">{{ $title }}</p>
            <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $value }}</p>
            @if ($change)
                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $change }}</p>
            @endif
        </div>
        @if ($icon)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $colors[$color] ?? $colors['primary'] }}">
                <x-ui.icon :name="$icon" class="h-5 w-5" />
            </div>
        @endif
    </div>
</div>
