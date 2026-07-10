@props(['default' => 'overview'])

@php
    $initialTab = request()->query('tab', $default);
@endphp

<div
    x-data="{
        activeTab: @js($initialTab),
        setTab(name) {
            this.activeTab = name;
            const url = new URL(window.location.href);
            if (name === @js($default)) {
                url.searchParams.delete('tab');
            } else {
                url.searchParams.set('tab', name);
            }
            window.history.replaceState({}, '', url);
        },
    }"
    {{ $attributes->merge(['class' => '']) }}
>
    {{ $slot }}
</div>
