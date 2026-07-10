@extends('layouts.app')

@section('title', 'Ödemeler')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Ödemeler</span>
@endsection

@section('content')
<div x-data="financePaymentPage(@js($recipientsByType))" @finance-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Ödemeler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kurye, acente ve diğer cari hesaplara yapılan ödemeleri yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Ödeme
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                Toplu Ödeme
            </x-ui.button>
            <x-ui.export-button :href="route('finance.payments.export', request()->query())" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Ödeme" :value="money_excl_vat($summary['total_payment'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Bu Ay Yapılan Ödeme" :value="money_excl_vat($summary['this_month_payment'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bekleyen Ödemeler" :value="money_excl_vat($summary['pending_payment'])" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Bugün Yapılan Ödeme" :value="money_excl_vat($summary['today_payment'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Kurye Ödemeleri" :value="money_excl_vat($summary['courier_payment'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Acente Ödemeleri" :value="money_excl_vat($summary['agency_payment'])" icon="agency" accent="danger" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.payments.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select name="recipient_type" label="Alıcı Türü" :selected="$filters['recipient_type']"
                    :options="array_merge(['all' => 'Tümü'], $recipientTypes)" />

                <x-ui.select name="recipient_id" label="Alıcı" :selected="$filters['recipient_id']"
                    :options="array_merge(['all' => 'Tümü'], $recipientOptions)" />

                <x-ui.select name="payment_status" label="Ödeme Durumu" :selected="$filters['payment_status']"
                    :options="array_merge(['all' => 'Tümü'], $paymentStatuses)" />

                <x-ui.select name="payment_method" label="Ödeme Yöntemi" :selected="$filters['payment_method']"
                    :options="array_merge(['all' => 'Tümü'], $paymentMethods)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.payments.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> ödeme kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1300px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 sm:px-6">
                            <input
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                @change="toggleSelectAll(@js(collect($payments)->where('can_update', true)->pluck('id')->values()->all()))"
                            >
                        </th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Alıcı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Alıcı Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hakediş No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Yöntemi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Ödenecek Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Ödenen Tutar</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($payments as $payment)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'opacity-60' => ! $payment['is_active'],
                        ])>
                            <td class="whitespace-nowrap px-4 py-3 sm:px-6">
                                @if ($payment['can_update'])
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                        :checked="isSelected({{ $payment['id'] }})"
                                        @change="toggleSelect({{ $payment['id'] }})"
                                    >
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                <a href="{{ route('finance.payments.show', $payment['id']) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ $payment['reference'] }}
                                </a>
                            </td>
                            <td class="max-w-[180px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white" title="{{ $payment['recipient_name'] }}">
                                {{ $payment['recipient_name'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $payment['recipient_type_label'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">
                                {{ $payment['earning_reference_display'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $payment['payment_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $payment['payment_method_label'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                {{ $payment['total_amount_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-emerald-600 dark:text-emerald-400">
                                {{ $payment['paid_amount_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.payment-status-badge :status="$payment['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.payment-row-actions :payment="$payment" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun ödeme kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.payments.partials.create-modal')
    @include('modules.finance.payments.partials.bulk-modal')
</div>
@endsection
