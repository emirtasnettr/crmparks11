@props(['status' => 'active'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'suspended' => ['label' => 'Askıda', 'dot' => '🟡', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'inactive' => ['label' => 'Pasif', 'dot' => '🔴', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
    ];

    $config = $badges[$status] ?? $badges['inactive'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
