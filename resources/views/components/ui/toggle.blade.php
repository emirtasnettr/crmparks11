@props([
    'label' => null,
    'name',
    'checked' => false,
])

<div {{ $attributes->only('class')->merge(['class' => 'flex items-center justify-between gap-4']) }}>
    @if ($label)
        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $label }}</span>
    @endif

    <label class="relative inline-flex cursor-pointer items-center">
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            @checked($checked)
            {{ $attributes->except('class', 'label', 'name', 'checked') }}
            class="peer sr-only"
        />
        <div class="h-6 w-11 rounded-full bg-gray-200 transition-colors peer-checked:bg-primary-600 peer-focus:ring-2 peer-focus:ring-primary-500/20 dark:bg-slate-600"></div>
        <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></div>
    </label>
</div>
