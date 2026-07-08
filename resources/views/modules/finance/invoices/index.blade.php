@extends('layouts.app')

@section('title', 'Faturalar')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Faturalar</span>
@endsection

@section('content')
<div x-data="financeInvoicePage()" @finance-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Faturalar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelere ait tüm faturaları yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Fatura
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                Toplu Fatura Oluştur
            </x-ui.button>
            <x-ui.export-button :href="route('finance.invoices.export', request()->query())" />
            <x-ui.button type="button" variant="secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                PDF'e Aktar
            </x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
        <x-ui.finance-stat-card title="Toplam Fatura (KDV Hariç)" :value="money_excl_vat($summary['total_invoice'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Bu Ay Kesilen (KDV Hariç)" :value="money_excl_vat($summary['this_month_issued'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Tahsil Edilen (KDV Hariç)" :value="money_excl_vat($summary['collected_amount'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Bekleyen (KDV Hariç)" :value="money_excl_vat($summary['pending_amount'])" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="İptal Edilen" :value="number_format($summary['cancelled_count'])" icon="chart" accent="danger" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.invoices.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />

                <x-ui.select name="invoice_type" label="Fatura Türü" :selected="$filters['invoice_type']"
                    :options="array_merge(['all' => 'Tümü'], $invoiceTypes)" />

                <x-ui.select name="invoice_status" label="Fatura Durumu" :selected="$filters['invoice_status']"
                    :options="array_merge(['all' => 'Tümü'], $invoiceStatuses)" />

                <x-ui.select name="collection_status" label="Tahsilat Durumu" :selected="$filters['collection_status']"
                    :options="array_merge(['all' => 'Tümü'], $collectionStatuses)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.invoices.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> fatura kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1400px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Fatura No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hakediş Dönemi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Fatura Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Vade Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar (KDV Hariç)</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">KDV</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tahsilat Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Fatura Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($invoices as $invoice)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'opacity-60' => $invoice['invoice_status'] === 'cancelled',
                        ])>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs sm:px-6">
                                <a href="{{ route('finance.invoices.show', $invoice['id']) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ $invoice['reference'] }}
                                </a>
                            </td>
                            <td class="max-w-[180px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white" title="{{ $invoice['business_name'] }}">
                                {{ $invoice['business_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $invoice['earning_period_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $invoice['invoice_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $invoice['due_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ $invoice['subtotal_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">
                                {{ $invoice['vat_amount_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.invoice-collection-status-badge :status="$invoice['collection_status']" />
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.invoice-status-badge :status="$invoice['invoice_status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.invoice-row-actions :invoice="$invoice" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun fatura kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.invoices.partials.create-modal')
    @include('modules.finance.invoices.partials.bulk-modal')
</div>
@endsection
