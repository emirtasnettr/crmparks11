@props(['status'])

@php
    $styles = [
        'draft' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
        'archived' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    ];
    $labels = [
        'draft' => 'Taslak',
        'active' => 'Yayında',
        'archived' => 'Arşiv',
    ];
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $styles[$status] ?? $styles['draft'] }}">
    {{ $labels[$status] ?? $status }}
</span>
