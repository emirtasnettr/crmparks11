@extends('layouts.app')

@section('title', 'Tahsilat Detayı')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('finance.collections.index') }}" class="hover:text-gray-900 dark:hover:text-white">Tahsilatlar</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $collection['reference'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tahsilat Detayı</h1>
                <x-finance.collection-record-status-badge :status="$collection['status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $collection['business_name'] }} — Vade: {{ $collection['due_date_formatted'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($collection['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @endif
            <x-ui.button variant="secondary">Tahsilat Gir</x-ui.button>
            <x-ui.button variant="secondary">Dekont Yükle</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Tutar" :value="$collection['total_amount_formatted']" accent="primary" />
        <x-ui.finance-stat-card title="Tahsil Edilen" :value="$collection['collected_amount_formatted']" accent="success" />
        <x-ui.finance-stat-card title="Kalan Tutar" :value="$collection['remaining_amount_formatted']" accent="danger" />
        <x-ui.finance-stat-card title="Ödeme Yöntemi" :value="$collection['payment_method_label']" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $collection['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $collection['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $collection['business_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Şehir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $collection['business_city'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Gelir Bilgisi">
            @if ($collection['revenue_info'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Gelir No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $collection['revenue_info']['reference'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Fatura No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $collection['revenue_info']['invoice_no'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tutar</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $collection['revenue_info']['amount_formatted'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-ui.button href="{{ route('finance.revenues.show', $collection['revenue_id']) }}" variant="secondary" size="sm">
                        Gelir Kaydına Git
                    </x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu tahsilat bir gelir kaydına bağlı değil.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Fatura Bilgisi">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $collection['invoice_info']['invoice_no'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Vade Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $collection['invoice_info']['due_date'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Toplam</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $collection['invoice_info']['total_formatted'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Cari Hareketi">
            @if ($collection['current_account_movement'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $collection['current_account_movement']['code'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $collection['current_account_movement']['document_no'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">İşlem</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $collection['current_account_movement']['type_label'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Alacak</dt>
                        <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ $collection['current_account_movement']['credit_formatted'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary" size="sm">
                        Cari Kartına Git
                    </x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz cari hareket oluşmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Tahsilat Geçmişi" class="lg:col-span-2">
            @if (count($collection['collection_history']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400 text-right">Tutar</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Yöntem</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Referans</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Banka</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach ($collection['collection_history'] as $payment)
                                <tr>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $payment['date'] }}</td>
                                    <td class="py-2 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $payment['amount_formatted'] }}</td>
                                    <td class="py-2 text-gray-600 dark:text-slate-300">{{ $payment['method'] }}</td>
                                    <td class="py-2 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $payment['reference'] }}</td>
                                    <td class="py-2 text-gray-600 dark:text-slate-300">{{ $payment['bank'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz tahsilat ödemesi yapılmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Dekontlar" id="receipts">
            @if (count($collection['receipts']) > 0)
                <ul class="mb-4 space-y-2 text-sm">
                    @foreach ($collection['receipts'] as $receipt)
                        <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-slate-700">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $receipt['name'] }}</span>
                                <span class="ml-2 text-xs text-gray-500 dark:text-slate-400">{{ $receipt['date'] }}</span>
                            </div>
                            @if (! empty($receipt['download_url']))
                                <a href="{{ $receipt['download_url'] }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                                    İndir
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">Yüklenmiş dekont bulunmuyor.</p>
            @endif

            <form
                action="{{ route('finance.collections.receipts.store', $collection['id']) }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-3"
            >
                @csrf
                <div>
                    <label for="receipt-file" class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">
                        Dekont dosyası
                    </label>
                    <input
                        id="receipt-file"
                        type="file"
                        name="file"
                        accept=".pdf,.png,.jpg,.jpeg,.webp"
                        required
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 dark:text-slate-300 dark:file:bg-brand-900/30 dark:file:text-brand-300"
                    >
                    @error('file')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700"
                >
                    Dekont Yükle
                </button>
            </form>
        </x-ui.card>

        <x-ui.card title="Notlar">
            <p class="text-sm text-gray-600 dark:text-slate-300">
                {{ $collection['notes'] ?? 'Bu tahsilat kaydı için not bulunmuyor.' }}
            </p>
        </x-ui.card>
    </div>

    @include('modules.finance.collections.partials.edit-form')
</div>
@endsection
