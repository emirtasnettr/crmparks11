@props(['type', 'class' => 'h-4 w-4'])

@php
    $paths = [
        'text' => 'M4 6h16M4 12h16M4 18h7',
        'textarea' => 'M4 6h16M4 10h16M4 14h10M4 18h7',
        'email' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'phone' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        'number' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14',
        'date' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'select' => 'M4 6h16M4 12h16M4 18h16',
        'radio' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'checkbox' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'file' => 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13',
        'heading' => 'M4 6h16M4 12h8',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'fill' => 'none', 'viewBox' => '0 0 24 24', 'stroke' => 'currentColor']) }}>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $paths[$type] ?? $paths['text'] }}"/>
</svg>
