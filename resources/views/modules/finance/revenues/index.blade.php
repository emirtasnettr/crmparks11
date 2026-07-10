@extends('layouts.app')

@section('title', 'Gelirler')


@section('content')
<div x-data="financeRevenuePage()" @finance-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gelirler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelerden elde edilen tüm gelir kayıtlarını yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Gelir
            </x-ui.button>
            <x-ui.export-button :href="route('finance.revenues.export', request()->query())" />
            <x-ui.export-button :href="route('finance.revenues.export-pdf', request()->query())" label="PDF Raporu" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="Toplam Gelir" :value="money_excl_vat($summary['total_revenue'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bu Ay Geliri" :value="money_excl_vat($summary['this_month_revenue'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Tahsil Edilen" :value="money_excl_vat($summary['collected_amount'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Bekleyen Tahsilat" :value="money_excl_vat($summary['pending_collection'])" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Ortalama İşletme Geliri" :value="money_excl_vat($summary['average_per_business'])" icon="building" accent="violet" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.revenues.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />

                <x-ui.select name="revenue_type" label="Gelir Türü" :selected="$filters['revenue_type']"
                    :options="array_merge(['all' => 'Tümü'], $revenueTypes)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />

                <x-ui.select name="collection_status" label="Ödeme Durumu" :selected="$filters['collection_status']"
                    :options="array_merge(['all' => 'Tümü'], $collectionStatuses)" />

                <x-ui.select name="invoice_status" label="Fatura Durumu" :selected="$filters['invoice_status']"
                    :options="array_merge(['all' => 'Tümü'], $invoiceStatuses)" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.revenues.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> gelir kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1300px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Gelir No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gelir Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hakediş Dönemi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Fatura No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tahsil Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tahsil Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Oluşturulma Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($revenues as $revenue)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300 sm:px-6">
                                <a href="{{ route('finance.revenues.show', $revenue['id']) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ $revenue['reference'] }}
                                </a>
                            </td>
                            <td class="max-w-[200px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $revenue['business_name'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.revenue-type-badge :type="$revenue['revenue_type']" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $revenue['period_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">
                                {{ $revenue['invoice_no_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ $revenue['amount_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.collection-status-badge :status="$revenue['collection_status']" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $revenue['collection_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $revenue['created_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.revenue-row-actions :revenue="$revenue" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun gelir kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.revenues.partials.create-modal')
</div>
@endsection
