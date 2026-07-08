@props(['default' => 'overview'])

<div
    x-data="{ activeTab: @js($default) }"
    {{ $attributes->merge(['class' => '']) }}
>
    {{ $slot }}
</div>
