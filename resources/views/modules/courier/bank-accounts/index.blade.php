@extends('layouts.app')

@section('title', 'Banka Bilgileri')


@section('content')
<div x-data="courierBankAccountPage()">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Banka Bilgileri</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kuryelere ait banka hesaplarını yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="openModal = true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Banka Hesabı
        </x-ui.button>
    </div>

    {{-- İstatistik Kartları --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Hesap" :value="number_format($summary['count'])" icon="earning" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Hesap" :value="number_format($summary['active'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Varsayılan Hesap" :value="number_format($summary['default'])" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Pasif Hesap" :value="number_format($summary['inactive'])" icon="earning" accent="violet" />
    </div>

    {{-- Filtre --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('couriers.bank-accounts.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input
                    name="search"
                    label="Kurye Ara"
                    placeholder="Kurye adı veya hesap sahibi"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="bank_key"
                    label="Banka"
                    :selected="$filters['bank_key']"
                    :options="array_merge(['all' => 'Tümü'], $banks)"
                />

                <x-ui.select
                    name="is_default"
                    label="Varsayılan Hesap"
                    :selected="$filters['is_default']"
                    :options="array_merge(['all' => 'Tümü'], $defaultFilters)"
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
                <x-ui.button href="{{ route('couriers.bank-accounts.index') }}" variant="secondary">Temizle</x-ui.button>
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
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Banka</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hesap Sahibi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IBAN</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Şube Kodu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Hesap No</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Varsayılan</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($accounts as $account)
                        <tr @class([
                            'transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50',
                            'bg-amber-50/20 dark:bg-amber-600/5' => $account['is_default'] && $account['status'] === 'active',
                            'opacity-75' => $account['status'] === 'inactive',
                        ])>
                            <td class="max-w-[160px] px-4 py-3 sm:px-6">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $account['courier_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $account['bank_name'] }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $account['account_holder'] }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">{{ $account['iban_masked'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $account['branch_code'] ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-400">{{ $account['account_number'] ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($account['is_default'])
                                    <x-courier.bank-account-default-badge />
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-courier.bank-account-status-badge :status="$account['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-courier.bank-account-row-actions :account="$account" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun banka hesabı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.courier.bank-accounts.partials.modal')
</div>
@endsection
