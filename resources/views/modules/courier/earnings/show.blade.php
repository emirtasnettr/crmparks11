@extends('layouts.app')

@section('title', 'Hakediş Detayı')


@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hakediş Detayı</h1>
                <x-courier.payment-status-badge :status="$earning['payment_status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $earning['courier_name'] }} — {{ $earning['business_name'] }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @can('earning.update')
                <x-ui.button href="{{ route('businesses.earnings.show', $earning['id']) }}" variant="secondary">İşletme Kaydında Düzenle</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('couriers.show', $earning['courier_id']) }}" variant="secondary">Kurye Profili</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Hakediş Tutarı" :value="money_excl_vat($earning['earning_amount'])" accent="blue" />
        <x-ui.finance-stat-card title="Kesinti" :value="money_excl_vat($earning['deduction'])" accent="danger" />
        <x-ui.finance-stat-card title="Net Ödeme" :value="money_excl_vat($earning['net_payment'])" accent="success" />
        <x-ui.finance-stat-card title="Ödenen" :value="money_excl_vat($earning['paid_amount'])" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Kurye Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Ad Soyad</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['courier_name'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Telefon</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['courier_phone'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Kurye Tipi</dt><dd><x-business.courier-type-badge :type="$earning['courier_type']" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Acente</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_name'] !== '—' ? $earning['agency_name'] : '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Firma</dt><dd class="text-right font-medium text-gray-900 dark:text-white">{{ $earning['business_name'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Marka</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['business_brand'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Dönem</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['period_label'] }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Hakediş Özeti">
            <dl class="space-y-3 text-sm">
                @if ($earning['package_count'] > 0)
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Paket Sayısı</dt><dd class="font-medium">{{ money_excl_vat($earning['package_count']) }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Birim Ücret</dt><dd>{{ number_format($earning['unit_price']) }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Hakediş Tutarı</dt><dd class="font-semibold text-gray-900 dark:text-white">{{ money_excl_vat($earning['earning_amount']) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Ek Ödeme</dt><dd class="text-emerald-600">+{{ money_excl_vat($earning['extra_payment']) }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-gray-100 pt-3 dark:border-slate-700">
                    <dt class="font-semibold text-gray-900 dark:text-white">Net Ödeme</dt>
                    <dd class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money_excl_vat($earning['net_payment']) }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Ödeme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Durum</dt><dd><x-courier.payment-status-badge :status="$earning['payment_status']" /></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Ödeme Tarihi</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['payment_date_formatted'] }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-500">Ödenen Tutar</dt><dd class="font-medium text-emerald-600">{{ money_excl_vat($earning['paid_amount']) }}</dd></div>
                @if ($earning['payment_status'] === 'partial')
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">Kalan Tutar</dt><dd class="font-medium text-amber-600">{{ money_excl_vat($earning['remaining_payment']) }}</dd></div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card title="Ek Ödemeler">
            @if (count($earning['extra_payments']) > 0)
                <dl class="space-y-3 text-sm">
                    @foreach ($earning['extra_payments'] as $item)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">{{ $item['label'] }}</dt>
                            <dd class="font-medium text-emerald-600">+{{ money_excl_vat($item['amount']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Ek ödeme bulunmuyor.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Kesintiler">
            @if (count($earning['deductions']) > 0)
                <dl class="space-y-3 text-sm">
                    @foreach ($earning['deductions'] as $item)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">{{ $item['label'] }}</dt>
                            <dd class="font-medium text-red-600">−{{ money_excl_vat($item['amount']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Kesinti bulunmuyor.</p>
            @endif
        </x-ui.card>
    </div>

    @if ($earning['description'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $earning['description'] }}</p>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('couriers.earnings.index') }}" variant="secondary">Listeye Dön</x-ui.button>
    </div>
</div>
@endsection
