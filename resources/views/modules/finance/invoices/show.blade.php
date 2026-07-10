@extends('layouts.app')

@section('title', 'Fatura Detayı')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Finans</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('finance.invoices.index') }}" class="hover:text-gray-900 dark:hover:text-white">Faturalar</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $invoice['reference'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Fatura Detayı</h1>
                <x-finance.invoice-status-badge :status="$invoice['invoice_status']" />
                <x-finance.invoice-collection-status-badge :status="$invoice['collection_status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $invoice['business_name'] }} — {{ $invoice['invoice_type_label'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($invoice['pdf_filename'])
                <x-ui.button variant="secondary">PDF Görüntüle</x-ui.button>
                <x-ui.button variant="secondary">İndir</x-ui.button>
            @endif
            @if ($invoice['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @endif
            @if ($invoice['collection_id'])
                <x-ui.button href="{{ route('finance.collections.show', $invoice['collection_id']) }}" variant="secondary">
                    Tahsilata Git
                </x-ui.button>
            @endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Ara Toplam (KDV Hariç)" :value="$invoice['subtotal_formatted']" accent="primary" />
        <x-ui.finance-stat-card title="KDV (%{{ $invoice['vat_rate'] }})" :value="$invoice['vat_amount_formatted']" accent="violet" />
        <x-ui.finance-stat-card title="Genel Toplam (KDV Dahil)" :value="$invoice['grand_total_formatted']" accent="success" />
        <x-ui.finance-stat-card title="Tahsil Edilen (KDV Hariç)" :value="$invoice['collected_amount_formatted']" accent="blue" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Fatura Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $invoice['reference'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['invoice_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Fatura Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['invoice_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Vade Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['due_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kaynak</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['source_label'] }}</dd>
                </div>
                @if ($invoice['e_invoice_uuid'] || $invoice['e_archive_uuid'])
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">e-Belge UUID</dt>
                        <dd class="text-right font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $invoice['integration_info']['uuid'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">GİB Durumu</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['integration_info']['gib_status'] }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $invoice['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Vergi No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $invoice['business_tax_no'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Adres</dt>
                    <dd class="text-right text-gray-900 dark:text-white">{{ $invoice['business_address'] }}</dd>
                </div>
            </dl>
            <div class="mt-4">
                <x-ui.button href="{{ route('businesses.index') }}" variant="secondary" size="sm">
                    İşletmeye Git
                </x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card title="Hakediş Bilgileri">
            @if ($invoice['earning_info'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Hakediş No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $invoice['earning_info']['reference'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Dönem</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['earning_info']['period'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Hakediş Tutarı</dt>
                        <dd class="font-semibold text-gray-900 dark:text-white">{{ $invoice['earning_info']['amount_formatted'] }}</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu fatura bir hakediş kaydına bağlı değil.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Tahsilat Bilgileri">
            @if ($invoice['collection_info'])
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tahsilat No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $invoice['collection_info']['reference'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $invoice['collection_info']['status'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Tahsil Edilen</dt>
                        <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ $invoice['collection_info']['collected_formatted'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Kalan</dt>
                        <dd class="font-medium text-red-600 dark:text-red-400">{{ $invoice['collection_info']['remaining_formatted'] }}</dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-ui.button href="{{ route('finance.collections.show', $invoice['collection_id']) }}" variant="secondary" size="sm">
                        Tahsilata Git
                    </x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu fatura için henüz tahsilat kaydı oluşturulmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Cari Hareketleri" class="lg:col-span-2">
            @if (count($invoice['current_account_movements']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Cari Kodu</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Belge No</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400 text-right">Borç</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400 text-right">Alacak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach ($invoice['current_account_movements'] as $movement)
                                <tr>
                                    <td class="py-2 font-mono text-xs text-gray-900 dark:text-white">{{ $movement['code'] }}</td>
                                    <td class="py-2 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $movement['document_no'] }}</td>
                                    <td class="py-2 text-gray-600 dark:text-slate-300">{{ $movement['date'] }}</td>
                                    <td class="py-2 text-gray-900 dark:text-white">{{ $movement['type_label'] }}</td>
                                    <td class="py-2 text-right tabular-nums text-red-600 dark:text-red-400">{{ $movement['debit_formatted'] }}</td>
                                    <td class="py-2 text-right tabular-nums text-emerald-600 dark:text-emerald-400">{{ $movement['credit_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <x-ui.button href="{{ route('finance.current-accounts.index') }}" variant="secondary" size="sm">
                        Cari Kartına Git
                    </x-ui.button>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz cari hareket oluşmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="PDF Önizleme" class="lg:col-span-2">
            @if ($invoice['pdf_filename'])
                <div class="flex min-h-[280px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-8 dark:border-slate-700 dark:bg-slate-800/50">
                    <svg class="mb-4 h-16 w-16 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $invoice['pdf_filename'] }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Fatura PDF önizlemesi — entegrasyon sonrası gerçek belge görüntülenecek</p>
                    <div class="mt-4 flex gap-2">
                        <x-ui.button variant="secondary" size="sm">PDF Görüntüle</x-ui.button>
                        <x-ui.button variant="secondary" size="sm">İndir</x-ui.button>
                    </div>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Taslak faturalar için PDF henüz oluşturulmamıştır.</p>
            @endif
        </x-ui.card>
    </div>

    @include('modules.finance.invoices.partials.edit-form')
</div>
@endsection
