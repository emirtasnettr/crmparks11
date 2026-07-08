@props(['type'])

@php
    $badges = [
        'per_package' => ['label' => 'Paket Başı Hizmet', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
        'fixed_monthly' => ['label' => 'Aylık Sabit Hizmet', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
        'extra_service' => ['label' => 'Ek Hizmet', 'class' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-600/10 dark:text-cyan-400'],
        'penalty' => ['label' => 'Ceza Bedeli', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
        'manual' => ['label' => 'Manuel Gelir', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'other' => ['label' => 'Diğer', 'class' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$type] ?? $badges['other'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
