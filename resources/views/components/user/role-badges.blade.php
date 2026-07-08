@props(['labels' => []])

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
    @forelse ($labels as $label)
        <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-slate-700 dark:text-slate-300">
            {{ $label }}
        </span>
    @empty
        <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
    @endforelse
</div>
