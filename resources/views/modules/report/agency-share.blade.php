@extends('layouts.app')

@section('title', 'Acente Payı')

@section('breadcrumb')
    <a href="{{ route('reports.index') }}" class="hover:text-gray-900 dark:hover:text-white">Raporlar</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Acente Payı</span>
@endsection

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Acente Payı</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Acente bazlı komisyon ve ödeme özeti.</p>
    </div>
    @can('report.export')
        <x-ui.export-button :href="route('reports.agency-share.export', request()->query())" />
    @endcan
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.finance-stat-card title="Acente" :value="number_format($summary['count'])" icon="agency" accent="primary" />
    <x-ui.finance-stat-card title="Paket" :value="number_format($summary['packages'])" icon="chart" accent="blue" />
    <x-ui.finance-stat-card title="Gelir" :value="$summary['revenue_formatted']" icon="earning" accent="success" />
    <x-ui.finance-stat-card title="Acente Payı" :value="$summary['agency_payment_formatted']" icon="chart" accent="warning" />
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('reports.agency-share') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:p-6">
        <x-ui.select name="year" label="Yıl" :selected="(string) $filters['year']" :options="$years" />
        <x-ui.select name="month" label="Ay" :selected="(string) $filters['month']" :options="$months" />
        <div class="flex items-end gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('reports.agency-share') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Paket</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kayıt</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gelir</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente Payı</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <a href="{{ $row['url'] }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ $row['agency'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ number_format($row['couriers']) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ number_format($row['packages']) }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ number_format($row['lines']) }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $row['revenue_formatted'] }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $row['agency_payment_formatted'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
