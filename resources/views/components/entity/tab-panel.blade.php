@props([
    'name',
    'alpinePage' => null,
    'alpineConfig' => [],
])

@php
    $alpineInit = null;

    if (filled($alpinePage)) {
        $alpineInit = $alpinePage.'('.\Illuminate\Support\Js::from($alpineConfig)->toHtml().')';
    }
@endphp

<div
    x-show="activeTab === '{{ $name }}'"
    x-cloak
    @if ($alpineInit)
        x-data="{!! $alpineInit !!}"
    @endif
    {{ $attributes->merge(['class' => 'space-y-6']) }}
>
    {{ $slot }}
</div>
