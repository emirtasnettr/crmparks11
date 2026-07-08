@props(['status'])

@php
    $badges = [
        'issued' => ['label' => 'Kesildi', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'draft' => ['label' => 'Taslak', 'dot' => '🟡', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'cancelled' => ['label' => 'İptal', 'dot' => '🔴', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
    ];

    $config = $badges[$status] ?? $badges['draft'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
