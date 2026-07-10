@extends('layouts.app')

@section('title', 'Evraklar')


@section('content')
<div x-data="documentPage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Evraklar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelere ait tüm evrakları yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Evrak Yükle
        </x-ui.button>
    </div>

    {{-- Filtreler --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('businesses.documents.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select
                    name="business_id"
                    label="İşletme"
                    :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())"
                />

                <x-ui.select
                    name="document_type"
                    label="Evrak Türü"
                    :selected="$filters['document_type']"
                    :options="array_merge(['all' => 'Tümü'], $documentTypes)"
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
                <x-ui.button href="{{ route('businesses.documents.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Evrak
            </p>

            <p class="text-xs text-gray-500 dark:text-slate-400">
                PDF, Word, Excel, Resim ve ZIP dosyaları desteklenir.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Evrak Adı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Evrak Türü</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Dosya Boyutu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Yüklenme Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Yükleyen</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($documents as $document)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="max-w-[200px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">
                                    {{ $document['business_name'] }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-business.document-file-icon :extension="$document['file_extension']" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-white">
                                            {{ $document['name'] }}
                                        </p>
                                        <p class="truncate text-xs text-gray-500 dark:text-slate-400">
                                            {{ $document['file_name'] }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['document_type_label'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['file_size_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['uploaded_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $document['uploaded_by'] }}
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-business.document-row-actions :document="$document" />
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

    @include('modules.business.documents.partials.modal', [
        'businesses' => $businesses,
        'documentTypes' => $documentTypes,
    ])
</div>
@endsection
