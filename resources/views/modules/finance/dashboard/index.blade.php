@extends('layouts.app')

@section('title', 'Finans Dashboard')


@section('content')
<div
    x-data="financeDashboardPage()"
    data-charts='@json($charts)'
    data-period="{{ $filters['period'] }}"
>
    {{-- Başlık & Tarih Filtresi --}}
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Finans Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Tüm finansal süreçleri tek ekrandan takip edin.
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

            <div
                x-show="showCustomRange"
                x-collapse
                class="w-full sm:w-auto"
            >
                <div class="flex flex-wrap items-end gap-2 rounded-lg border border-gray-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Başlangıç</label>
                        <input
                            type="date"
                            x-model="customStart"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Bitiş</label>
                        <input
                            type="date"
                            x-model="customEnd"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                    </div>
                    <x-ui.button type="button" size="sm" @click="applyCustomRange()">Uygula</x-ui.button>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Gelir" :value="$kpis['total_revenue_formatted']" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Toplam Gider" :value="$kpis['total_expense_formatted']" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Net Kâr" :value="$kpis['net_profit_formatted']" icon="earning" accent="primary" />
        <x-ui.finance-stat-card title="Kâr Marjı" :value="$kpis['profit_margin_formatted']" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Bekleyen Tahsilat" :value="$kpis['pending_collection_formatted']" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Bekleyen Ödeme" :value="$kpis['pending_payment_formatted']" icon="chart" accent="warning" />
        <x-ui.finance-stat-card title="Bu Ay Hakediş" :value="number_format($kpis['monthly_earnings_count'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Cari Hesap" :value="number_format($kpis['active_accounts'])" icon="building" accent="primary" />
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Ana İçerik --}}
        <div class="space-y-6 xl:col-span-2">
            {{-- Grafikler --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Aylık Gelir / Gider Grafiği">
                    <div id="finance-chart-revenue-expense" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Aylık Kâr Analizi">
                    <div id="finance-chart-profit" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Gelir Dağılımı — İşletmelere göre">
                    <div id="finance-chart-revenue-distribution" class="min-h-[280px]"></div>
                </x-ui.card>

                <x-ui.card title="Gider Dağılımı — Kurye · Acente · Diğer">
                    <div id="finance-chart-expense-distribution" class="min-h-[280px]"></div>
                </x-ui.card>
            </div>

            {{-- Son Hareketler --}}
            <x-ui.card :padding="false" title="Son Hareketler">
                <p class="border-b border-gray-200 px-6 pb-4 text-xs text-gray-500 dark:border-slate-700 dark:text-slate-400">
                    Son 15 finans hareketi
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Cari</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach ($recent_transactions as $transaction)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300 sm:px-6">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $transaction['occurred_at'] }}</span>
                                        <span class="ml-1 text-xs text-gray-400">{{ $transaction['occurred_at_time'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $transaction['type'] }}</td>
                                    <td class="max-w-[200px] truncate px-4 py-3 text-gray-600 dark:text-slate-300" title="{{ $transaction['account'] }}">
                                        {{ $transaction['account'] }}
                                    </td>
                                    <td @class([
                                        'px-4 py-3 text-right font-medium tabular-nums',
                                        'text-emerald-600 dark:text-emerald-400' => ! $transaction['is_negative'],
                                        'text-red-600 dark:text-red-400' => $transaction['is_negative'],
                                    ])>
                                        {{ $transaction['amount_formatted'] }}
                                    </td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <x-finance.transaction-status-badge :status="$transaction['status']" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Bekleyen Tablolar --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card :padding="false" title="Bekleyen Tahsilatlar">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Fatura</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Vade</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gecikme</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach ($pending_collections as $collection)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                        <td class="max-w-[140px] truncate px-4 py-3 text-gray-900 dark:text-white" title="{{ $collection['business'] }}">
                                            {{ $collection['business'] }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $collection['invoice'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $collection['due_date_formatted'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-gray-900 dark:text-white">
                                            {{ $collection['amount_formatted'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($collection['is_overdue'])
                                                <x-ui.badge variant="danger">{{ $collection['delay_label'] }}</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="secondary">{{ $collection['delay_label'] }}</x-ui.badge>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>

                <x-ui.card :padding="false" title="Bekleyen Ödemeler">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye / Acente</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hakediş</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ödeme Tarihi</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                @foreach ($pending_payments as $payment)
                                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                        <td class="max-w-[140px] truncate px-4 py-3 text-gray-900 dark:text-white" title="{{ $payment['payee'] }}">
                                            <span class="block truncate">{{ $payment['payee'] }}</span>
                                            <span class="text-xs text-gray-400">{{ $payment['type'] }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $payment['reference'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $payment['payment_date_formatted'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-gray-900 dark:text-white">
                                            {{ $payment['amount_formatted'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-finance.payment-status-badge :status="$payment['status']" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- Sağ Panel: Bugünkü Özet --}}
        <div class="space-y-6">
            <x-ui.card title="Bugünkü Özet" class="xl:sticky xl:top-6">
                <div class="space-y-4">
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Bugünkü Gelir</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $today_summary['revenue_formatted'] }}</p>
                    </div>

                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-red-700 dark:text-red-400">Bugünkü Gider</p>
                        <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">{{ $today_summary['expense_formatted'] }}</p>
                    </div>

                    <div class="rounded-lg border border-primary-200 bg-primary-50 p-4 dark:border-primary-800/50 dark:bg-primary-900/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-primary-700 dark:text-primary-400">Bugünkü Kâr</p>
                        <p class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $today_summary['profit_formatted'] }}</p>
                    </div>

                    <div class="border-t border-gray-200 pt-4 dark:border-slate-700">
                        <dl class="space-y-3">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-slate-400">Yeni Hakediş</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $today_summary['new_earnings'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-slate-400">Yeni Fatura</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $today_summary['new_invoices'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-600 dark:text-slate-400">Bekleyen Onay</dt>
                                <dd>
                                    <x-ui.badge variant="warning">{{ $today_summary['pending_approvals'] }}</x-ui.badge>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
