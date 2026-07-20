@props(['name', 'class' => 'h-5 w-5'])

@php
    /**
     * Semantic app icon names → Heroicons outline (blade-ui-kit/blade-heroicons).
     * Raw heroicon slugs (e.g. user-group) are also accepted.
     */
    $aliases = [
        'chart' => 'chart-bar',
        'building' => 'building-office-2',
        'users' => 'user-group',
        'user' => 'user',
        'calendar' => 'calendar-days',
        'contract' => 'document-text',
        'package' => 'folder',
        'folder' => 'folder',
        'report' => 'document-chart-bar',
        'form-applications' => 'clipboard-document-check',
        'courier' => 'truck',
        'agency' => 'building-storefront',
        'earning' => 'banknotes',
        'settings' => 'cog-6-tooth',
        'cog' => 'cog-6-tooth',
        'assignment' => 'user-plus',
        'policy-settings' => 'shield-check',
        'form-builder' => 'wrench-screwdriver',
        'landing-page' => 'window',
        'shield-check' => 'shield-check',
        'shield' => 'shield-check',
        'briefcase' => 'briefcase',
        'truck' => 'truck',
        'office' => 'building-office',
        'mail' => 'envelope',
        'sms' => 'device-phone-mobile',
        'bell' => 'bell',
        'photo' => 'photo',
        'swatch' => 'swatch',
        'key' => 'key',
        'server' => 'server-stack',
        'archive' => 'archive-box',
        'lock' => 'lock-closed',
        'clock' => 'clock',
        'signal' => 'signal',
        'information' => 'information-circle',
        'code' => 'code-bracket',
        'paint' => 'paint-brush',
        'identification' => 'identification',
    ];

    $heroicon = $aliases[$name] ?? $name;
    $component = 'heroicon-o-'.$heroicon;
@endphp

<x-dynamic-component :component="$component" {{ $attributes->merge(['class' => $class]) }} />
