@props(['status'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'pending' => ['label' => 'Beklemede', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'expired' => ['label' => 'Süresi Doldu', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
        'archived' => ['label' => 'Arşivlendi', 'class' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$status] ?? $badges['archived'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    {{ $config['label'] }}
</span>
