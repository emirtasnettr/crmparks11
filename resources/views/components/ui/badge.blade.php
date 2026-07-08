@props(['variant' => 'secondary'])

@php
    $variants = [
        'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400',
        'secondary' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . ($variants[$variant] ?? $variants['secondary'])]) }}>
    {{ $slot }}
</span>
