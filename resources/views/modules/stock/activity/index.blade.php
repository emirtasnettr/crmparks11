@extends('layouts.app')

@section('title', 'Stok Kayıt Geçmişi')

@section('content')
<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kayıt Geçmişi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Ürün, zimmet ve stok adedi işlemlerinin silinmeyen kayıtları.
            </p>
        </div>
        <x-ui.button variant="secondary" href="{{ route('stock.products.index') }}">Ürünlere Dön</x-ui.button>
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('stock.activity.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.input name="search" label="Ara" :value="$filters['search']" placeholder="Açıklama" />
                <x-ui.select
                    name="action"
                    label="İşlem Türü"
                    :selected="$filters['action']"
                    :options="filter_select_options($actionTypes)"
                />
                <x-ui.select
                    name="product_id"
                    label="Ürün"
                    :selected="$filters['product_id']"
                    :options="filter_select_options(collect($products)->mapWithKeys(fn ($p) => [$p['id'] => $p['label']])->all())"
                />
                <x-ui.select
                    name="user_id"
                    label="Kullanıcı"
                    :selected="$filters['user_id']"
                    :options="filter_select_options(collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())"
                />
                <x-ui.select
                    name="date_range"
                    label="Tarih"
                    :selected="$filters['date_range']"
                    :options="filter_select_options($dateRanges)"
                />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('stock.activity.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Kayıt
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ürün</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Stok Değişimi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kullanıcı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Açıklama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($activities as $activity)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400 sm:px-6">
                                {{ $activity['occurred_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' => in_array($activity['action'], ['stock_product_created', 'stock_quantity_increased', 'stock_returned'], true),
                                    'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => in_array($activity['action'], ['stock_product_updated'], true),
                                    'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' => in_array($activity['action'], ['stock_quantity_decreased', 'stock_assigned'], true),
                                ])>
                                    {{ $activity['action_label'] }}
                                </span>
                            </td>
                            <td class="max-w-[180px] px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $activity['product_name'] }}
                            </td>
                            <td class="px-4 py-3 font-mono text-sm">
                                <span @class([
                                    'text-emerald-600 dark:text-emerald-400' => ($activity['quantity_delta'] ?? 0) > 0,
                                    'text-rose-600 dark:text-rose-400' => ($activity['quantity_delta'] ?? 0) < 0,
                                    'text-gray-500 dark:text-slate-400' => ($activity['quantity_delta'] ?? 0) === 0 || $activity['quantity_delta'] === null,
                                ])>
                                    {{ $activity['quantity_delta_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $activity['user_name'] }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">
                                {{ $activity['ip_address'] }}
                            </td>
                            <td class="max-w-sm px-4 py-3 text-gray-600 dark:text-slate-400 sm:px-6">
                                <p class="line-clamp-2">{{ $activity['description'] }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Henüz stok işlem kaydı yok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination
            :total="$total"
            :page="$page"
            :per-page="$perPage"
            :last-page="$lastPage"
        />
    </x-ui.card>
</div>
@endsection
