@extends('layouts.app')

@section('title', 'Karlılık Analizi')

@php
    $dateRangeLabel = $dateRanges[$filters['date_range']] ?? 'Bu Ay';
    $hasBusinessRows = count($business_table) > 0;
    $hasAgencyRows = count($agency_table) > 0;
    $hasCourierRows = count($courier_table) > 0;
@endphp

@section('content')
<div
    x-data="financeProfitabilityPage({ activeTab: @js(request()->query('tab', 'overview')) })"
    data-charts='@json($charts)'
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Karlılık Analizi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Seçilen dönemdeki gelir, gider ve net kârı işletme · kurye · acente bazında görün.
                <span class="font-medium text-gray-700 dark:text-slate-300">Dönem: {{ $dateRangeLabel }}</span>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.export-button :href="route('finance.profitability.export', request()->query())" />
            <x-ui.export-button :href="route('finance.profitability.export-pdf', request()->query())" label="PDF Raporu" />
        </div>
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('finance.profitability.index') }}" class="p-4 sm:p-6">
            <input type="hidden" name="tab" :value="activeTab">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']" :options="$dateRanges" />
                <x-ui.select name="business_id" label="İşletme" :selected="$filters['business_id']"
                    :options="filter_select_options(collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())" />
                <x-ui.select name="courier_id" label="Kurye" :selected="$filters['courier_id']"
                    :options="filter_select_options(collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())" />
                <x-ui.select name="agency_id" label="Acente" :selected="$filters['agency_id']"
                    :options="filter_select_options(collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())" />
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select name="city" label="İl" :selected="$filters['city']"
                    :options="filter_select_options(collect($cities)->mapWithKeys(fn ($c) => [$c => $c])->all())" />
                <x-ui.select name="pricing_model" label="Çalışma Modeli" :selected="$filters['pricing_model']"
                    :options="filter_select_options($pricingModels)" />
                <x-ui.select name="profit_margin" label="Kâr Marjı" :selected="$filters['profit_margin']"
                    :options="$profitMarginFilters" />
                <div class="flex items-end gap-2">
                    <x-ui.button type="submit" class="w-full sm:w-auto">Filtrele</x-ui.button>
                    <x-ui.button href="{{ route('finance.profitability.index') }}" variant="secondary" class="w-full sm:w-auto">Temizle</x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Gelir" :value="$kpis['total_revenue_formatted']" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Toplam Gider" :value="$kpis['total_expense_formatted']" icon="chart" accent="danger" />
        <x-ui.finance-stat-card
            title="Net Kâr"
            :value="$kpis['net_profit_formatted']"
            icon="earning"
            :accent="$kpis['net_profit'] >= 0 ? 'success' : 'danger'"
        />
        <x-ui.finance-stat-card
            title="Kâr Marjı"
            :value="$kpis['profit_margin_formatted']"
            icon="chart"
            :accent="$kpis['net_profit'] >= 0 ? 'violet' : 'danger'"
        />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-medium text-gray-500 dark:text-slate-400">Paket Başına Ort. Kâr</p>
            <p class="mt-1 text-base font-semibold tabular-nums text-gray-900 dark:text-white">{{ $kpis['profit_per_package_formatted'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-medium text-gray-500 dark:text-slate-400">En Karlı İşletme</p>
            <p class="mt-1 truncate text-base font-semibold text-gray-900 dark:text-white" title="{{ $kpis['top_business_name'] }}">{{ $kpis['top_business_name'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-medium text-gray-500 dark:text-slate-400">En Karlı Acente</p>
            <p class="mt-1 truncate text-base font-semibold text-gray-900 dark:text-white" title="{{ $kpis['top_agency_name'] }}">{{ $kpis['top_agency_name'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-medium text-gray-500 dark:text-slate-400">En Karlı Kurye Operasyonu</p>
            <p class="mt-1 truncate text-base font-semibold text-gray-900 dark:text-white" title="{{ $kpis['top_operation_name'] }}">{{ $kpis['top_operation_name'] }}</p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="Gelir / Gider / Kâr" class="xl:col-span-2">
            <div id="profitability-chart-trend" class="min-h-[320px]"></div>
        </x-ui.card>
        <x-ui.card title="Gelir Dağılımı">
            <div id="profitability-chart-revenue-distribution" class="min-h-[320px]"></div>
        </x-ui.card>
        <x-ui.card title="İşletme Bazlı Kârlılık" class="xl:col-span-2">
            <div id="profitability-chart-business" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="İl Bazlı Kârlılık">
            <div id="profitability-chart-city" class="min-h-[300px]"></div>
        </x-ui.card>
        <x-ui.card title="Acente Bazlı Kârlılık" class="xl:col-span-3">
            <div id="profitability-chart-agency" class="min-h-[280px]"></div>
        </x-ui.card>
    </div>

    <div class="mb-4 border-b border-gray-200 dark:border-slate-700">
        <nav class="-mb-px flex flex-wrap gap-1" aria-label="Karlılık sekmeleri">
            <button type="button" @click="setTab('overview')"
                :class="activeTab === 'overview' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-slate-400'"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition-colors">
                Özet Sıralama
            </button>
            <button type="button" @click="setTab('businesses')"
                :class="activeTab === 'businesses' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-slate-400'"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition-colors">
                İşletme Karlılık Tablosu
            </button>
            <button type="button" @click="setTab('agencies')"
                :class="activeTab === 'agencies' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-slate-400'"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition-colors">
                Acente Karlılık Tablosu
            </button>
            <button type="button" @click="setTab('couriers')"
                :class="activeTab === 'couriers' ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-slate-400'"
                class="-mb-px border-b-2 px-4 py-2.5 text-sm font-medium transition-colors">
                Kurye Maliyet Tablosu
            </button>
        </nav>
    </div>

    <div x-show="activeTab === 'overview'" x-cloak class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <x-ui.card :padding="false" title="En Karlı İşletmeler">
            @if (count($top_businesses) === 0)
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde işletme kârı yok.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($top_businesses as $index => $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business_name'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :padding="false" title="En Düşük Karlı İşletmeler">
            @if (count($bottom_businesses) === 0)
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde işletme kârı yok.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($bottom_businesses as $index => $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <span class="mr-2 text-xs font-bold text-red-500">{{ $index + 1 }}.</span>
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business_name'] }}</span>
                            </div>
                            <span @class([
                                'shrink-0 text-sm font-semibold tabular-nums',
                                'text-emerald-600 dark:text-emerald-400' => $item['net_profit'] >= 0,
                                'text-red-600 dark:text-red-400' => $item['net_profit'] < 0,
                            ])>{{ $item['net_profit_formatted'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :padding="false" title="En Karlı Acenteler">
            @if (count($top_agencies) === 0)
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde acente kârı yok.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($top_agencies as $index => $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['agency_name'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :padding="false" title="En Düşük Karlı Acenteler">
            @if (count($bottom_agencies) === 0)
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde acente kârı yok.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($bottom_agencies as $index => $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <span class="mr-2 text-xs font-bold text-red-500">{{ $index + 1 }}.</span>
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['agency_name'] }}</span>
                            </div>
                            <span @class([
                                'shrink-0 text-sm font-semibold tabular-nums',
                                'text-emerald-600 dark:text-emerald-400' => $item['net_profit'] >= 0,
                                'text-amber-600 dark:text-amber-400' => $item['net_profit'] < 0,
                            ])>{{ $item['net_profit_formatted'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :padding="false" title="En Karlı Operasyonlar" class="xl:col-span-2">
            @if (count($top_operations) === 0)
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde operasyon kaydı yok.</p>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach ($top_operations as $index => $item)
                        <li class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
                            <div class="min-w-0">
                                <span class="mr-2 text-xs font-bold text-primary-600 dark:text-primary-400">{{ $index + 1 }}.</span>
                                <span class="truncate text-sm font-medium text-gray-900 dark:text-white" title="{{ $item['operation_label'] }}">{{ $item['operation_label'] }}</span>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $item['net_profit_formatted'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <div x-show="activeTab === 'businesses'" x-cloak>
        <x-ui.card :padding="false" title="İşletme Karlılık Tablosu">
            @if (! $hasBusinessRows)
                <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">Filtrelere uygun işletme kaydı yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1100px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Paket</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Gelir</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Kurye Maliyeti</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Acente Maliyeti</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Diğer Gider</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Net Kâr</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Kâr Marjı</th>
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
                                    <td @class([
                                        'px-4 py-3 text-right font-semibold tabular-nums',
                                        'text-emerald-600 dark:text-emerald-400' => $row['net_profit'] >= 0,
                                        'text-red-600 dark:text-red-400' => $row['net_profit'] < 0,
                                    ])>{{ $row['net_profit_formatted'] }}</td>
                                    <td class="px-4 py-3 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $row['profit_margin_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div x-show="activeTab === 'agencies'" x-cloak>
        <x-ui.card :padding="false" title="Acente Karlılık Tablosu">
            @if (! $hasAgencyRows)
                <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">Filtrelere uygun acente kaydı yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Paket</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Hakediş</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Maliyet</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Net Kâr</th>
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
                                    <td @class([
                                        'px-4 py-3 text-right font-semibold tabular-nums',
                                        'text-emerald-600 dark:text-emerald-400' => $row['net_profit'] >= 0,
                                        'text-red-600 dark:text-red-400' => $row['net_profit'] < 0,
                                    ])>{{ $row['net_profit_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.card>
    </div>

    <div x-show="activeTab === 'couriers'" x-cloak>
        <x-ui.card :padding="false" title="Kurye Maliyet Tablosu">
            @if (! $hasCourierRows)
                <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">Filtrelere uygun kurye kaydı yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Paket</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Hakediş</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Ek Ödeme</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Kesinti</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Toplam Maliyet</th>
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
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
