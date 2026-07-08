@props(['source'])

@php
    $badges = [
        'earning' => ['label' => 'Kaynak: Hakediş', 'class' => 'bg-primary-50 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400'],
        'manual' => ['label' => 'Kaynak: Manuel', 'class' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$source] ?? $badges['manual'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
