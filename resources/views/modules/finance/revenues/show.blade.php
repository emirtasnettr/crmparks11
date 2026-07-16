@extends('layouts.app')

@section('title', 'Gelir Detayı')


@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gelir Detayı</h1>
                <x-finance.collection-status-badge :status="$revenue['collection_status']" />
                <x-finance.revenue-type-badge :type="$revenue['revenue_type']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $revenue['business_name'] }} — {{ $revenue['revenue_date_formatted'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($revenue['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @endif
            <x-ui.button href="{{ route('finance.collections.index', ['business_id' => $revenue['business_id']]) }}" variant="secondary">Tahsilatlar</x-ui.button>
            <x-ui.button href="{{ route('finance.revenues.pdf', $revenue['id']) }}" variant="secondary">PDF Oluştur</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Net Tutar" :value="$revenue['amount_formatted']" accent="success" />
        <x-ui.finance-stat-card title="KDV" :value="$revenue['vat_amount_formatted']" :excl-vat="false" accent="warning" />
        <x-ui.finance-stat-card title="Brüt Tutar" :value="$revenue['gross_amount_formatted']" :excl-vat="false" accent="primary" />
        <x-ui.finance-stat-card title="KDV Oranı" :value="number_format($revenue['vat_rate'], 0) . '%'" :excl-vat="false" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $revenue['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Şehir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['business_city'] }} / {{ $revenue['business_district'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['business_phone'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Gelir Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gelir No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['reference'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gelir Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['revenue_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gelir Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['revenue_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Oluşturulma</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['created_at_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Açıklama</dt>
                    <dd class="max-w-[240px] text-right font-medium text-gray-900 dark:text-white">{{ $revenue['description'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Hakediş Bilgisi">
            @if ($revenue['earning_info'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Hakediş No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['earning_info']['reference'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Dönem</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['earning_info']['period'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tutar</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $revenue['earning_info']['amount_formatted'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu gelir kaydı bir hakedişe bağlı değil.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Fatura Bilgisi">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura Durumu</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['invoice_info']['status_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['invoice_info']['invoice_no'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kesim Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['invoice_info']['issue_date'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Vade Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['invoice_info']['due_date'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Tahsilat Bilgisi">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Tahsil Durumu</dt>
                    <dd><x-finance.collection-status-badge :status="$revenue['collection_info']['status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Tahsil Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['collection_info']['date'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Yöntemi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['collection_info']['method'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Tahsilat Ref.</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['collection_info']['reference'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Cari Hareketi">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['current_account_code'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $revenue['current_account_movement']['document_no'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">İşlem</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['current_account_movement']['type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Borç</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['current_account_movement']['debit_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Alacak</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $revenue['current_account_movement']['credit_formatted'] }}</dd>
                </div>
            </dl>
            <div class="mt-4">
                <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary" size="sm">
                    Cari Kartına Git
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Notlar" class="lg:col-span-2">
            <p class="text-sm text-gray-600 dark:text-slate-300">
                {{ $revenue['notes'] ?? 'Bu gelir kaydı için not bulunmuyor.' }}
            </p>
        </x-ui.card>
    </div>

    @include('modules.finance.revenues.partials.edit-form')
</div>
@endsection
