@extends('layouts.app')

@section('title', 'Sözleşme Vadeleri')


@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sözleşme Vadeleri</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Yakında bitecek ve gecikmiş işletme sözleşmeleri.</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-ui.finance-stat-card title="Toplam" :value="number_format($summary['total'])" icon="contract" accent="primary" />
    <x-ui.finance-stat-card title="30 Gün İçinde" :value="number_format($summary['expiring_soon'])" icon="chart" accent="warning" />
    <x-ui.finance-stat-card title="Gecikmiş" :value="number_format($summary['overdue'])" icon="report" accent="danger" />
</div>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Sözleşme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bitiş Tarihi</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Durum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <a href="{{ $row['url'] }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ $row['business_name'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['title'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['end_date_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-rose-100 text-rose-800 dark:bg-rose-500/25 dark:text-rose-200' => $row['is_overdue'],
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200' => ! $row['is_overdue'],
                            ])>
                                {{ $row['delay_label'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">Yakın tarihte bitecek sözleşme yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
