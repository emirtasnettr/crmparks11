@props(['type', 'class' => 'h-4 w-4'])

@php
    $icons = [
        'text' => 'bars-3-bottom-left',
        'textarea' => 'bars-3',
        'email' => 'envelope',
        'phone' => 'phone',
        'number' => 'hashtag',
        'date' => 'calendar-days',
        'select' => 'list-bullet',
        'radio' => 'check-circle',
        'checkbox' => 'check-circle',
        'file' => 'paper-clip',
        'heading' => 'bars-3-bottom-left',
    ];
@endphp

<x-ui.icon :name="$icons[$type] ?? 'bars-3-bottom-left'" :class="$class" {{ $attributes }} />
