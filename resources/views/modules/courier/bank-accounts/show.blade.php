@extends('layouts.app')

@section('title', 'Banka Hesabı Detayı')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('couriers.bank-accounts.index') }}" class="hover:text-gray-900 dark:hover:text-white">Banka Bilgileri</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $account['bank_name'] }}</span>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Banka Hesabı Detayı</h1>
                <x-courier.bank-account-status-badge :status="$account['status']" />
                @if ($account['is_default'])
                    <x-courier.bank-account-default-badge />
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $account['courier_name'] }} — {{ $account['bank_name'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @if (! $account['is_default'] && $account['status'] === 'active')
                <x-ui.button variant="secondary">Varsayılan Yap</x-ui.button>
            @endif
            @if ($account['status'] === 'active')
                <x-ui.button variant="secondary">Pasife Al</x-ui.button>
            @endif
            <x-ui.button variant="secondary">Düzenle</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Kurye Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $account['courier_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $account['courier_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd><x-business.courier-type-badge :type="$account['courier_type']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Banka Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Banka</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $account['bank_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Hesap Sahibi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $account['account_holder'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Varsayılan</dt>
                    <dd>
                        @if ($account['is_default'])
                            <x-courier.bank-account-default-badge />
                        @else
                            <span class="text-gray-500">Hayır</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                    <dd><x-courier.bank-account-status-badge :status="$account['status']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="IBAN" class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 dark:border-slate-700 dark:bg-slate-800/50">
                <p class="font-mono text-lg tracking-wider text-gray-900 dark:text-white">{{ $account['iban_masked'] }}</p>
                <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">Güvenlik nedeniyle IBAN maskelenmiştir. Ödemeler varsayılan hesaba yapılır.</p>
            </div>
        </x-ui.card>

        <x-ui.card title="Şube Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Şube Kodu</dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $account['branch_code'] ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Hesap No</dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $account['account_number'] ?: '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        @if (count($courierAccounts) > 1)
            <x-ui.card title="Kuryenin Diğer Hesapları">
                <div class="space-y-3">
                    @foreach ($courierAccounts as $item)
                        @if ($item['id'] !== $account['id'])
                            <a
                                href="{{ route('couriers.bank-accounts.show', $item['id']) }}"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:hover:bg-slate-800/50"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item['bank_name'] }}</p>
                                    <p class="font-mono text-xs text-gray-500 dark:text-slate-400">{{ $item['iban_masked'] }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($item['is_default'])
                                        <x-courier.bank-account-default-badge />
                                    @endif
                                    <x-courier.bank-account-status-badge :status="$item['status']" />
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            </x-ui.card>
        @endif
    </div>

    @if ($account['notes'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $account['notes'] }}</p>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('couriers.bank-accounts.index') }}" variant="secondary">Listeye Dön</x-ui.button>
    </div>
</div>
@endsection
