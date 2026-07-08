@extends('layouts.app')

@section('title', 'Hakedişler')

@section('breadcrumb')
    <a href="{{ route('businesses.index') }}" class="hover:text-gray-900 dark:hover:text-white">İşletmeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Hakedişler</span>
@endsection

@section('content')
<div x-data="earningPage()">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hakedişler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelere ait hakedişleri buradan yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'single'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tekli Hakediş
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                Toplu Hakediş
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="activeModal = 'bulk'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Excel Yükle
            </x-ui.button>
        </div>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Hakediş" :value="money_excl_vat($summary['count'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Toplam Gelir" :value="number_format($summary['total_revenue'])" icon="chart" accent="success" />
        <x-ui.finance-stat-card title="Toplam Gider" :value="money_excl_vat($summary['total_expense'])" icon="earning" accent="danger" />
        <x-ui.finance-stat-card title="Toplam Kâr" :value="money_excl_vat($summary['total_profit'])" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Bekleyen Hakediş" :value="number_format($summary['pending_count'])" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Ödenen Hakediş" :value="number_format($summary['paid_count'])" icon="earning" accent="primary" />
    </div>

    {{-- Filtre --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('businesses.earnings.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />
                <x-ui.select name="courier_id" label="Kurye" :selected="$filters['courier_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())" />
                <x-ui.select name="agency_id" label="Acente" :selected="$filters['agency_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())" />
                <x-ui.select name="period_month" label="Ay" :selected="$filters['period_month']"
                    :options="array_merge(['all' => 'Tümü'], $months)" />
                <x-ui.select name="period_year" label="Yıl" :selected="$filters['period_year']"
                    :options="array_merge(['all' => 'Tümü'], [2026 => '2026', 2025 => '2025', 2024 => '2024'])" />
                <x-ui.select name="status" label="Hakediş Durumu" :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)" />
                <x-ui.select name="pricing_model" label="Çalışma Modeli" :selected="$filters['pricing_model']"
                    :options="array_merge(['all' => 'Tümü'], $pricingModels)" />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('businesses.earnings.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ money_excl_vat($total) }}</span> kayıt listeleniyor
            </p>
            <x-ui.export-button :href="route('businesses.earnings.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ay / Yıl</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Çalışma Modeli</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Paket</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">İşletmeden Gelir</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kurye Ödemesi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kâr</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($earnings as $earning)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="max-w-[160px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $earning['business_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $earning['courier_name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $earning['period_label'] }}</td>
                            <td class="px-4 py-3">
                                <x-business.pricing-badge :model="$earning['pricing_model']" />
                            </td>
                            <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                {{ $earning['pricing_model'] === 'per_package' ? number_format($earning['package_count']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                {{ number_format($earning['revenue']) }}
                            </td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                                {{ money_excl_vat($earning['courier_payment']) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-business.profit-display :amount="$earning['profit']" />
                            </td>
                            <td class="px-4 py-3">
                                <x-business.earning-status-badge :status="$earning['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-business.earning-row-actions :earning="$earning" />
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

    @include('modules.business.earnings.partials.single-modal')
    @include('modules.business.earnings.partials.bulk-modal')
</div>
@endsection
