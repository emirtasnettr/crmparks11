@props(['model' => 'per_package'])

@php
    $models = [
        'per_package' => ['label' => 'Paket Başı', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
        'fixed' => ['label' => 'Sabit Ücret', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
        'monthly_fixed' => ['label' => 'Aylık Sabit', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
        'hourly' => ['label' => 'Saatlik', 'class' => 'bg-orange-50 text-orange-700 dark:bg-orange-600/10 dark:text-orange-400'],
        'daily' => ['label' => 'Günlük', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
    ];

    $config = $models[$model] ?? $models['per_package'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    {{ $config['label'] }}
</span>
