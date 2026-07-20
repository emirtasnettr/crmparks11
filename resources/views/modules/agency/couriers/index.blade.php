@extends('layouts.app')

@section('title', 'Acenteye Bağlı Kuryeler')


@section('content')
<div x-data="agencyCourierPage()" @agency-courier-detail.window="openDetail($event.detail)">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Acenteye Bağlı Kuryeler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Acentelere bağlı tüm kuryeleri buradan yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openAssignModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Kurye Ata
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Kurye" :value="number_format($summary['total'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Kurye" :value="number_format($summary['active'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Pasif Kurye" :value="number_format($summary['inactive'])" icon="courier" accent="primary" />
        <x-ui.finance-stat-card title="Bu Ay Eklenen Kurye" :value="number_format($summary['this_month'])" icon="courier" accent="violet" />
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('agencies.couriers.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.input
                    name="search"
                    label="Kurye Ara"
                    placeholder="Ad Soyad, Telefon"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="agency_id"
                    label="Acente"
                    :selected="$filters['agency_id']"
                    :options="filter_select_options(collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="[
                        'all' => 'Tümü',
                        'active' => 'Aktif',
                        'on_leave' => 'İzinli',
                        'inactive' => 'Pasif',
                    ]"
                />

                <x-ui.select
                    name="vehicle_type"
                    label="Araç Tipi"
                    :selected="$filters['vehicle_type']"
                    :options="filter_select_options($vehicleTypes)"
                />

                <x-ui.select
                    name="active_business"
                    label="Aktif İşletme"
                    :selected="$filters['active_business']"
                    :options="filter_select_options(collect($businesses)->mapWithKeys(fn ($b) => [$b => $b])->all())"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('agencies.couriers.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Kayıt
            </p>

            <x-ui.export-button :href="route('agencies.couriers.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Araç Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktif İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Acenteye Katılış Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($records as $record)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'opacity-75' => ! $record['is_current'],
                        ])>
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $record['avatar_color'] }} text-xs font-bold text-white">
                                        {{ $record['avatar_initials'] }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $record['courier_name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $record['agency_name'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['phone'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['vehicle_type_label'] }}
                            </td>
                            <td class="max-w-[200px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                @if ($record['active_business_name'])
                                    <p class="line-clamp-2">{{ $record['active_business_name'] }}</p>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $record['join_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.status-badge :status="$record['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-agency.courier-row-actions :record="$record" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun kurye kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination
            :total="$total"
            :page="$page"
            :per-page="$perPage"
            :last-page="$lastPage"
        />
    </x-ui.card>

    @include('modules.agency.couriers.partials.assign-modal')
    @include('modules.agency.couriers.partials.detail-modal')
</div>
@endsection
