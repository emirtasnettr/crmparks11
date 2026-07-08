@props(['status'])

@php
    $badges = [
        'completed' => ['label' => 'Tamamlandı', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'pending' => ['label' => 'Bekliyor', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'overdue' => ['label' => 'Gecikmiş', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
        'approval' => ['label' => 'Onay Bekliyor', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
    ];

    $config = $badges[$status] ?? $badges['pending'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
