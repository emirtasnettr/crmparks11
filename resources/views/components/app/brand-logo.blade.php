@props([
    'size' => 'md',
    'context' => 'app',
    'surface' => 'auto',
])

@php
    $branding = $branding ?? app(\App\Modules\Setting\Services\AppBrandingService::class)->resolve();

    $logoUrl = $context === 'login'
        ? ($branding['login_logo_url'] ?? $branding['logo_url'])
        : $branding['logo_url'];

    $darkLogoUrl = $context === 'login'
        ? ($branding['login_logo_url'] ?? $branding['dark_logo_url'] ?? $branding['logo_url'])
        : ($branding['dark_logo_url'] ?? $branding['logo_url']);

    $sizes = [
        'sm' => ['box' => 'h-8', 'img' => 'h-8 max-w-[96px]', 'text' => 'text-xs'],
        'md' => ['box' => 'h-9 w-9', 'img' => 'h-9 max-w-[120px]', 'text' => 'text-sm'],
        'lg' => ['box' => 'h-12 w-12', 'img' => 'h-12 max-w-[200px]', 'text' => 'text-lg'],
        'xl' => ['box' => 'h-14 w-14', 'img' => 'h-14 max-w-[240px]', 'text' => 'text-xl'],
        'sidebar' => ['box' => 'h-10 w-10', 'img' => 'h-10 max-w-[180px]', 'text' => 'text-sm'],
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['md'];
    $initials = mb_strtoupper(mb_substr($branding['system_name'], 0, 2));
@endphp

@if ($logoUrl)
    @if ($surface === 'dark')
        <img
            src="{{ $darkLogoUrl }}"
            alt="{{ $branding['system_name'] }}"
            {{ $attributes->merge(['class' => $sizeClasses['img'].' w-auto shrink-0 object-contain']) }}
        />
    @elseif ($surface === 'light')
        <img
            src="{{ $logoUrl }}"
            alt="{{ $branding['system_name'] }}"
            {{ $attributes->merge(['class' => $sizeClasses['img'].' w-auto shrink-0 object-contain']) }}
        />
    @else
        <img
            src="{{ $logoUrl }}"
            alt="{{ $branding['system_name'] }}"
            {{ $attributes->merge(['class' => $sizeClasses['img'].' w-auto shrink-0 object-contain dark:hidden']) }}
        />
        <img
            src="{{ $darkLogoUrl }}"
            alt="{{ $branding['system_name'] }}"
            {{ $attributes->merge(['class' => 'hidden '.$sizeClasses['img'].' w-auto shrink-0 object-contain dark:block']) }}
        />
    @endif
@else
    <div {{ $attributes->merge(['class' => 'flex '.$sizeClasses['box'].' shrink-0 items-center justify-center rounded-xl bg-primary-600 font-bold text-white '.$sizeClasses['text']]) }}>
        {{ $initials }}
    </div>
@endif
