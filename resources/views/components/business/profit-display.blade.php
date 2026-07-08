@props(['amount'])

@php
    $isProfit = $amount >= 0;
@endphp

<span {{ $attributes->merge(['class' => 'font-semibold ' . ($isProfit ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400')]) }}>
    {{ $isProfit ? '' : '−' }}{{ money_excl_vat(abs($amount)) }}
</span>
