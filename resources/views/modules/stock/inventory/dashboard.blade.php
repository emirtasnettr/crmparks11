@extends('layouts.app')

@section('title', 'Envanter Durumu')

@section('content')
<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Envanter Durumu</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Depodaki ürünler, kritik stok uyarıları ve son zimmetler.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" href="{{ route('stock.products.index') }}">Ürünler</x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('stock.assignments.index') }}">Zimmetler</x-ui.button>
            @can('stock.create')
                <x-ui.button href="{{ route('stock.products.create') }}">Yeni Ürün</x-ui.button>
            @endcan
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="Aktif Ürün" :value="number_format($summary['total_products'])" icon="archive" accent="primary" />
        <x-ui.finance-stat-card title="Depodaki Stok" :value="number_format($summary['total_quantity'])" icon="package" accent="success" />
        <x-ui.finance-stat-card title="Kritik Stok" :value="number_format($summary['critical_count'])" icon="archive" accent="warning" />
        <x-ui.finance-stat-card title="Stokta Yok" :value="number_format($summary['out_of_stock'])" icon="archive" accent="danger" />
        <x-ui.finance-stat-card title="Zimmette" :value="number_format($summary['assigned_items'])" icon="users" accent="primary" />
    </div>

    @if (count($critical_products))
        <x-ui.card class="mb-6 border-l-4 border-l-amber-500" title="Kritik / Tükenen Stok">
            <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
                {{ $threshold }} adet ve altında kalan ürünler kritik kabul edilir.
            </p>
            <div class="space-y-2">
                @foreach ($critical_products as $product)
                    <a
                        href="{{ route('stock.products.show', $product['id']) }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2.5 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:hover:bg-slate-800/60"
                    >
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $product['sku'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="text-sm font-semibold {{ $product['stock_level'] === 'out' ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ number_format($product['quantity']) }} {{ $product['unit_label'] }}
                            </span>
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' => $product['stock_level'] === 'out',
                                'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $product['stock_level'] === 'critical',
                            ])>
                                {{ $product['stock_level_label'] }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
        <x-ui.card :padding="false" class="xl:col-span-3" title="Ürün Envanteri">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Ürün</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Stok</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Zimmette</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($products as $product)
                            <tr
                                class="cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800/60"
                                onclick="window.location='{{ route('stock.products.show', $product['id']) }}'"
                            >
                                <td class="px-4 py-3 sm:px-6">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $product['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $product['sku'] }}</p>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ number_format($product['quantity']) }} {{ $product['unit_label'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                    {{ number_format($product['assigned_quantity']) }}
                                </td>
                                <td class="px-4 py-3 sm:px-6">
                                    <span @class([
                                        'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => $product['stock_level'] === 'ok',
                                        'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => $product['stock_level'] === 'critical',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' => $product['stock_level'] === 'out',
                                    ])>
                                        {{ $product['stock_level_label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-slate-400 sm:px-6">
                                    Henüz aktif ürün yok.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card :padding="false" class="xl:col-span-2" title="Son Zimmetler">
            <div class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse ($recent_assignments as $assignment)
                    <a
                        href="{{ route('stock.products.show', $assignment['product_id']) }}"
                        class="block px-4 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/60 sm:px-6"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</p>
                                <p class="truncate text-sm text-gray-500 dark:text-slate-400">{{ $assignment['product_name'] }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format($assignment['quantity']) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $assignment['assigned_at_formatted'] }}</p>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-gray-500 dark:text-slate-400 sm:px-6">
                        Aktif zimmet kaydı yok.
                    </p>
                @endforelse
            </div>
            @if (count($recent_assignments))
                <div class="border-t border-gray-200 px-4 py-3 dark:border-slate-700 sm:px-6">
                    <a href="{{ route('stock.assignments.index') }}" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">
                        Tüm zimmetleri gör
                    </a>
                </div>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
