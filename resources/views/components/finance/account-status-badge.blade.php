@props(['status'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'passive' => ['label' => 'Pasif', 'class' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$status] ?? $badges['active'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
