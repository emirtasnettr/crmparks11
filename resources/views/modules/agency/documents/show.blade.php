@extends('layouts.app')

@section('title', 'Evrak Detayı')

@section('breadcrumb')
    <a href="{{ route('agencies.index') }}" class="hover:text-gray-900 dark:hover:text-white">Acenteler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('agencies.documents.index') }}" class="hover:text-gray-900 dark:hover:text-white">Evraklar</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $document['document_type_label'] }}
                </h1>
                <x-agency.document-status-badge :status="$document['status']" />
                @if ($document['version'] > 1 || count($document['version_history']) > 1)
                    <x-ui.badge variant="primary">v{{ $document['version'] }}</x-ui.badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $document['agency_name'] }} — {{ $document['document_number'] }}
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

    @if ($document['status'] === 'expiring_soon')
        <x-ui.alert type="warning" class="mb-6">
            Bu evrakın geçerlilik süresi {{ $document['days_remaining'] }} gün içinde dolacak. Lütfen yenileme işlemini planlayın.
        </x-ui.alert>
    @elseif ($document['status'] === 'expired')
        <x-ui.alert type="danger" class="mb-6">
            Bu evrakın geçerlilik süresi {{ abs($document['days_remaining']) }} gün önce doldu. Acil yenileme gereklidir.
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Acente Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $document['agency_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Yetkili</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['agency_authorized'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Şehir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['agency_city'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['agency_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">E-posta</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['agency_email'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Belge Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Evrak Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Belge No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $document['document_number'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Versiyon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">v{{ $document['version'] }}</dd>
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
            </dl>
        </x-ui.card>
    </div>

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

    @if (count($document['version_history']) > 1)
        <x-ui.card title="Versiyon Geçmişi" class="mt-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Versiyon</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Dosya</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Yüklenme</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Geçerlilik</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach ($document['version_history'] as $version)
                            <tr>
                                <td class="py-2">
                                    <span class="font-medium text-gray-900 dark:text-white">v{{ $version['version'] }}</span>
                                    @if ($version['is_current'])
                                        <x-ui.badge variant="primary" class="ml-2">Güncel</x-ui.badge>
                                    @endif
                                </td>
                                <td class="py-2 text-gray-600 dark:text-slate-400">{{ $version['file_name'] }}</td>
                                <td class="py-2 text-gray-600 dark:text-slate-400">{{ $version['uploaded_at_formatted'] }}</td>
                                <td class="py-2 text-gray-600 dark:text-slate-400">{{ $version['expiry_date_formatted'] }}</td>
                                <td class="py-2">
                                    <x-agency.document-status-badge :status="$version['status']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('agencies.documents.index') }}" variant="secondary">
            Listeye Dön
        </x-ui.button>
    </div>
</div>
@endsection
