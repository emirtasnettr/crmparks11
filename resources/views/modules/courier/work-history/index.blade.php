@extends('layouts.app')

@section('title', 'Çalışma Geçmişi')


@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Çalışma Geçmişi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Kuryelerin geçmiş ve aktif çalışma kayıtlarını görüntüleyin.
        </p>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Çalışma Kaydı" :value="number_format($summary['count'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Görev" :value="number_format($summary['active_count'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Tamamlanan Görev" :value="number_format($summary['completed_count'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Bu Ay Başlayan Görev" :value="number_format($summary['started_this_month'])" icon="courier" accent="primary" />
    </div>

    {{-- Filtre --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.work-history.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <x-ui.input
                    name="search"
                    label="Kurye Ara"
                    placeholder="Ad soyad veya telefon"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="business_id"
                    label="İşletme"
                    :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())"
                />

                <x-ui.select
                    name="agency_id"
                    label="Acente"
                    :selected="$filters['agency_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())"
                />

                <x-ui.select
                    name="courier_type"
                    label="Kurye Tipi"
                    :selected="$filters['courier_type']"
                    :options="array_merge(['all' => 'Tümü'], $courierTypes)"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)"
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
                <x-ui.button href="{{ route('couriers.work-history.index') }}" variant="secondary">Temizle</x-ui.button>
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
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başlangıç Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bitiş Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Toplam Çalışma Süresi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($records as $record)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-emerald-50/20 dark:bg-emerald-600/5' => $record['work_status'] === 'active',
                            'bg-amber-50/20 dark:bg-amber-600/5' => $record['work_status'] === 'leaving_soon',
                            'opacity-80' => $record['work_status'] === 'completed',
                        ])>
                            <td class="px-4 py-3 sm:px-6">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $record['courier_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $record['courier_phone'] }}</p>
                            </td>
                            <td class="max-w-[180px] px-4 py-3">
                                <p class="line-clamp-2 text-gray-900 dark:text-white">{{ $record['business_name'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-business.courier-type-badge :type="$record['courier_type']" />
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['agency_name'] !== '—' ? $record['agency_name'] : '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['start_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['end_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $record['work_duration'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.work-history-status-badge :status="$record['work_status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.work-history-row-actions :record="$record" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun çalışma kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>
@endsection
