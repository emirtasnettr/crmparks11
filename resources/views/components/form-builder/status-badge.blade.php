@props(['status', 'name' => null, 'color' => null])

@php
    $colorKey = $color ?? ($status['color'] ?? (is_string($status) ? $status : 'muted'));
    $label = $name ?? ($status['name'] ?? (is_string($status) ? $status : '—'));

    $styles = [
        'primary' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300',
        'success' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
        'danger' => 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300',
        'warning' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        'muted' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
        // form publish statuses (existing)
        'draft' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300',
        'archived' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    ];

    $formLabels = [
        'draft' => 'Taslak',
        'active' => 'Yayında',
        'archived' => 'Arşiv',
    ];

    if (is_string($status) && isset($formLabels[$status])) {
        $label = $formLabels[$status];
        $colorKey = $status;
    }
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $styles[$colorKey] ?? $styles['muted'] }}">
    {{ $label }}
</span>
