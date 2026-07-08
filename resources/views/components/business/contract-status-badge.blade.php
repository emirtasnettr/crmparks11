@props(['status'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'expiring_soon' => ['label' => 'Yakında Bitiyor', 'dot' => '🟡', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'expired' => ['label' => 'Süresi Doldu', 'dot' => '🔴', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
        'draft' => ['label' => 'Taslak', 'dot' => '⚪', 'class' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$status] ?? $badges['draft'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
