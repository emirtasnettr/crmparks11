@props([
    'label' => null,
    'name',
    'rows' => 4,
    'error' => null,
])

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->except('class', 'label', 'name', 'rows', 'error')->merge([
            'class' => 'w-full rounded-lg border px-3 py-2 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500/20 ' .
                ($error
                    ? 'border-red-300 focus:border-red-500 dark:border-red-500'
                    : 'border-gray-300 focus:border-primary-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white'),
        ]) }}
    >{{ $attributes->get('value') }}</textarea>

    @if ($error)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
