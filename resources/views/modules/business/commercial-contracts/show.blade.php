@extends('layouts.app')

@section('title', 'Kontrat Detayı')

@section('content')
<div
    x-data="commercialContractPage(@js([
        'contractsById' => [$contract['id'] => $contract],
        'routes' => [
            'store' => route('businesses.commercial-contracts.store'),
            'update' => url('/isletmeler/kontratlar'),
        ],
        'today' => now()->toDateString(),
    ]))"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('businesses.show', ['id' => $contract['business_id'], 'tab' => 'commercial-contracts']) }}" class="text-sm text-primary-600 hover:underline">← İşletmeye dön</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $contract['business_name'] }} · Kontrat</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $contract['start_date_formatted'] }} – {{ $contract['end_date_formatted'] }} · {{ $contract['status_label'] }}</p>
        </div>
        @if ($canEdit ?? false)
            <x-ui.button type="button" @click="openEdit({{ $contract['id'] }})">Düzenle</x-ui.button>
        @endif
    </div>

    <x-ui.card>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium text-gray-500">Çalışma Şekli</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['work_type_label'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Ödeme Periyodu</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['payment_period_label'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">İşletmeden Alınan (KDV hariç)</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['business_amount_formatted'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Kuryeye Verilen (KDV hariç)</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['courier_amount_formatted'] }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Net Kazanç (KDV hariç)</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['net_profit_formatted'] }}</dd>
            </div>
            @if (($contract['work_type'] ?? '') === 'per_package')
                <div>
                    <dt class="text-xs font-medium text-gray-500">Saatlik Garanti Paket Sayısı</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $contract['guaranteed_package_count'] ?? '—' }}</dd>
                </div>
            @endif
        </dl>
        @if ($contract['notes'])
            <p class="mt-4 text-sm text-gray-600 dark:text-slate-300">{{ $contract['notes'] }}</p>
        @endif
    </x-ui.card>

    @include('modules.business.commercial-contracts.partials.modal', [
        'presetBusinessId' => $contract['business_id'],
        'presetBusinessLabel' => $contract['business_name'],
        'workTypes' => $workTypes,
        'paymentPeriods' => $paymentPeriods,
    ])
</div>
@endsection
