@props([
    'url' => null,
    'initials' => '',
    'color' => 'bg-gray-500',
    'shape' => 'rounded-lg',
    'size' => 'h-10 w-10',
    'textSize' => 'text-xs',
    'alt' => '',
])

@if (! empty($url))
    <img
        src="{{ $url }}"
        alt="{{ $alt }}"
        {{ $attributes->merge(['class' => $size.' shrink-0 object-cover border border-gray-200 dark:border-slate-700 '.$shape]) }}
    />
@else
    <div {{ $attributes->merge(['class' => 'flex '.$size.' shrink-0 items-center justify-center '.$textSize.' font-bold text-white '.$color.' '.$shape]) }}>
        {{ $initials }}
    </div>
@endif
