@extends('layouts.app')

@section('title', 'Sözleşme Detayı')


@section('content')
<div
    x-data="contractPage(@js([
        'businessId' => $contract['business_id'],
        'contractsById' => [$contract['id'] => $contract],
        'routes' => [
            'store' => route('businesses.contracts.store'),
            'update' => url('/isletmeler/sozlesmeler'),
        ],
    ]))"
>
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $contract['contract_number'] ?? 'Taslak Sözleşme' }}
                </h1>
                <x-business.contract-status-badge :status="$contract['status']" />
                @if ($contract['is_current'])
                    <x-ui.badge variant="primary">Aktif Sözleşme</x-ui.badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $contract['contract_type_label'] }} — {{ $contract['business_name'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($contract['file_name'])
                <x-ui.button variant="secondary">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Dosyayı İndir
                </x-ui.button>
            @endif
            @can('business.update')
                @if ($contract['can_update'])
                    <x-ui.button type="button" @click="openEdit({{ $contract['id'] }})">Düzenle</x-ui.button>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Sözleşme Bilgileri --}}
        <x-ui.card title="Sözleşme Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Sözleşme No</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contract['contract_number'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Sözleşme Türü</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contract['contract_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Başlangıç Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contract['start_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Bitiş Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contract['end_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kalan Gün</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        @if ($contract['status'] === 'expired')
                            {{ abs($contract['remaining_days']) }} gün önce doldu
                        @elseif ($contract['status'] === 'draft')
                            —
                        @else
                            {{ $contract['remaining_days'] }} gün
                        @endif
                    </dd>
                </div>
                @if ($contract['notes'])
                    <div>
                        <dt class="mb-1 text-gray-500 dark:text-slate-400">Notlar</dt>
                        <dd class="text-gray-700 dark:text-slate-300">{{ $contract['notes'] }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        {{-- İşletme Bilgileri --}}
        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma Ünvanı</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $contract['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka Adı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contract['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Sözleşme Durumu</dt>
                    <dd><x-business.contract-status-badge :status="$contract['status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Güncel Sözleşme</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $contract['is_current'] ? 'Evet' : 'Hayır (Arşiv)' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    {{-- PDF Önizleme --}}
    <x-ui.card title="PDF Önizleme" class="mt-6">
        @if ($contract['file_name'])
            <div class="flex min-h-[420px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 p-8 dark:border-slate-600 dark:bg-slate-800/50">
                <svg class="mb-4 h-16 w-16 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $contract['file_name'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">PDF önizleme backend bağlantısı sonrası aktif olacaktır.</p>
                <x-ui.button variant="secondary" class="mt-4" size="sm">
                    Dosyayı İndir
                </x-ui.button>
            </div>
        @else
            <div class="flex min-h-[200px] items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 dark:border-slate-600 dark:bg-slate-800/50">
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu sözleşmeye henüz dosya yüklenmemiş.</p>
            </div>
        @endif
    </x-ui.card>

    <div class="mt-6">
        <x-ui.button href="{{ route('businesses.contracts.index') }}" variant="secondary">
            Listeye Dön
        </x-ui.button>
    </div>
</div>

@include('modules.business.contracts.partials.modal', [
    'hideEntitySelector' => true,
    'presetEntityLabel' => $contract['business_name'],
    'lockedBusinessId' => $contract['business_id'],
    'redirectToContract' => true,
    'contractTypes' => $contractTypes,
    'businesses' => $businesses,
])
</div>
@endsection
