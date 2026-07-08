@extends('layouts.app')

@section('title', 'Kuryeler')

@section('breadcrumb')
    <span class="font-medium text-gray-900 dark:text-white">Kuryeler</span>
@endsection

@section('content')
<div x-data="courierListPage(@js($couriersForModal))" @courier-detail.window="openDetail($event.detail)">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kuryeler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistemde kayıtlı tüm kuryeleri buradan yönetin.
            </p>
        </div>

        <x-ui.button href="{{ route('couriers.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kurye
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Kurye" :value="number_format($summary['total'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Kurye" :value="number_format($summary['active'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Esnaf Kurye" :value="number_format($summary['independent'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Acente Kuryesi" :value="number_format($summary['agency'])" icon="agency" accent="primary" />
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.input
                    name="search"
                    label="Kurye Ara"
                    placeholder="Ad Soyad, Telefon, TC Kimlik No"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="courier_type"
                    label="Kurye Tipi"
                    :selected="$filters['courier_type']"
                    :options="array_merge(['all' => 'Tümü'], $courierTypes)"
                />

                <x-ui.select
                    name="agency_id"
                    label="Acente"
                    :selected="$filters['agency_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)"
                />

                <x-ui.select
                    name="vehicle_type"
                    label="Araç Tipi"
                    :selected="$filters['vehicle_type']"
                    :options="array_merge(['all' => 'Tümü'], $vehicleTypes)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('couriers.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Kurye
            </p>

            <x-ui.export-button :href="route('couriers.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Profil</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ad Soyad</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bağlı Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Araç Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktif İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($couriers as $courier)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 sm:px-6">
                                <x-ui.entity-avatar
                                    :url="$courier['photo_url'] ?? null"
                                    :initials="$courier['avatar_initials']"
                                    :color="$courier['avatar_color']"
                                    shape="rounded-full"
                                    :alt="$courier['full_name'].' profil fotoğrafı'"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $courier['full_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $courier['tc_number'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $courier['phone'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-business.courier-type-badge :type="$courier['courier_type']" />
                            </td>
                            <td class="max-w-[180px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                @if ($courier['agency_name'])
                                    <p class="line-clamp-2">{{ $courier['agency_name'] }}</p>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $courier['vehicle_type_label'] }}
                            </td>
                            <td class="max-w-[200px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                @if ($courier['active_business_name'])
                                    <p class="line-clamp-2">{{ $courier['active_business_name'] }}</p>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.status-badge :status="$courier['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.row-actions :courier="$courier" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun kurye bulunamadı.
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

    @include('modules.courier.partials.detail-modal')
</div>
@endsection
