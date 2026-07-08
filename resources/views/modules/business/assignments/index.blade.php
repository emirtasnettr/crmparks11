@extends('layouts.app')

@section('title', 'Atanan Kuryeler')

@section('breadcrumb')
    <a href="{{ route('businesses.index') }}" class="hover:text-gray-900 dark:hover:text-white">İşletmeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Atanan Kuryeler</span>
@endsection

@section('content')
<div x-data="assignmentPage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Atanan Kuryeler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelere atanmış tüm kuryeleri buradan yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Kurye Ataması
        </x-ui.button>
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('businesses.assignments.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
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
                    :options="[
                        'all' => 'Tümü',
                        'independent' => 'Esnaf Kurye',
                        'agency' => 'Acente Kuryesi',
                    ]"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="[
                        'all' => 'Tümü',
                        'active' => 'Aktif',
                        'inactive' => 'Pasif',
                    ]"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('businesses.assignments.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($activeCount) }}</span>
                Aktif Atama
            </p>

            <x-ui.export-button :href="route('businesses.assignments.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Acente</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başlangıç Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bitiş Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Çalışma Durumu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($assignments as $assignment)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-emerald-50/20 dark:bg-emerald-600/5' => $assignment['work_status'] === 'active',
                            'opacity-75' => $assignment['work_status'] === 'left',
                        ])>
                            <td class="px-4 py-3 sm:px-6">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $assignment['courier_phone'] }}</p>
                                </div>
                            </td>
                            <td class="max-w-[180px] px-4 py-3">
                                <p class="line-clamp-2 text-gray-900 dark:text-white">{{ $assignment['business_name'] }}</p>
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $assignment['agency_name'] !== '—' ? $assignment['agency_name'] : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-business.courier-type-badge :type="$assignment['courier_type']" :label="$assignment['courier_type_label']" />
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $assignment['start_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $assignment['end_date_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-business.assignment-status-badge :status="$assignment['work_status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-business.assignment-row-actions :assignment="$assignment" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun atama bulunamadı.
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

    @include('modules.business.assignments.partials.modal')
</div>
@endsection
