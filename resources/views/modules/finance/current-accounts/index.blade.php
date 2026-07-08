@extends('layouts.app')

@section('title', 'Cari Hesaplar')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Cari Hesaplar</span>
@endsection

@section('content')
<div
    x-data="financeCurrentAccountPage(@js($accountDetails))"
    @current-account-card.window="openCard($event.detail)"
    @current-account-statement.window="openStatement($event.detail)"
    @current-account-movement.window="openMovement($event.detail)"
    @finance-row-action.window="handleRowAction($event.detail)"
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Cari Hesaplar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistemdeki tüm cari hesapları yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="openNewAccount()">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Cari Hesap
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" @click="openMovement({ id: null, preset: null })">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Hareket
            </x-ui.button>
            <x-ui.export-button :href="route('finance.current-accounts.export', request()->query())" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Cari" :value="money_excl_vat($summary['count'])" icon="building" accent="primary" />
        <x-ui.finance-stat-card title="Toplam Alacak" :value="number_format($summary['total_receivable'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Toplam Borç" :value="money_excl_vat($summary['total_payable'])" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Net Bakiye" :value="money_excl_vat($summary['net_balance'])" icon="earning" accent="violet" />
        <x-ui.finance-stat-card title="Vadesi Geçen Alacak" :value="money_excl_vat($summary['overdue_receivable'])" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Vadesi Geçen Borç" :value="money_excl_vat($summary['overdue_payable'])" icon="chart" accent="warning" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('finance.current-accounts.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input name="search" label="Cari Ara" :value="$filters['search']" placeholder="Kod, ünvan veya telefon" />

                <x-ui.select name="type" label="Cari Tipi" :selected="$filters['type']"
                    :options="array_merge(['all' => 'Tümü'], $accountTypes)" />

                <x-ui.select name="status" label="Durum" :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)" />

                <x-ui.select name="balance_status" label="Bakiye Durumu" :selected="$filters['balance_status']"
                    :options="array_merge(['all' => 'Tümü'], $balanceStatuses)" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> cari hesap listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Cari Kodu</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Cari Ünvanı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Cari Tipi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Borç</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Alacak</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Bakiye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Son Hareket</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($accounts as $account)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300 sm:px-6">
                                {{ $account['code'] }}
                            </td>
                            <td class="max-w-[220px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">{{ $account['title'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.account-type-badge :type="$account['type']" />
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                {{ $account['phone'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ $account['total_debit_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ $account['total_credit_formatted'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">
                                <span @class([
                                    'text-emerald-600 dark:text-emerald-400' => $account['balance_tone'] === 'positive',
                                    'text-red-600 dark:text-red-400' => $account['balance_tone'] === 'negative',
                                    'text-gray-500 dark:text-slate-400' => $account['balance_tone'] === 'zero',
                                ])>
                                    {{ $account['balance_formatted'] }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">
                                <span class="block text-gray-900 dark:text-white">{{ $account['last_movement_formatted'] }}</span>
                                <span class="text-xs text-gray-400">{{ $account['last_movement_label'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <x-finance.account-status-badge :status="$account['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-finance.current-account-row-actions :account="$account" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun cari hesap bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.finance.current-accounts.partials.card-modal')
    @include('modules.finance.current-accounts.partials.statement-modal')
    @include('modules.finance.current-accounts.partials.movement-modal')
    @include('modules.finance.current-accounts.partials.new-account-modal')
</div>
@endsection
