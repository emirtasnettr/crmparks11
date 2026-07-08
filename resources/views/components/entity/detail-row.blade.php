@props(['label', 'value' => null])

<div class="flex justify-between gap-4">
    <dt class="text-gray-500 dark:text-slate-400">{{ $label }}</dt>
    <dd class="max-w-[60%] text-right font-medium text-gray-900 dark:text-white">
        @if (trim($slot) !== '')
            {{ $slot }}
        @else
            {{ $value ?? '—' }}
        @endif
    </dd>
</div>
