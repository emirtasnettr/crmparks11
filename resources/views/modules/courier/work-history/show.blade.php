@extends('layouts.app')

@section('title', 'Çalışma Detayı')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('couriers.work-history.index') }}" class="hover:text-gray-900 dark:hover:text-white">Çalışma Geçmişi</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $record['courier_name'] }}</span>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Çalışma Detayı</h1>
                <x-courier.work-history-status-badge :status="$record['work_status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $record['courier_name'] }} — {{ $record['business_name'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($record['is_ongoing'])
                <x-ui.button variant="secondary">Çalışmayı Sonlandır</x-ui.button>
            @endif
            <x-ui.button variant="secondary">Düzenle</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.finance-stat-card title="Başlangıç" :value="$record['start_date_formatted']" accent="blue" />
        <x-ui.finance-stat-card title="Bitiş" :value="$record['end_date_formatted']" accent="violet" />
        <x-ui.finance-stat-card title="Toplam Süre" :value="$record['work_duration']" accent="success" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Kurye Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record['courier_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record['courier_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd><x-business.courier-type-badge :type="$record['courier_type']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $record['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Bağlı Acente</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">
                        {{ $record['agency_name'] !== '—' ? $record['agency_name'] : '—' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Çalışma Özeti" class="lg:col-span-2">
            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                <div class="flex justify-between gap-4 sm:flex-col sm:gap-1">
                    <dt class="text-gray-500 dark:text-slate-400">Başlangıç Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record['start_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:flex-col sm:gap-1">
                    <dt class="text-gray-500 dark:text-slate-400">Bitiş Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $record['end_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:flex-col sm:gap-1">
                    <dt class="text-gray-500 dark:text-slate-400">Toplam Çalışma Süresi</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ $record['work_duration'] }}</dd>
                </div>
                <div class="flex justify-between gap-4 sm:flex-col sm:gap-1">
                    <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                    <dd><x-courier.work-history-status-badge :status="$record['work_status']" /></dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    @if ($record['notes'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $record['notes'] }}</p>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('couriers.work-history.index') }}" variant="secondary">Listeye Dön</x-ui.button>
    </div>
</div>
@endsection
