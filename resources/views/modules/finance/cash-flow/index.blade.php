@extends('layouts.app')

@section('title', 'Nakit Akışı')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Nakit Akışı</span>
@endsection

@section('content')
<div
    x-data="financeCashFlowPage()"
    data-charts='@json($charts)'
    data-period="{{ $filters['period'] }}"
>
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Nakit Akışı</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Giren ve çıkan tüm nakit hareketlerini analiz edin.
            </p>
        </div>

        <div class="flex flex-col items-stretch gap-3 sm:items-end">
            <div class="inline-flex flex-wrap rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-slate-700 dark:bg-slate-800/50">
                @foreach ($periods as $key => $label)
                    @if ($key !== 'custom')
                        <button
                            type="button"
                            @click="selectPeriod('{{ $key }}')"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' => $filters['period'] === $key,
                                'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white' => $filters['period'] !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @else
                        <button
                            type="button"
                            @click="showCustomRange = !showCustomRange"
                            @class([
                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                'bg-white text-gray-900 shadow-sm dark:bg-slate-700 dark:text-white' => $filters['period'] === 'custom',
                                'text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white' => $filters['period'] !== 'custom',
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endif
                @endforeach
            </div>

            <div x-show="showCustomRange" x-collapse class="w-full sm:w-auto">
                <div class="flex flex-wrap items-end gap-2 rounded-lg border border-gray-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Başlangıç</label>
                        <input type="date" x-model="customStart" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Bitiş</label>
                        <input type="date" x-model="customEnd" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    </div>
                    <x-ui.button type="button" size="sm" @click="applyCustomRange()">Uygula</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Kasaya Giren" :value="$kpis['cash_in_formatted']" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Kasadan Çıkan" :value="$kpis['cash_out_formatted']" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Net Nakit" :value="$kpis['net_cash_formatted']" icon="earning" accent="primary" />
        <x-ui.finance-stat-card title="Bekleyen Tahsilatlar" :value="$kpis['pending_collections_formatted']" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Bekleyen Ödemeler" :value="$kpis['pending_payments_formatted']" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Nakit Değişim Oranı" :value="$kpis['cash_change_rate_formatted']" icon="chart" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Nakit Akış Grafiği">
                    <div id="cashflow-chart-balance" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Günlük Nakit Hareketi">
                    <div id="cashflow-chart-daily" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Gelir / Gider Dağılımı">
                    <div id="cashflow-chart-distribution" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Bekleyen Tahsilatlar vs Bekleyen Ödemeler">
                    <div id="cashflow-chart-pending" class="min-h-[280px]"></div>
                </x-ui.card>
            </div>

            <x-ui.card :padding="false" title="Nakit Hareketleri">
                <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        <span class="text-lg font-bold">{{ number_format($total) }}</span> nakit hareketi listeleniyor
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1200px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem No</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem Türü</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Cari</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açıklama</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Giren</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Çıkan</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Bakiye</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kaynak</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlemi Yapan</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @forelse ($transactions as $transaction)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300 sm:px-6">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $transaction['date_formatted'] }}</span>
                                        <span class="ml-1 text-xs text-gray-400">{{ $transaction['time_formatted'] }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ $transaction['reference'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $transaction['transaction_type_label'] }}</td>
                                    <td class="max-w-[160px] truncate px-4 py-3 text-gray-900 dark:text-white" title="{{ $transaction['current_account_name'] }}">
                                        {{ $transaction['current_account_name'] }}
                                    </td>
                                    <td class="max-w-[200px] truncate px-4 py-3 text-gray-600 dark:text-slate-300" title="{{ $transaction['description'] }}">
                                        {{ $transaction['description'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-emerald-600 dark:text-emerald-400">
                                        {{ $transaction['amount_in_formatted'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-red-600 dark:text-red-400">
                                        {{ $transaction['amount_out_formatted'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-gray-900 dark:text-white">
                                        {{ $transaction['balance_formatted'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $transaction['source_type_label'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $transaction['performed_by'] }}</td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <x-finance.cash-flow-row-actions :transaction="$transaction" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                        Seçilen dönemde nakit hareketi bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Bugünkü Hareketler" class="xl:sticky xl:top-6">
                <div class="mb-4 rounded-lg border border-primary-200 bg-primary-50 p-4 dark:border-primary-800/50 dark:bg-primary-900/20">
                    <p class="text-xs font-medium uppercase tracking-wide text-primary-700 dark:text-primary-400">Toplam Hareket</p>
                    <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $sidebar['today_movements'] }}</p>
                </div>

                <dl class="space-y-4">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/20">
                        <dt class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Bugünkü Tahsilatlar</dt>
                        <dd class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $sidebar['today_collections_total_formatted'] }}</dd>
                        <p class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">{{ $sidebar['today_collections_count'] }} işlem</p>
                    </div>

                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
                        <dt class="text-xs font-medium uppercase tracking-wide text-red-700 dark:text-red-400">Bugünkü Ödemeler</dt>
                        <dd class="mt-1 text-lg font-bold text-red-700 dark:text-red-300">{{ $sidebar['today_payments_total_formatted'] }}</dd>
                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $sidebar['today_payments_count'] }} işlem</p>
                    </div>

                    @if ($sidebar['largest_collection'])
                        <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">En Büyük Tahsilat</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $sidebar['largest_collection']['amount_formatted'] }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ $sidebar['largest_collection']['cari'] }}</p>
                        </div>
                    @endif

                    @if ($sidebar['largest_payment'])
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">En Büyük Ödeme</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $sidebar['largest_payment']['amount_formatted'] }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ $sidebar['largest_payment']['cari'] }}</p>
                        </div>
                    @endif
                </dl>

                @if (count($sidebar['recent_today']) > 0)
                    <div class="mt-6 border-t border-gray-200 pt-4 dark:border-slate-700">
                        <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Son Hareketler</p>
                        <ul class="space-y-2">
                            @foreach ($sidebar['recent_today'] as $item)
                                <li class="flex items-start justify-between gap-2 text-sm">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-white">{{ $item['transaction_type_label'] }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ $item['time_formatted'] }} — {{ $item['current_account_name'] }}</p>
                                    </div>
                                    <span @class([
                                        'shrink-0 font-medium tabular-nums',
                                        'text-emerald-600 dark:text-emerald-400' => $item['amount_in'] > 0,
                                        'text-red-600 dark:text-red-400' => $item['amount_out'] > 0,
                                    ])>
                                        {{ $item['amount_in'] > 0 ? $item['amount_in_formatted'] : $item['amount_out_formatted'] }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
