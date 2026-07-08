@props(['status'])

@php
    $badges = [
        'collected' => ['label' => 'Tahsil Edildi', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'pending' => ['label' => 'Bekliyor', 'dot' => '🟡', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'overdue' => ['label' => 'Gecikmiş', 'dot' => '🔴', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
    ];

    $config = $badges[$status] ?? $badges['pending'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
