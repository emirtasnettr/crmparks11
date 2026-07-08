@props(['type'])

@php
    $badges = [
        'courier_earning' => ['label' => 'Kurye Hakedişi', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
        'agency_earning' => ['label' => 'Acente Hakedişi', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
        'personnel' => ['label' => 'Personel', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
        'fuel' => ['label' => 'Yakıt', 'class' => 'bg-orange-50 text-orange-700 dark:bg-orange-600/10 dark:text-orange-400'],
        'office' => ['label' => 'Ofis', 'class' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
        'software' => ['label' => 'Yazılım', 'class' => 'bg-cyan-50 text-cyan-700 dark:bg-cyan-600/10 dark:text-cyan-400'],
        'advertising' => ['label' => 'Reklam', 'class' => 'bg-pink-50 text-pink-700 dark:bg-pink-600/10 dark:text-pink-400'],
        'tax' => ['label' => 'Vergi', 'class' => 'bg-red-50 text-red-700 dark:bg-red-600/10 dark:text-red-400'],
        'rent' => ['label' => 'Kira', 'class' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-600/10 dark:text-indigo-400'],
        'other' => ['label' => 'Diğer', 'class' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300'],
    ];

    $config = $badges[$type] ?? $badges['other'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$config['class']]) }}>
    {{ $config['label'] }}
</span>
