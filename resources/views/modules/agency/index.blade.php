@extends('layouts.app')

@section('title', 'Acenteler')


@section('content')
<div x-data="agencyListPage(@js($agenciesForModal))" @agency-detail.window="openDetail($event.detail)">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Acenteler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistemde kayıtlı tüm acenteleri buradan yönetin.
            </p>
        </div>

        <x-ui.button href="{{ route('agencies.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Acente
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-ui.finance-stat-card title="Toplam Acente" :value="number_format($summary['total'])" icon="agency" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Acente" :value="number_format($summary['active'])" icon="agency" accent="success" />
        <x-ui.finance-stat-card title="Toplam Kurye" :value="number_format($summary['total_couriers'])" icon="courier" accent="violet" />
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('agencies.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input
                    name="search"
                    label="Acente Ara"
                    placeholder="Firma Ünvanı, Vergi No, Telefon"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="city"
                    label="İl"
                    :selected="$filters['city']"
                    :options="filter_select_options(collect($cities)->mapWithKeys(fn ($c) => [$c => $c])->all())"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="filter_select_options($statuses)"
                />

                <x-ui.select
                    name="courier_count"
                    label="Aktif Kurye Sayısı"
                    :selected="$filters['courier_count']"
                    :options="filter_select_options($courierCountRanges)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('agencies.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Acente
            </p>

            <x-ui.export-button :href="route('agencies.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Logo</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Marka Adı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Yetkili</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İl / İlçe</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktif Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktif İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($agencies as $agency)
                        <tr
                            role="link"
                            tabindex="0"
                            class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50"
                            data-href="{{ route('agencies.show', $agency['id']) }}"
                            onclick="window.location.href = this.dataset.href"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.href; }"
                        >
                            <td class="px-4 py-3 sm:px-6">
                                <x-ui.entity-avatar
                                    :url="$agency['logo_url'] ?? null"
                                    :initials="$agency['logo']"
                                    :color="$agency['logo_color']"
                                    :alt="($agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name']).' logosu'"
                                />
                            </td>
                            <td class="max-w-[220px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">{{ $agency['company_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $agency['authorized_person'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $agency['phone'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $agency['location'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-medium text-gray-900 dark:text-white">
                                {{ $agency['active_couriers'] }}
                            </td>
                            <td class="px-4 py-3 text-center font-medium text-gray-900 dark:text-white">
                                {{ $agency['active_businesses'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-agency.status-badge :status="$agency['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                <x-agency.row-actions :agency="$agency" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun acente bulunamadı.
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

    @include('modules.agency.partials.detail-modal')
</div>
@endsection
