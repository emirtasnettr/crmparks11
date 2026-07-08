@props(['extension'])

@php
    $config = match ($extension) {
        'pdf' => ['label' => 'PDF', 'class' => 'bg-red-50 text-red-600 dark:bg-red-600/10 dark:text-red-400'],
        'doc', 'docx' => ['label' => 'DOC', 'class' => 'bg-blue-50 text-blue-600 dark:bg-blue-600/10 dark:text-blue-400'],
        'xls', 'xlsx' => ['label' => 'XLS', 'class' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-600/10 dark:text-emerald-400'],
        'jpg', 'jpeg', 'png', 'webp' => ['label' => 'IMG', 'class' => 'bg-violet-50 text-violet-600 dark:bg-violet-600/10 dark:text-violet-400'],
        'zip' => ['label' => 'ZIP', 'class' => 'bg-amber-50 text-amber-600 dark:bg-amber-600/10 dark:text-amber-400'],
        default => ['label' => strtoupper(substr($extension, 0, 3)), 'class' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300'],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold ' . $config['class']]) }}>
    {{ $config['label'] }}
</span>
