@props(['title' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800']) }}>
    @if ($title)
        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['px-6 py-4' => $padding])>
        {{ $slot }}
    </div>
</div>
