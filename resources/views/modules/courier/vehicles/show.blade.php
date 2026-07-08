@extends('layouts.app')

@section('title', 'Araç Detayı')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('couriers.vehicles.index') }}" class="hover:text-gray-900 dark:hover:text-white">Araç Bilgileri</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $vehicle['plate_formatted'] !== '—' ? $vehicle['plate_formatted'] : $vehicle['vehicle_type_label'] }}</span>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Araç Detayı</h1>
                <x-courier.vehicle-status-badge :status="$vehicle['status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $vehicle['courier_name'] }} — {{ $vehicle['brand_formatted'] }} {{ $vehicle['model_formatted'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($vehicle['status'] === 'active')
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
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['courier_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['courier_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd><x-business.courier-type-badge :type="$vehicle['courier_type']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Araç Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Araç Tipi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['vehicle_type_label'] }}</dd>
                </div>
                @if ($vehicle['plate'])
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Plaka</dt>
                        <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $vehicle['plate'] }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka / Model</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $vehicle['brand_formatted'] }} {{ $vehicle['model_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Model Yılı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['model_year_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Renk</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['color_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kayıt Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['registered_at_formatted'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        @if ($vehicle['requires_vehicle_docs'])
            <x-ui.card title="Ruhsat Bilgileri">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Ruhsat No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $vehicle['license_number'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                        <dd>
                            @if ($vehicle['license_status'])
                                <x-courier.vehicle-license-status-badge :status="$vehicle['license_status']" />
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Sigorta Bilgileri">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Poliçe No</dt>
                        <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $vehicle['insurance_policy_number'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Bitiş Tarihi</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $vehicle['insurance_expiry_formatted'] }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                        <dd>
                            @if ($vehicle['insurance_status'])
                                <x-courier.vehicle-insurance-status-badge :status="$vehicle['insurance_status']" />
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.card>
        @endif
    </div>

    @if ($vehicle['notes'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $vehicle['notes'] }}</p>
        </x-ui.card>
    @endif

    <x-ui.card title="Araç Geçmişi" class="mt-6">
        <div class="space-y-6">
            @foreach ($vehicle['history'] as $event)
                <div class="flex gap-4">
                    <div class="flex shrink-0 flex-col items-center">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-600/10 dark:text-primary-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1 border-b border-gray-100 pb-4 last:border-0 last:pb-0 dark:border-slate-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $event['label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $event['date_formatted'] }}</p>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">{{ $event['detail'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        @if (count($courierVehicles) > 1)
            <div class="mt-6 border-t border-gray-200 pt-6 dark:border-slate-700">
                <p class="mb-4 text-sm font-medium text-gray-900 dark:text-white">Kuryenin Diğer Araçları</p>
                <div class="space-y-3">
                    @foreach ($courierVehicles as $item)
                        @if ($item['id'] !== $vehicle['id'])
                            <a
                                href="{{ route('couriers.vehicles.show', $item['id']) }}"
                                class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 transition-colors hover:bg-gray-50 dark:border-slate-700 dark:hover:bg-slate-800/50"
                            >
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item['brand_formatted'] }} {{ $item['model_formatted'] }}
                                        <span class="font-normal text-gray-500">({{ $item['vehicle_type_label'] }})</span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $item['plate_formatted'] }} · {{ $item['registered_at_formatted'] }}</p>
                                </div>
                                <x-courier.vehicle-status-badge :status="$item['status']" />
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.card>

    <div class="mt-6">
        <x-ui.button href="{{ route('couriers.vehicles.index') }}" variant="secondary">Listeye Dön</x-ui.button>
    </div>
</div>
@endsection
