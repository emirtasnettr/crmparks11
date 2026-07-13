@extends('layouts.app')

@section('title', 'Stok Yönetimi')

@section('content')
<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Stok Yönetimi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Ekipman ürün kartlarını yönetin ve kuryelere zimmetleyin.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" href="{{ route('stock.dashboard') }}">
                Envanter
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('stock.assignments.index') }}">
                Zimmetler
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('stock.activity.index') }}">
                Kayıt Geçmişi
            </x-ui.button>
            @can('stock.create')
                <x-ui.button href="{{ route('stock.products.create') }}">
                    Yeni Ürün
                </x-ui.button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Ürün Kartı" :value="number_format($summary['total_products'])" icon="archive" accent="primary" />
        <x-ui.finance-stat-card title="Depodaki Stok" :value="number_format($summary['total_quantity'])" icon="package" accent="success" />
        <x-ui.finance-stat-card title="Stokta Yok" :value="number_format($summary['low_stock'])" icon="archive" accent="danger" />
        <x-ui.finance-stat-card title="Zimmetteki Adet" :value="number_format($summary['assigned_items'])" icon="users" accent="warning" />
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('stock.products.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:p-6">
            <x-ui.input name="search" label="Ara" :value="$filters['search']" placeholder="Ürün adı veya SKU" />
            <x-ui.select
                name="status"
                label="Durum"
                :selected="$filters['status']"
                :options="collect($statuses)->prepend('Tümü', 'all')->all()"
            />
            <div class="flex items-end">
                <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">Filtrele</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Ürün</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">SKU</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Stok</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Zimmette</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($products as $product)
                        <tr
                            class="cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800/60"
                            onclick="window.location='{{ route('stock.products.show', $product['id']) }}'"
                        >
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $product['name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $product['sku'] }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'font-medium',
                                    'text-rose-600 dark:text-rose-400' => $product['is_out_of_stock'],
                                    'text-gray-900 dark:text-white' => ! $product['is_out_of_stock'],
                                ])>
                                    {{ number_format($product['quantity']) }} {{ $product['unit_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ number_format($product['assigned_quantity']) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $product['status_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-slate-400 sm:px-6">
                                Henüz ürün kartı yok. Yeni ürün ekleyerek stok takibine başlayın.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)
            <div class="border-t border-gray-200 px-4 py-3 text-sm text-gray-500 dark:border-slate-700 dark:text-slate-400 sm:px-6">
                Sayfa {{ $page }} / {{ $lastPage }} · Toplam {{ number_format($total) }} kayıt
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
