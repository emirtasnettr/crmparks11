@props([
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
    'error' => null,
])

@php
    $alpineAttributes = $attributes->filter(fn ($value, $key) => str_starts_with($key, 'x-') || str_starts_with($key, '@'));
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-2']) }}>
    @if ($label)
        <p class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $label }}</p>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach ($options as $value => $text)
            <label class="relative flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 transition-colors has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:has-[:checked]:border-primary-500 dark:has-[:checked]:bg-primary-600/10 {{ $error ? 'border-red-300 dark:border-red-500' : 'border-gray-200 dark:border-slate-600' }}">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    @checked((string) $selected === (string) $value)
                    {{ $alpineAttributes }}
                    class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-500 dark:bg-slate-800"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $text }}</span>
            </label>
        @endforeach
    </div>

    @if ($error)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
