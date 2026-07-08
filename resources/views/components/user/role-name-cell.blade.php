@props(['role'])

@php
    $colorClasses = [
        'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-600/10 dark:text-rose-400',
        'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400',
        'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-600/10 dark:text-blue-400',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400',
        'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-600/10 dark:text-cyan-400',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400',
        'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-600/10 dark:text-indigo-400',
        'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-600/10 dark:text-teal-400',
        'primary' => 'bg-primary-100 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400',
        'slate' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
        'gray' => 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300',
    ];

    $colorClass = $colorClasses[$role['color'] ?? 'gray'] ?? $colorClasses['gray'];
@endphp

<div class="flex items-center gap-3">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $colorClass }}" data-role-icon="{{ $role['icon'] ?? 'shield' }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
    </div>
    <div class="min-w-0">
        <p class="font-medium text-gray-900 dark:text-white">{{ $role['display_name'] }}</p>
        <p class="truncate font-mono text-xs text-gray-500 dark:text-slate-400">{{ $role['name'] }}</p>
    </div>
</div>
