@extends('layouts.app')

@section('title', 'Hakedişler')


@section('content')
<div x-data="courierEarningPage(@js(['openBulk' => $errors->has('file')]))">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hakedişler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kuryelere ait hakediş ve ödeme kayıtlarını yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'single'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Hakediş
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                Toplu Hakediş Oluştur
            </x-ui.button>
            <x-ui.export-button :href="route('couriers.earnings.export', request()->query())" />
        </div>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="Toplam Hakediş" :value="number_format($summary['count'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Toplam Ödenecek Tutar" :value="money_excl_vat($summary['total_payable'])" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Ödenen" :value="money_excl_vat($summary['paid_amount'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bekleyen" :value="number_format($summary['pending_count'])" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Bu Ay Hakedişi" :value="number_format($summary['this_month_count'])" icon="earning" accent="primary" />
    </div>

    {{-- Filtre --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.earnings.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                <x-ui.select name="courier_id" label="Kurye" :selected="$filters['courier_id']"
                    :options="filter_select_options(collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())" />
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="filter_select_options(collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />
                <x-ui.select name="agency_id" label="Acente" :selected="$filters['agency_id']"
                    :options="filter_select_options(collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())" />
                <x-ui.select name="period_month" label="Ay" :selected="$filters['period_month']"
                    :options="filter_select_options($months)" />
                <x-ui.select name="period_year" label="Yıl" :selected="$filters['period_year']"
                    :options="filter_select_options([2026 => '2026', 2025 => '2025', 2024 => '2024'])" />
                <x-ui.select name="payment_status" label="Ödeme Durumu" :selected="$filters['payment_status']"
                    :options="filter_select_options($paymentStatuses)" />
                <x-ui.select name="courier_type" label="Kurye Tipi" :selected="$filters['courier_type']"
                    :options="filter_select_options($courierTypes)" />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('couriers.earnings.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> kayıt listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Çalışma Modeli</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ay / Yıl</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Paket Sayısı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Hakediş Tutarı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kesinti</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Net Ödeme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($earnings as $earning)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="max-w-[160px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $earning['courier_name'] }}</p>
                                <x-business.courier-type-badge :type="$earning['courier_type']" class="mt-1" />
                            </td>
                            <td class="max-w-[180px] px-4 py-3">
                                <p class="line-clamp-2 text-gray-600 dark:text-slate-400">{{ $earning['business_name'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                @if (! empty($earning['pricing_model']))
                                    <x-business.pricing-badge :model="$earning['pricing_model']" />
                                @else
                                    <span class="text-sm text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $earning['period_label'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ $earning['package_count'] > 0 ? number_format($earning['package_count']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ $earning['worked_hours'] > 0 ? number_format($earning['worked_hours'], 2, ',', '.').' sa' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                {{ money_excl_vat($earning['earning_amount']) }}
                            </td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                                {{ $earning['deduction'] > 0 ? '−' . money_excl_vat($earning['deduction']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                {{ money_excl_vat($earning['net_payment']) }}
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.payment-status-badge :status="$earning['payment_status']" />
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $earning['payment_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.earning-row-actions :earning="$earning" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun hakediş bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.courier.earnings.partials.single-modal')
    @include('modules.courier.earnings.partials.bulk-modal')
</div>
@endsection
