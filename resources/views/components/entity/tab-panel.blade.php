@props(['name'])

<div x-show="activeTab === '{{ $name }}'" x-cloak {{ $attributes->merge(['class' => 'space-y-6']) }}>
    {{ $slot }}
</div>
