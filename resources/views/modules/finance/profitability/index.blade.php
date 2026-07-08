@extends('layouts.app')

@section('title', 'Karlılık Analizi')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Karlılık Analizi</span>
@endsection

@section('content')
<div x-data="financeProfitabilityPage()" data-charts='@json($charts)'>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Karlılık Analizi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletme, kurye ve acente bazlı kârlılığı analiz edin.
            </p>
        </div>
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('finance.profitability.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-7">
                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']" :options="$dateRanges" />
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />
                <x-ui.select name="courier_id" label="Kurye" :selected="$filters['courier_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())" />
                <x-ui.select name="agency_id" label="Acente" :selected="$filters['agency_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())" />
                <x-ui.select name="city" label="İl" :selected="$filters['city']"
                    :options="array_merge(['all' => 'Tümü'], collect($cities)->mapWithKeys(fn ($c) => [$c => $c])->all())" />
                <x-ui.select name="pricing_model" label="Çalışma Modeli" :selected="$filters['pricing_model']"
                    :options="array_merge(['all' => 'Tümü'], $pricingModels)" />
                <x-ui.select name="profit_margin" label="Kâr Marjı" :selected="$filters['profit_margin']"
                    :options="$profitMarginFilters" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.profitability.index') }}" variant="secondary">Temizle</x-ui.button>
                <x-ui.export-button :href="route('finance.profitability.export', request()->query())" />
                <x-ui.button type="button" variant="secondary">PDF Raporu</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-8">
        <x-ui.finance-stat-card title="Toplam Gelir" :value="$kpis['total_revenue_formatted']" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Toplam Gider" :value="$kpis['total_expense_formatted']" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Net Kâr" :value="$kpis['net_profit_formatted']" icon="earning" accent="primary" />
        <x-ui.finance-stat-card title="Kâr Marjı %" :value="$kpis['profit_margin_formatted']" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Paket Başına Ortalama Kâr" :value="$kpis['profit_per_package_formatted']" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="En Karlı İşletme" :value="$kpis['top_business_name']" icon="building" accent="success" />
        <x-ui.finance-stat-card title="En Karlı Acente" :value="$kpis['top_agency_name']" icon="agency" accent="warning" />
        <x-ui.finance-stat-card title="En Karlı Kurye Operasyonu" :value="$kpis['top_operation_name']" icon="courier" accent="primary" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card title="Gelir / Gider / Kâr">
            <div id="profitability-chart-trend" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="İşletme Bazlı Kârlılık">
            <div id="profitability-chart-business" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="Acente Bazlı Kârlılık">
            <div id="profitability-chart-agency" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="İl Bazlı Kârlılık">
            <div id="profitability-chart-city" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="Gelir Dağılımı" class="xl:col-span-2">
            <div id="profitability-chart-revenue-distribution" class="mx-auto min-h-[300px] max-w-xl"></div>
        </x-ui.card>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card :padding="false" title="İlk 10 En Karlı İşletme" class="xl:col-span-1">
            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                @foreach ($top_businesses as $index => $item)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business_name'] }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card :padding="false" title="İlk 10 En Karlı Acente">
            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                @foreach ($top_agencies as $index => $item)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['agency_name'] }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card :padding="false" title="İlk 10 En Karlı Operasyon">
            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                @foreach ($top_operations as $index => $item)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white" title="{{ $item['operation_label'] }}">{{ $item['operation_label'] }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card :padding="false" title="İlk 10 En Düşük Karlı İşletme">
            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                @foreach ($bottom_businesses as $index => $item)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <span class="mr-2 text-xs font-bold text-red-500">{{ $index + 1 }}.</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business_name'] }}</span>
                        </div>
                        <span @class([
                            'shrink-0 text-sm font-semibold',
                            'text-emerald-600 dark:text-emerald-400' => $item['net_profit'] >= 0,
                            'text-red-600 dark:text-red-400' => $item['net_profit'] < 0,
                        ])>{{ $item['net_profit_formatted'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card :padding="false" title="İlk 10 En Düşük Karlı Acente">
            <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                @foreach ($bottom_agencies as $index => $item)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                        <div class="min-w-0">
                            <span class="mr-2 text-xs font-bold text-red-500">{{ $index + 1 }}.</span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['agency_name'] }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold text-amber-600 dark:text-amber-400">{{ $item['net_profit_formatted'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    </div>

    <x-ui.card :padding="false" title="İşletme Karlılık Tablosu" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Paket Sayısı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Gelir</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kurye Maliyeti</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Acente Maliyeti</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Diğer Gider</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Net Kâr</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kâr Marjı</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($business_table as $row)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="max-w-[200px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $row['business_name'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">{{ number_format($row['package_count']) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">{{ $row['revenue_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-red-600 dark:text-red-400">{{ $row['courier_cost_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-amber-600 dark:text-amber-400">{{ $row['agency_cost_formatted'] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">{{ $row['other_expenses_formatted'] }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $row['net_profit_formatted'] }}</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $row['profit_margin_formatted'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card :padding="false" title="Acente Karlılık Tablosu">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kurye Sayısı</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Toplam Paket</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Toplam Hakediş</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Toplam Maliyet</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Net Kâr</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach ($agency_table as $row)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="max-w-[180px] truncate px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $row['agency_name'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">{{ $row['courier_count'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">{{ number_format($row['total_packages']) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">{{ $row['total_earning_formatted'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600 dark:text-red-400">{{ $row['total_cost_formatted'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $row['net_profit_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card :padding="false" title="Kurye Maliyet Tablosu">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Paket Sayısı</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Hakediş</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Ek Ödeme</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Kesinti</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Toplam Maliyet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach ($courier_table as $row)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $row['courier_name'] }}</td>
                                <td class="max-w-[160px] truncate px-4 py-3 text-gray-600 dark:text-slate-300">{{ $row['business_name'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-600 dark:text-slate-300">{{ number_format($row['package_count']) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">{{ $row['earning_formatted'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-amber-600 dark:text-amber-400">{{ $row['extra_payment_formatted'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-600 dark:text-red-400">{{ $row['deduction_formatted'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">{{ $row['total_cost_formatted'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
