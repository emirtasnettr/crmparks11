@props(['type'])

@php
    $badges = [
        'business' => ['label' => 'İşletme', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
        'courier' => ['label' => 'Kurye', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
        'agency' => ['label' => 'Acente', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
    ];

    $config = $badges[$type] ?? $badges['business'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
