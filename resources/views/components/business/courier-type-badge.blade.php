@props(['type' => 'independent', 'label' => null])

@php
    $types = [
        'independent' => ['label' => 'Esnaf Kurye', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400'],
        'agency' => ['label' => 'Acente Kuryesi', 'class' => 'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400'],
    ];

    $config = $types[$type] ?? $types['independent'];
    $displayLabel = $label ?? $config['label'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . $config['class']]) }}>
    {{ $displayLabel }}
</span>
