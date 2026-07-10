@extends('layouts.app')

@section('title', 'Belge Detayı')


@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $document['document_type_label'] }}
                </h1>
                <x-courier.document-status-badge :status="$document['status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $document['courier_name'] }} — {{ $document['document_number'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            <x-ui.button variant="secondary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                İndir
            </x-ui.button>
            <x-ui.button variant="secondary">Düzenle</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Kurye Bilgisi --}}
        <x-ui.card title="Kurye Bilgisi">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $document['courier_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['courier_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd><x-business.courier-type-badge :type="$document['courier_type']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Belge Bilgisi --}}
        <x-ui.card title="Belge Bilgisi">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Belge Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $document['document_number'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Yüklenme Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['uploaded_at_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Geçerlilik Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['expiry_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kalan Süre</dt>
                    <dd @class([
                        'font-medium',
                        'text-emerald-600 dark:text-emerald-400' => $document['status'] === 'valid',
                        'text-amber-600 dark:text-amber-400' => $document['status'] === 'expiring_soon',
                        'text-red-600 dark:text-red-400' => $document['status'] === 'expired',
                    ])>
                        @if ($document['status'] === 'expired')
                            {{ abs($document['days_remaining']) }} gün önce doldu
                        @else
                            {{ $document['days_remaining'] }} gün
                        @endif
                    </dd>
                </div>
                @if ($document['description'])
                    <div>
                        <dt class="mb-1 text-gray-500 dark:text-slate-400">Açıklama</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $document['description'] }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    </div>

    {{-- Dosya Önizleme --}}
    <x-ui.card title="Dosya Önizleme" class="mt-6">
        <div class="flex min-h-[420px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-8 dark:border-slate-600 dark:bg-slate-800/50">
            <svg class="mb-4 h-16 w-16 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $document['file_name'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Dosya önizleme backend bağlantısı sonrası aktif olacaktır.</p>
            <x-ui.button variant="secondary" class="mt-4" size="sm">
                Dosyayı İndir
            </x-ui.button>
        </div>
    </x-ui.card>

    <div class="mt-6">
        <x-ui.button href="{{ route('couriers.documents.index') }}" variant="secondary">
            Listeye Dön
        </x-ui.button>
    </div>
</div>
@endsection
