@extends('layouts.app')

@section('title', 'Tahsilat Yaşlandırma')

@section('breadcrumb')
    <a href="{{ route('reports.index') }}" class="hover:text-gray-900 dark:hover:text-white">Raporlar</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Tahsilat Yaşlandırma</span>
@endsection

@section('content')
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tahsilat Yaşlandırma</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bekleyen ve geciken tahsilatların vade analizi.</p>
    </div>
    @can('report.export')
        <x-ui.export-button :href="route('reports.collections.export')" />
    @endcan
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <x-ui.finance-stat-card title="Toplam Kalan" :value="$summary['amount_formatted']" :subtitle="number_format($summary['count']).' kayıt'" icon="earning" accent="warning" />
    @foreach ($buckets as $bucket)
        <x-ui.finance-stat-card
            :title="$bucket['label']"
            :value="$bucket['amount_formatted']"
            :subtitle="number_format($bucket['count']).' kayıt'"
            icon="chart"
            accent="primary"
        />
    @endforeach
</div>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Referans</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Vade</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gecikme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Grup</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kalan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <a href="{{ $row['url'] }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ $row['business'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['reference'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['due_date_formatted'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['days_overdue'] }} gün</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['bucket_label'] }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $row['amount_formatted'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">Bekleyen tahsilat yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
