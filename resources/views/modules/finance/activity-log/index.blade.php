@extends('layouts.app')

@section('title', 'Finans Hareket Geçmişi')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Hareket Geçmişi</span>
@endsection

@section('content')
<div x-data="financeActivityLogPage(@js($logsForModal))" @open-activity-detail.window="openDetail($event)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Finans Hareket Geçmişi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Finans modülünde gerçekleştirilen tüm işlemleri görüntüleyin.
            </p>
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                Bu kayıtlar salt okunurdur. Denetim amaçlıdır; düzenlenemez veya silinemez.
            </p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="Toplam Hareket" :value="number_format($summary['total'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Bugünkü Hareket" :value="number_format($summary['today'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bu Hafta" :value="number_format($summary['this_week'])" icon="chart" accent="blue" />
        <x-ui.finance-stat-card title="Bu Ay" :value="number_format($summary['this_month'])" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Kritik İşlem Sayısı" :value="number_format($summary['critical'])" icon="chart" accent="danger" />
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('finance.activity-log.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <x-ui.select name="action_type" label="İşlem Türü" :selected="$filters['action_type']"
                    :options="array_merge(['all' => 'Tümü'], $actionTypes)" />

                <x-ui.select name="module" label="Modül" :selected="$filters['module']"
                    :options="array_merge(['all' => 'Tümü'], $modules)" />

                <x-ui.select name="user_id" label="Kullanıcı" :selected="$filters['user_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />

                <x-ui.select name="current_account" label="Cari" :selected="$filters['current_account']"
                    :options="array_merge(['all' => 'Tümü'], collect($currentAccounts)->mapWithKeys(fn ($c) => [$c => $c])->all())" />

                <x-ui.input type="text" name="reference" label="İşlem No" :value="$filters['reference']" placeholder="GLR-2026-000001" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.activity-log.index') }}" variant="secondary">Temizle</x-ui.button>
                <x-ui.export-button :href="route('finance.activity-log.export', request()->query())" />
                <x-ui.button type="button" variant="secondary">PDF'e Aktar</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> hareket kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Modül</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Cari</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlemi Yapan</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP Adresi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($logs as $log)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white sm:px-6">{{ $log['date_formatted'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $log['time_formatted'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $log['module_label'] }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $log['action_type_label'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-primary-600 dark:text-primary-400">{{ $log['reference'] }}</td>
                            <td class="max-w-[180px] truncate px-4 py-3 text-gray-600 dark:text-slate-300" title="{{ $log['current_account_name'] }}">{{ $log['current_account_name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $log['user_name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $log['ip_address'] }}</td>
                            <td class="px-4 py-3">
                                <x-finance.activity-status-badge :status="$log['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.activity-row-actions :log="$log" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun hareket kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.activity-log.partials.detail-modal')
</div>
@endsection
