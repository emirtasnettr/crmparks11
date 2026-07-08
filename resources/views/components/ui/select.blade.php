@props([
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
])

@php
    $alpineAttributes = $attributes->filter(fn ($value, $key) => str_starts_with($key, 'x-') || str_starts_with($key, '@'));
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $alpineAttributes }}
        {{ $attributes->except('class', 'label', 'name', 'options', 'selected')->merge([
            'class' => 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white',
        ]) }}
    >
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</div>
