@props([
    'src' => null,
    'alt' => '',
])

<div {{ $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-2xl bg-slate-100 '. \App\Modules\LandingPage\Support\LandingPageHero::aspectClass()]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"></div>
    @else
        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 text-sm text-white/80">
            {{ $slot }}
        </div>
    @endif
</div>
