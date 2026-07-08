@extends('layouts.app')

@section('title', 'Giderler')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Giderler</span>
@endsection

@section('content')
<div x-data="financeExpensePage()" @finance-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Giderler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Şirkete ait tüm giderleri buradan yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Gider
            </x-ui.button>
            <x-ui.export-button :href="route('finance.expenses.export', request()->query())" />
            <x-ui.button type="button" variant="secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                PDF Raporu
            </x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Gider" :value="money_excl_vat($summary['total_expense'])" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Bu Ay Gideri" :value="money_excl_vat($summary['this_month_expense'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Ödenen Gider" :value="money_excl_vat($summary['paid_amount'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bekleyen Ödeme" :value="money_excl_vat($summary['pending_payment'])" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Kurye Gideri" :value="money_excl_vat($summary['courier_expense'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Acente Gideri" :value="money_excl_vat($summary['agency_expense'])" icon="agency" accent="blue" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.expenses.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select name="expense_type" label="Gider Türü" :selected="$filters['expense_type']"
                    :options="array_merge(['all' => 'Tümü'], $expenseTypes)" />

                <x-ui.select name="courier_id" label="Kurye" :selected="$filters['courier_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())" />

                <x-ui.select name="agency_id" label="Acente" :selected="$filters['agency_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />

                <x-ui.select name="payment_status" label="Ödeme Durumu" :selected="$filters['payment_status']"
                    :options="array_merge(['all' => 'Tümü'], $paymentStatuses)" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.expenses.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> gider kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Gider No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gider Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye / Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açıklama</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Oluşturulma Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($expenses as $expense)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                                <a href="{{ route('finance.expenses.show', $expense['id']) }}" class="font-mono text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ $expense['reference'] }}
                                </a>
                                <div class="mt-1">
                                    <x-finance.expense-source-badge :source="$expense['source']" />
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.expense-type-badge :type="$expense['expense_type']" />
                            </td>
                            <td class="max-w-[160px] truncate px-4 py-3 text-gray-900 dark:text-white" title="{{ $expense['payee_display'] }}">
                                {{ $expense['payee_display'] }}
                            </td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-gray-600 dark:text-slate-300" title="{{ $expense['description'] }}">
                                {{ $expense['description'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ $expense['amount_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.expense-payment-status-badge :status="$expense['payment_status']" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $expense['payment_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $expense['created_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.expense-row-actions :expense="$expense" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun gider kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.expenses.partials.create-modal')
</div>
@endsection
