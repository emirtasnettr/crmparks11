@extends('layouts.app')

@section('title', 'Hakediş Detayı')

@section('breadcrumb')
    <a href="{{ route('agencies.index') }}" class="hover:text-gray-900 dark:hover:text-white">Acenteler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('agencies.earnings.index') }}" class="hover:text-gray-900 dark:hover:text-white">Hakedişler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $earning['reference'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hakediş Detayı</h1>
                <x-agency.payment-status-badge :status="$earning['payment_status']" />
                <x-agency.earning-status-badge :status="$earning['status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $earning['agency_name'] }} — {{ $earning['period_label'] }}
            </p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <x-ui.button variant="secondary">Düzenle</x-ui.button>
            <x-ui.button href="{{ route('agencies.earnings.pdf', $earning['id']) }}" variant="secondary">PDF Oluştur</x-ui.button>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.finance-stat-card title="Brüt Hakediş" :value="money_excl_vat($earning['gross_amount'])" accent="blue" />
        <x-ui.finance-stat-card title="Kesinti" :value="money_excl_vat($earning['deduction'])" accent="danger" />
        <x-ui.finance-stat-card title="Net Ödeme" :value="money_excl_vat($earning['net_payment'])" accent="success" />
        <x-ui.finance-stat-card title="Toplam Paket" :value="number_format($earning['package_count'])" accent="violet" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Acente Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $earning['agency_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Yetkili</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_authorized'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Şehir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_city'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">E-posta</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_email'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Hakediş Özeti">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Referans No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $earning['reference'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Dönem</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['period_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Dönem Tipi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['period_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Bağlı Kurye Sayısı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ money_excl_vat($earning['courier_count']) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Brüt Hakediş</dt>
                    <dd class="font-semibold text-gray-900 dark:text-white">{{ number_format($earning['gross_amount']) }}</dd>
                </div>
                <div class="flex justify-between gap-4 border-t border-gray-100 pt-3 dark:border-slate-700">
                    <dt class="font-semibold text-gray-900 dark:text-white">Net Ödeme</dt>
                    <dd class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money_excl_vat($earning['net_payment']) }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Bağlı Kuryeler" class="lg:col-span-2">
            @if (count($earning['linked_couriers']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                                <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-400">Paket</th>
                                <th class="pb-2 text-right font-medium text-gray-500 dark:text-slate-400">Hakediş Payı</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($earning['linked_couriers'] as $courier)
                                <tr>
                                    <td class="py-2 font-medium text-gray-900 dark:text-white">{{ $courier['name'] }}</td>
                                    <td class="py-2 text-right text-gray-600 dark:text-slate-400">{{ money_excl_vat($courier['package_count']) }}</td>
                                    <td class="py-2 text-right font-medium text-gray-900 dark:text-white">{{ number_format($courier['gross_share']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bağlı kurye bulunmuyor.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Ek Ödemeler">
            @if (count($earning['extra_payments']) > 0)
                <dl class="space-y-3 text-sm">
                    @foreach ($earning['extra_payments'] as $item)
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500 dark:text-slate-400">{{ $item['label'] }}</dt>
                            <dd class="font-medium text-emerald-600 dark:text-emerald-400">+{{ money_excl_vat($item['amount']) }}</dd>
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
                            <dt class="text-gray-500 dark:text-slate-400">{{ $item['label'] }}</dt>
                            <dd class="font-medium text-red-600 dark:text-red-400">−{{ money_excl_vat($item['amount']) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Kesinti bulunmuyor.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Ödeme Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Durumu</dt>
                    <dd><x-agency.payment-status-badge :status="$earning['payment_status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Hakediş Durumu</dt>
                    <dd><x-agency.earning-status-badge :status="$earning['status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödeme Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $earning['payment_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ödenen Tutar</dt>
                    <dd class="font-medium text-emerald-600 dark:text-emerald-400">{{ money_excl_vat($earning['paid_amount']) }}</dd>
                </div>
                @if ($earning['payment_status'] === 'partial')
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Kalan Tutar</dt>
                        <dd class="font-medium text-amber-600 dark:text-amber-400">{{ money_excl_vat($earning['remaining_payment']) }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    </div>

    @if ($earning['description'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $earning['description'] }}</p>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('agencies.earnings.index') }}" variant="secondary">Listeye Dön</x-ui.button>
    </div>
</div>
@endsection
