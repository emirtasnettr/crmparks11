@extends('layouts.app')

@section('title', 'Sözleşmeler')


@section('content')
<div x-data="agencyContractPage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sözleşmeler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Acenteler ile yapılan tüm sözleşmeleri buradan yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Sözleşme
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Sözleşme" :value="number_format($summary['total'])" icon="contract" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Sözleşme" :value="number_format($summary['active'])" icon="contract" accent="success" />
        <x-ui.finance-stat-card title="Yakında Bitecek" :value="number_format($summary['expiring_soon'])" icon="contract" accent="violet" />
        <x-ui.finance-stat-card title="Süresi Dolmuş" :value="number_format($summary['expired'])" icon="contract" accent="primary" />
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('agencies.contracts.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                <x-ui.select
                    name="agency_id"
                    label="Acente"
                    :selected="$filters['agency_id']"
                    :options="filter_select_options(collect($agencies)->mapWithKeys(fn ($a) => [$a['id'] => $a['name']])->all())"
                />

                <x-ui.select
                    name="contract_type"
                    label="Sözleşme Türü"
                    :selected="$filters['contract_type']"
                    :options="filter_select_options($contractTypes)"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="[
                        'all' => 'Tümü',
                        'active' => 'Aktif',
                        'expiring_soon' => '30 Gün İçinde Bitecek',
                        'expired' => 'Süresi Dolmuş',
                        'draft' => 'Taslak',
                    ]"
                />

                <x-ui.select
                    name="start_date"
                    label="Başlangıç Tarihi"
                    :selected="$filters['start_date']"
                    :options="filter_select_options($startDateFilters)"
                />

                <x-ui.select
                    name="end_date"
                    label="Bitiş Tarihi"
                    :selected="$filters['end_date']"
                    :options="filter_select_options($endDateFilters)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('agencies.contracts.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Sözleşme
            </p>

            <x-ui.export-button :href="route('agencies.contracts.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Sözleşme No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Sözleşme Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başlangıç</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bitiş</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kalan Gün</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($contracts as $contract)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-primary-50/30 dark:bg-primary-600/5' => $contract['is_current'],
                            'opacity-75' => $contract['status'] === 'expired',
                        ])>
                            <td class="max-w-[200px] px-4 py-3 sm:px-6">
                                <div class="space-y-1">
                                    <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">
                                        {{ $contract['agency_name'] }}
                                    </p>
                                    @if ($contract['is_current'])
                                        <x-ui.badge variant="primary">Güncel Sözleşme</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">
                                {{ $contract['contract_number'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contract['contract_type_label'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contract['start_date_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contract['end_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($contract['status'] === 'expired')
                                    <span class="font-medium text-red-600 dark:text-red-400">
                                        {{ abs($contract['remaining_days']) }} gün önce
                                    </span>
                                @elseif ($contract['status'] === 'draft')
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @else
                                    <span @class([
                                        'font-medium',
                                        'text-amber-600 dark:text-amber-400' => $contract['remaining_days'] <= 30,
                                        'text-gray-900 dark:text-white' => $contract['remaining_days'] > 30,
                                    ])>
                                        {{ $contract['remaining_days'] }} gün
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-agency.contract-status-badge :status="$contract['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-agency.contract-row-actions :contract="$contract" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun sözleşme bulunamadı.
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

    @include('modules.agency.contracts.partials.modal', [
        'agencies' => $agencies,
        'contractTypes' => $contractTypes,
    ])
</div>
@endsection
