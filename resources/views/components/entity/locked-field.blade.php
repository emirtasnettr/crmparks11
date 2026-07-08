@props([
    'label',
    'value',
])

<div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-slate-600 dark:bg-slate-800/50">
    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">{{ $label }}</p>
    <p class="mt-0.5 text-sm font-medium text-gray-900 dark:text-white">{{ $value }}</p>
</div>
