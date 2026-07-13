@extends('layouts.app')

@section('title', 'Yeni Ürün')

@section('content')
<div class="max-w-3xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni Ürün Kartı</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Depoya eklenecek ekipman ürününü tanımlayın.</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" href="{{ route('stock.products.index') }}">İptal</x-ui.button>
            <x-ui.button type="submit" form="stock-product-form">Kaydet</x-ui.button>
        </div>
    </div>

    @include('modules.stock.products.partials.form', [
        'formAction' => route('stock.products.store'),
        'formMethod' => 'POST',
        'formValues' => old() ?: [
            'name' => '',
            'sku' => '',
            'description' => '',
            'quantity' => '0',
            'unit' => 'adet',
            'status' => 'active',
            'notes' => '',
        ],
        'statuses' => $statuses,
        'units' => $units,
    ])
</div>
@endsection
