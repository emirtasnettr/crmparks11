@props(['status' => 'active'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'inactive' => ['label' => 'Pasif', 'dot' => '⚪', 'class' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$status] ?? $badges['inactive'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
