@extends('layouts.app')

@section('title', 'Gider Detayı')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('finance.expenses.index') }}" class="hover:text-gray-900 dark:hover:text-white">Giderler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $expense['reference'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gider Detayı</h1>
                <x-finance.expense-payment-status-badge :status="$expense['payment_status']" />
                <x-finance.expense-type-badge :type="$expense['expense_type']" />
                <x-finance.expense-source-badge :source="$expense['source']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $expense['payee_display'] }} — {{ $expense['expense_date_formatted'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($expense['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @endif
            <x-ui.button variant="secondary">Ödeme Yap</x-ui.button>
            <x-ui.button variant="secondary">PDF Oluştur</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Net Tutar" :value="$expense['amount_formatted']" accent="danger" />
        <x-ui.finance-stat-card title="KDV" :value="$expense['vat_amount_formatted']" accent="warning" />
        <x-ui.finance-stat-card title="Brüt Tutar" :value="$expense['gross_amount_formatted']" accent="primary" />
        <x-ui.finance-stat-card title="KDV Oranı" :value="number_format($expense['vat_rate'], 0) . '%'" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Gider Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gider No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['reference'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gider Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['expense_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kaynak</dt>
                    <dd><x-finance.expense-source-badge :source="$expense['source']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Gider Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['expense_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['document_no'] }}</dd>
                </div>
                @if ($expense['earning_reference'])
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Hakediş Ref.</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['earning_reference'] }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Açıklama</dt>
                    <dd class="max-w-[240px] text-right font-medium text-gray-900 dark:text-white">{{ $expense['description'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Kurye / Acente Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Tip</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['payee_info']['type'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ünvan</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $expense['payee_info']['name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['payee_info']['phone'] }}</dd>
                </div>
                @if ($expense['current_account_code'])
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['current_account_code'] }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card title="Ödeme Bilgisi">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Durumu</dt>
                    <dd><x-finance.expense-payment-status-badge :status="$expense['payment_info']['status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['payment_info']['date'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Yöntemi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['payment_info']['method'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Ref.</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['payment_info']['reference'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Cari Hareketi">
            @if ($expense['current_account_movement'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['current_account_movement']['code'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $expense['current_account_movement']['document_no'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">İşlem</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['current_account_movement']['type_label'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Borç</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['current_account_movement']['debit_formatted'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Alacak</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $expense['current_account_movement']['credit_formatted'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary" size="sm">
                        Cari Kartına Git
                    </x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu gider kaydı bir cari hesaba bağlı değil.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Belgeler" class="lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Belge</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach ($expense['documents'] as $document)
                            <tr>
                                <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $document['name'] }}</td>
                                <td class="py-2 text-gray-600 dark:text-slate-300">{{ $document['type'] }}</td>
                                <td class="py-2 text-gray-600 dark:text-slate-300">{{ $document['date'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card title="Notlar" class="lg:col-span-2">
            <p class="text-sm text-gray-600 dark:text-slate-300">
                {{ $expense['notes'] ?? 'Bu gider kaydı için not bulunmuyor.' }}
            </p>
        </x-ui.card>
    </div>

    @include('modules.finance.expenses.partials.edit-form')
</div>
@endsection
