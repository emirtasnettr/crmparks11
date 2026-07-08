@extends('layouts.app')

@section('title', 'Yetkililer')

@section('breadcrumb')
    <a href="{{ route('businesses.index') }}" class="hover:text-gray-900 dark:hover:text-white">İşletmeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Yetkililer</span>
@endsection

@section('content')
<div x-data="contactPage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yetkililer</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletmelere ait tüm yetkilileri buradan yönetebilirsiniz.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Yetkili
        </x-ui.button>
    </div>

    {{-- Filtre Alanı --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('businesses.contacts.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input
                    name="search"
                    label="Yetkili Ara"
                    placeholder="Ad soyad, telefon veya e-posta"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="business_id"
                    label="İşletme"
                    :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())"
                />

                <x-ui.select
                    name="title"
                    label="Görev"
                    :selected="$filters['title']"
                    :options="array_merge(['all' => 'Tümü'], collect($titles)->mapWithKeys(fn ($t) => [$t => $t])->all())"
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
                <x-ui.button href="{{ route('businesses.contacts.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Yetkili
            </p>

            <x-ui.export-button :href="route('businesses.contacts.export', request()->query())" />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ad Soyad</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Görevi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">E-Posta</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Varsayılan Yetkili</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($contacts as $contact)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="max-w-[200px] px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
                                <span class="line-clamp-2">{{ $contact['business_name'] }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $contact['full_name'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contact['title'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contact['phone'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $contact['email'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($contact['is_default'])
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                        <span>⭐</span> Varsayılan
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-business.status-badge :status="$contact['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-business.contact-row-actions :contact="$contact" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun yetkili bulunamadı.
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

    @include('modules.business.contacts.partials.modal')
</div>
@endsection
