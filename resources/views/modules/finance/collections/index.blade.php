@extends('layouts.app')

@section('title', 'Tahsilatlar')


@section('content')
<div x-data="financeCollectionPage()" @finance-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tahsilatlar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelerden alınacak tüm tahsilatları yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Tahsilat
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                Toplu Tahsilat
            </x-ui.button>
            <x-ui.export-button :href="route('finance.collections.export', request()->query())" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Tahsilat" :value="money_excl_vat($summary['total_amount'])" icon="earning" accent="primary" />
        <x-ui.finance-stat-card title="Tahsil Edilen" :value="money_excl_vat($summary['collected_amount'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bekleyen Tahsilat" :value="money_excl_vat($summary['pending_amount'])" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Geciken Tahsilat" :value="money_excl_vat($summary['overdue_amount'])" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Bugün Tahsil Edilen" :value="money_excl_vat($summary['today_collected'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Bu Ay Tahsil Edilen" :value="money_excl_vat($summary['month_collected'])" icon="earning" accent="violet" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.collections.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />

                <x-ui.select name="collection_status" label="Tahsilat Durumu" :selected="$filters['collection_status']"
                    :options="array_merge(['all' => 'Tümü'], $collectionStatuses)" />

                <x-ui.select name="payment_method" label="Ödeme Yöntemi" :selected="$filters['payment_method']"
                    :options="array_merge(['all' => 'Tümü'], $paymentMethods)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />

                <x-ui.select name="due_date" label="Vade Tarihi" :selected="$filters['due_date']"
                    :options="$dueDateFilters" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.collections.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> tahsilat kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1400px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 sm:px-6">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @change="toggleSelectAll(@js(collect($collections)->where('can_update', true)->pluck('id')->values()->all()))"
                            >
                        </th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tahsilat No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gelir No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Fatura No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Vade Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tahsilat Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kalan Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Yöntemi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($collections as $collection)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                                @if ($collection['can_update'])
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        :checked="isSelected({{ $collection['id'] }})"
                                        @change="toggleSelect({{ $collection['id'] }})"
                                    >
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                <a href="{{ route('finance.collections.show', $collection['id']) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ $collection['reference'] }}
                                </a>
                            </td>
                            <td class="max-w-[180px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white" title="{{ $collection['business_name'] }}">
                                {{ $collection['business_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">
                                {{ $collection['revenue_reference_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">
                                {{ $collection['invoice_no_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $collection['due_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $collection['collection_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ $collection['total_amount_formatted'] }}
                            </td>
                            <td @class([
                                'whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums',
                                'text-red-600 dark:text-red-400' => $collection['remaining_amount'] > 0,
                                'text-emerald-600 dark:text-emerald-400' => $collection['remaining_amount'] <= 0,
                            ])>
                                {{ $collection['remaining_amount_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $collection['payment_method_label'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.collection-record-status-badge :status="$collection['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.collection-row-actions :collection="$collection" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun tahsilat kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.collections.partials.create-modal')
    @include('modules.finance.collections.partials.bulk-modal')
</div>
@endsection
