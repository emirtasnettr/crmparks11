@extends('layouts.app')

@section('title', 'Ödeme Detayı')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('finance.payments.index') }}" class="hover:text-gray-900 dark:hover:text-white">Ödemeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $payment['reference'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Ödeme Detayı</h1>
                <x-finance.payment-status-badge :status="$payment['status']" />
                @if (! $payment['is_active'])
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-slate-300">Pasif</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $payment['recipient_name'] }} — {{ $payment['recipient_type_label'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($payment['status'] !== 'cancelled')
                <x-ui.button variant="secondary">Düzenle</x-ui.button>
                @if ($payment['remaining_amount'] > 0)
                    <x-ui.button variant="secondary">Ödeme Yap</x-ui.button>
                @endif
            @endif
            @if (count($payment['receipts']) > 0)
                <x-ui.button variant="secondary">Dekont Görüntüle</x-ui.button>
            @endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Ödenecek Tutar" :value="$payment['total_amount_formatted']" accent="primary" />
        <x-ui.finance-stat-card title="Ödenen Tutar" :value="$payment['paid_amount_formatted']" accent="success" />
        <x-ui.finance-stat-card title="Kalan Tutar" :value="$payment['remaining_amount_formatted']" accent="danger" />
        <x-ui.finance-stat-card title="Ödeme Yöntemi" :value="$payment['payment_method_label']" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Alıcı Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Alıcı Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['recipient_info']['type'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad / Unvan</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $payment['recipient_info']['name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $payment['recipient_info']['code'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['recipient_info']['phone'] }}</dd>
                </div>
            </dl>
            <div class="mt-4">
                <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary" size="sm">
                    Cari Kartına Git
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Hakediş Bilgileri">
            @if ($payment['earning_info'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Hakediş No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $payment['earning_info']['reference'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tür</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['earning_info']['type_label'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tutar</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $payment['earning_info']['amount_formatted'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-ui.button variant="secondary" size="sm">Hakedişe Git</x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu ödeme bir hakediş kaydına bağlı değil (manuel ödeme).</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Cari Hareketi">
            @if ($payment['current_account_movement'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Cari Kodu</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $payment['current_account_movement']['code'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $payment['current_account_movement']['document_no'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">İşlem</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['current_account_movement']['type_label'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Borç</dt>
                        <dd class="font-medium text-red-600 dark:text-red-400">{{ $payment['current_account_movement']['debit_formatted'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz cari hareket oluşmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Ödeme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['payment_info']['status'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['payment_info']['date'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Yöntem</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['payment_info']['method'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Referans No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $payment['payment_info']['reference'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Banka Hesabı</dt>
                    <dd class="text-right text-xs font-medium text-gray-900 dark:text-white">{{ $payment['payment_info']['bank_account'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kaynak</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $payment['source_label'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Ödeme Geçmişi" class="lg:col-span-2">
            @if (count($payment['payment_history']) > 0)
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
                            @foreach ($payment['payment_history'] as $history)
                                <tr>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $history['date'] }}</td>
                                    <td class="py-2 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $history['amount_formatted'] }}</td>
                                    <td class="py-2 text-gray-600 dark:text-slate-300">{{ $history['method'] }}</td>
                                    <td class="py-2 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $history['reference'] }}</td>
                                    <td class="py-2 text-gray-600 dark:text-slate-300">{{ $history['bank'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz ödeme yapılmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Dekontlar">
            @if (count($payment['receipts']) > 0)
                <ul class="space-y-2 text-sm">
                    @foreach ($payment['receipts'] as $receipt)
                        <li class="flex items-center justify-between rounded-lg border border-gray-200 px-3 py-2 dark:border-slate-700">
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $receipt['name'] }}</span>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $receipt['type'] }}</p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">{{ $receipt['date'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Yüklenmiş dekont bulunmuyor.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Notlar">
            <p class="text-sm text-gray-600 dark:text-slate-300">
                {{ $payment['notes'] ?? 'Bu ödeme kaydı için not bulunmuyor.' }}
            </p>
            @if ($payment['status'] === 'cancelled')
                <p class="mt-3 text-sm text-gray-500 dark:text-slate-400">
                    Bu kayıt iptal edilmiştir. Ödeme kayıtları silinmez; yalnızca iptal veya pasif duruma getirilebilir.
                </p>
            @endif
        </x-ui.card>
    </div>
</div>
@endsection
