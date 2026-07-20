@extends('layouts.app')

@section('title', 'Hareket Geçmişi')


@section('content')
<div x-data="agencyActivityPage()" @activity-detail.window="openDetail($event.detail)">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hareket Geçmişi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Acenteler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.
            </p>
        </div>

        <x-ui.export-button :href="route('agencies.activities.export', request()->query())" />
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Hareket" :value="number_format($summary['count'])" icon="contract" accent="blue" />
        <x-ui.finance-stat-card title="Bugünkü Hareket" :value="number_format($summary['today'])" icon="contract" accent="success" />
        <x-ui.finance-stat-card title="Bu Hafta" :value="number_format($summary['this_week'])" icon="contract" accent="primary" />
        <x-ui.finance-stat-card title="Bu Ay" :value="number_format($summary['this_month'])" icon="contract" accent="violet" />
    </div>

    {{-- Filtreler --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('agencies.activities.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select
                    name="agency_id"
                    label="Acente"
                    :selected="$filters['agency_id']"
                    :options="filter_select_options(collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())"
                />

                <x-ui.select
                    name="action"
                    label="İşlem Türü"
                    :selected="$filters['action']"
                    :options="filter_select_options($actionTypes)"
                />

                <x-ui.select
                    name="user_id"
                    label="İşlemi Yapan Kullanıcı"
                    :selected="$filters['user_id']"
                    :options="filter_select_options(collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())"
                />

                <x-ui.select
                    name="date_range"
                    label="Tarih Aralığı"
                    :selected="$filters['date_range']"
                    :options="filter_select_options($dateRanges)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('agencies.activities.index') }}" variant="secondary">Temizle</x-ui.button>
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
            <table class="w-full min-w-[1150px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlemi Yapan</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP Adresi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açıklama</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($activities as $activity)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400 sm:px-6">
                                {{ $activity['occurred_at_date'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">
                                {{ $activity['occurred_at_time'] }}
                            </td>
                            <td class="max-w-[200px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $activity['agency_name'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-agency.activity-type-badge :action="$activity['action']">
                                    {{ $activity['action_label'] }}
                                </x-agency.activity-type-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $activity['user_name'] }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $activity['ip_address'] }}</td>
                            <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-slate-400">
                                <p class="line-clamp-2">{{ $activity['description'] }}</p>
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-agency.activity-row-actions :activity="$activity" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun hareket kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.agency.activities.partials.detail-modal')
</div>
@endsection
