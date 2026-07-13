@extends('layouts.app')

@section('title', 'Ürün Düzenle')

@section('content')
<div class="max-w-3xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Ürün Düzenle</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $product['name'] }}</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" href="{{ route('stock.products.show', $product['id']) }}">İptal</x-ui.button>
            <x-ui.button type="submit" form="stock-product-form">Kaydet</x-ui.button>
        </div>
    </div>

    @include('modules.stock.products.partials.form', [
        'formAction' => route('stock.products.update', $product['id']),
        'formMethod' => 'PUT',
        'formValues' => old() ?: $formValues,
        'statuses' => $statuses,
        'units' => $units,
    ])
</div>
@endsection
