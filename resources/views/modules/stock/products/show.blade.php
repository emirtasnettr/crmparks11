@extends('layouts.app')

@section('title', $product['name'])

@section('content')
<div
    class="max-w-5xl"
    x-data="{ openAssign: {{ ($errors->has('courier_id') || $errors->has('quantity') || $errors->has('assigned_at')) ? 'true' : 'false' }} }"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $product['name'] }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $product['sku'] }} · {{ number_format($product['quantity']) }} {{ $product['unit_label'] }} stokta
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('stock.update')
                <x-ui.button
                    type="button"
                    x-on:click="openAssign = true"
                    :disabled="$product['quantity'] < 1"
                >
                    Kuryeye Zimmetle
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('stock.products.edit', $product['id']) }}">Düzenle</x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('stock.products.index') }}">Listeye Dön</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.finance-stat-card title="Depodaki Stok" :value="number_format($product['quantity']).' '.$product['unit_label']" icon="archive" accent="success" />
        <x-ui.finance-stat-card title="Aktif Zimmet" :value="number_format(count($product['assignments']))" icon="users" accent="warning" />
        <x-ui.finance-stat-card title="Durum" :value="$product['status_label']" icon="chart" accent="primary" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Ürün Bilgileri">
            <dl class="space-y-3 text-sm">
                <x-entity.detail-row label="Ürün Adı" :value="$product['name']" />
                <x-entity.detail-row label="SKU" :value="$product['sku']" />
                <x-entity.detail-row label="Birim" :value="$product['unit_label']" />
                <x-entity.detail-row label="Açıklama" :value="$product['description'] ?: '—'" />
                <x-entity.detail-row label="Notlar" :value="$product['notes'] ?: '—'" />
                <x-entity.detail-row label="Oluşturulma" :value="$product['created_at_formatted']" />
            </dl>
        </x-ui.card>

        <x-ui.card title="Aktif Zimmetler">
            @if (count($product['assignments']))
                <div class="space-y-3">
                    @foreach ($product['assignments'] as $assignment)
                        <div class="flex flex-col gap-2 rounded-lg border border-gray-200 p-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ number_format($assignment['quantity']) }} {{ $product['unit_label'] }} · {{ $assignment['assigned_at_formatted'] }}
                                </p>
                            </div>
                            @can('stock.update')
                                <form method="POST" action="{{ route('stock.assignments.return', $assignment['id']) }}" onsubmit="return confirm('Zimmet iade alınsın mı? Stok geri eklenecek.')">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary" size="sm">İade Al</x-ui.button>
                                </form>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu üründe aktif zimmet yok.</p>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card title="Zimmet Geçmişi">
        @if (count($product['assignment_history']))
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Adet</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Zimmet</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İade</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach ($product['assignment_history'] as $row)
                            <tr>
                                <td class="py-2.5 text-gray-900 dark:text-white">{{ $row['courier_name'] }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ number_format($row['quantity']) }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $row['assigned_at_formatted'] }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $row['returned_at_formatted'] }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $row['status_label'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-slate-400">Henüz zimmet kaydı yok.</p>
        @endif
    </x-ui.card>

    @can('stock.update')
        <div
            x-show="openAssign"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
        >
            <div class="fixed inset-0 bg-gray-900/50" x-on:click="openAssign = false"></div>
            <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kuryeye Zimmetle</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Mevcut stok: {{ number_format($product['quantity']) }} {{ $product['unit_label'] }}
                </p>

                <form method="POST" action="{{ route('stock.products.assign', $product['id']) }}" class="mt-4 space-y-4">
                    @csrf
                    <x-ui.select
                        name="courier_id"
                        label="Kurye *"
                        :selected="old('courier_id', '')"
                        :options="collect($couriers)->mapWithKeys(fn ($c) => [(string) $c['id'] => $c['label']])->prepend('Kurye seçin', '')->all()"
                        required
                    />
                    <x-ui.input
                        name="quantity"
                        type="number"
                        label="Adet *"
                        :value="old('quantity', '1')"
                        min="1"
                        :max="$product['quantity']"
                        required
                    />
                    <x-ui.input
                        name="assigned_at"
                        type="date"
                        label="Zimmet Tarihi *"
                        :value="old('assigned_at', now()->toDateString())"
                        required
                    />
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Not</label>
                        <textarea name="notes" rows="2" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">{{ old('notes') }}</textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.button type="button" variant="secondary" x-on:click="openAssign = false">Vazgeç</x-ui.button>
                        <x-ui.button type="submit">Zimmetle</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
</div>
@endsection
