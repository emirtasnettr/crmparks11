@props(['status'])

@php
    $badges = [
        'active' => ['label' => 'Aktif', 'dot' => '🟢', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'completed' => ['label' => 'Tamamlandı', 'dot' => '⚪', 'class' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300'],
        'leaving_soon' => ['label' => 'Yakında Ayrılıyor', 'dot' => '🟡', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400'],
    ];

    $config = $badges[$status] ?? $badges['completed'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ' . $config['class']]) }}>
    <span>{{ $config['dot'] }}</span>
    {{ $config['label'] }}
</span>
