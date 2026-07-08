@extends('layouts.app')

@section('title', 'Araç Bilgileri')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Araç Bilgileri</span>
@endsection

@section('content')
<div x-data="courierVehiclePage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Araç Bilgileri</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kuryelere ait araç bilgilerini yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Araç
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Araç" :value="number_format($summary['count'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Motosiklet" :value="number_format($summary['motorcycle'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Otomobil" :value="number_format($summary['car'])" icon="courier" accent="primary" />
        <x-ui.finance-stat-card title="Aktif Araç" :value="number_format($summary['active'])" icon="courier" accent="success" />
    </div>

    {{-- Filtre --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.vehicles.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select
                    name="courier_id"
                    label="Kurye"
                    :selected="$filters['courier_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($couriers)->mapWithKeys(fn ($c) => [$c['id'] => $c['name']])->all())"
                />

                <x-ui.select
                    name="vehicle_type"
                    label="Araç Tipi"
                    :selected="$filters['vehicle_type']"
                    :options="array_merge(['all' => 'Tümü'], $vehicleTypes)"
                />

                <x-ui.select
                    name="brand"
                    label="Marka"
                    :selected="$filters['brand']"
                    :options="array_merge(['all' => 'Tümü'], collect($brands)->mapWithKeys(fn ($b) => [$b => $b])->all())"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('couriers.vehicles.index') }}" variant="secondary">Temizle</x-ui.button>
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
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Araç Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Plaka</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Marka</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Model</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Model Yılı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ruhsat Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Sigorta Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($vehicles as $vehicle)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-emerald-50/20 dark:bg-emerald-600/5' => $vehicle['status'] === 'active',
                            'opacity-75' => $vehicle['status'] === 'inactive',
                        ])>
                            <td class="max-w-[160px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $vehicle['courier_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $vehicle['vehicle_type_label'] }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white">{{ $vehicle['plate_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $vehicle['brand_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $vehicle['model_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $vehicle['model_year_formatted'] }}</td>
                            <td class="px-4 py-3">
                                @if ($vehicle['license_status'])
                                    <x-courier.vehicle-license-status-badge :status="$vehicle['license_status']" />
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($vehicle['insurance_status'])
                                    <x-courier.vehicle-insurance-status-badge :status="$vehicle['insurance_status']" />
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.vehicle-status-badge :status="$vehicle['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.vehicle-row-actions :vehicle="$vehicle" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun araç bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.courier.vehicles.partials.modal')
</div>
@endsection
