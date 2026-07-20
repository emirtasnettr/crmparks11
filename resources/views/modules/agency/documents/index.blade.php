@extends('layouts.app')

@section('title', 'Evraklar')


@section('content')
<div x-data="agencyDocumentPage(@js(['maxSizeMb' => config('crmlog.upload.max_size_mb')]))">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Evraklar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Acentelere ait tüm evrakları buradan yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Evrak Yükle
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Evrak" :value="number_format($summary['total'])" icon="contract" accent="blue" />
        <x-ui.finance-stat-card title="Geçerli Evrak" :value="number_format($summary['valid'])" icon="contract" accent="success" />
        <x-ui.finance-stat-card title="Süresi Yaklaşan" :value="number_format($summary['expiring_soon'])" icon="contract" accent="warning" />
        <x-ui.finance-stat-card title="Süresi Dolmuş" :value="number_format($summary['expired'])" icon="contract" accent="danger" />
    </div>

    {{-- Filtreler --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('agencies.documents.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input
                    name="search"
                    label="Acente Ara"
                    placeholder="Acente adı veya belge no"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="document_type"
                    label="Evrak Türü"
                    :selected="$filters['document_type']"
                    :options="filter_select_options($documentTypes)"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="filter_select_options($statuses)"
                />

                <x-ui.select
                    name="expiry_filter"
                    label="Geçerlilik Tarihi"
                    :selected="$filters['expiry_filter']"
                    :options="filter_select_options($expiryFilters)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('agencies.documents.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Evrak
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Evrak Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Belge No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Yüklenme Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Geçerlilik Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($documents as $document)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-red-50/40 dark:bg-red-900/10' => $document['status'] === 'expired',
                            'bg-amber-50/30 dark:bg-amber-900/10' => $document['status'] === 'expiring_soon',
                        ])>
                            <td class="max-w-[200px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">
                                    {{ $document['agency_name'] }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['document_type_label'] }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">
                                {{ $document['document_number'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['uploaded_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-gray-600 dark:text-slate-400">{{ $document['expiry_date_formatted'] }}</p>
                                @if ($document['status'] === 'expiring_soon')
                                    <p class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ $document['days_remaining'] }} gün kaldı</p>
                                @elseif ($document['status'] === 'expired')
                                    <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ abs($document['days_remaining']) }} gün önce doldu</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-agency.document-status-badge :status="$document['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-agency.document-row-actions :document="$document" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun evrak bulunamadı.
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

    @include('modules.agency.documents.partials.modal', [
        'agencies' => $agencies,
        'documentTypes' => $documentTypes,
    ])
</div>
@endsection
