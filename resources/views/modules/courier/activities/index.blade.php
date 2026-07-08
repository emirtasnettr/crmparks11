@extends('layouts.app')

@section('title', 'Hareket Geçmişi')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Hareket Geçmişi</span>
@endsection

@section('content')
<div x-data="courierActivityPage()" @activity-detail.window="openDetail($event.detail)">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hareket Geçmişi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Kuryeler üzerinde gerçekleştirilen tüm işlemleri görüntüleyin.
        </p>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Hareket" :value="number_format($summary['count'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Bugünkü Hareketler" :value="number_format($summary['today'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Bu Hafta" :value="number_format($summary['this_week'])" icon="courier" accent="primary" />
        <x-ui.finance-stat-card title="Bu Ay" :value="number_format($summary['this_month'])" icon="courier" accent="violet" />
    </div>

    {{-- Filtreler --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.activities.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select
                    name="courier_id"
                    label="Kurye"
                    :selected="$filters['courier_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())"
                />

                <x-ui.select
                    name="action"
                    label="İşlem Türü"
                    :selected="$filters['action']"
                    :options="array_merge(['all' => 'Tümü'], $actionTypes)"
                />

                <x-ui.select
                    name="user_id"
                    label="Kullanıcı"
                    :selected="$filters['user_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())"
                />

                <x-ui.select
                    name="date_range"
                    label="Tarih Aralığı"
                    :selected="$filters['date_range']"
                    :options="array_merge(['all' => 'Tümü'], $dateRanges)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('couriers.activities.index') }}" variant="secondary">Temizle</x-ui.button>
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
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlemi Yapan Kullanıcı</th>
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
                            <td class="max-w-[160px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $activity['courier_name'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.activity-type-badge :action="$activity['action']">
                                    {{ $activity['action_label'] }}
                                </x-courier.activity-type-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $activity['user_name'] }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $activity['ip_address'] }}</td>
                            <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-slate-400">
                                <p class="line-clamp-2">{{ $activity['description'] }}</p>
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.activity-row-actions :activity="$activity" />
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

    @include('modules.courier.activities.partials.detail-modal')
</div>
@endsection
