@props([
    'amount' => null,
    'value' => null,
    'decimals' => 2,
    'exclVat' => true,
    'prefix' => '',
])

@php
    $display = $value ?? money_excl_vat($amount ?? 0, $decimals);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex flex-col items-start leading-tight']) }}>
    <span class="tabular-nums">{{ $prefix }}{{ $display }}</span>
    @if ($exclVat)
        <span class="text-[10px] font-normal text-gray-400 dark:text-slate-500">KDV hariç</span>
    @endif
</span>
