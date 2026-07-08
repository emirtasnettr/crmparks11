@extends('layouts.app')

@section('title', 'Hakediş Detayı')

@section('breadcrumb')
    <a href="{{ route('businesses.index') }}" class="hover:text-gray-900 dark:hover:text-white">İşletmeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('businesses.earnings.index') }}" class="hover:text-gray-900 dark:hover:text-white">Hakedişler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $earning['period_label'] }}</span>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hakediş Detayı</h1>
                <x-business.earning-status-badge :status="$earning['status']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $earning['business_name'] }} — {{ $earning['courier_name'] }}</p>
        </div>
        <x-ui.button variant="secondary">Düzenle</x-ui.button>
    </div>

  {{-- Üst Kartlar --}}
  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-ui.finance-stat-card title="Toplam Gelir" :value="money_excl_vat($earning['revenue'])" accent="success" />
    <x-ui.finance-stat-card title="Toplam Gider" :value="money_excl_vat($earning['total_expense'])" accent="danger" />
    <x-ui.finance-stat-card title="Toplam Kâr" :value="money_excl_vat($earning['profit'])" :accent="$earning['profit'] >= 0 ? 'violet' : 'danger'" />
    <x-ui.finance-stat-card title="Durum" :value="$earning['status_label']" accent="blue" />
  </div>

  <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
    <x-ui.card title="İşletme Bilgisi">
      <dl class="space-y-3 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Firma</dt><dd class="text-right font-medium text-gray-900 dark:text-white">{{ $earning['business_name'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Dönem</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['period_label'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Model</dt><dd><x-business.pricing-badge :model="$earning['pricing_model']" /></dd></div>
      </dl>
    </x-ui.card>

    <x-ui.card title="Kurye Bilgisi">
      <dl class="space-y-3 text-sm">
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Kurye</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['courier_name'] }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Acente</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $earning['agency_name'] !== '—' ? $earning['agency_name'] : 'Esnaf Kurye' }}</dd></div>
      </dl>
    </x-ui.card>

    <x-ui.card title="Hakediş Detayı">
      <dl class="space-y-3 text-sm">
        @if ($earning['pricing_model'] === 'per_package')
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Paket Sayısı</dt><dd class="font-medium">{{ money_excl_vat($earning['package_count']) }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Birim Gelir</dt><dd>{{ number_format($earning['revenue_unit_price']) }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Birim Kurye</dt><dd>{{ money_excl_vat($earning['courier_unit_price']) }}</dd></div>
        @else
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Sabit Gelir</dt><dd>{{ money_excl_vat($earning['revenue']) }}</dd></div>
          <div class="flex justify-between gap-4"><dt class="text-gray-500">Sabit Kurye Ödemesi</dt><dd>{{ money_excl_vat($earning['courier_payment']) }}</dd></div>
        @endif
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Ek Gelir</dt><dd class="text-emerald-600">+{{ money_excl_vat($earning['extra_income'] ?? 0) }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Ek Gider</dt><dd class="text-red-600">−{{ money_excl_vat($earning['extra_expense'] ?? 0) }}</dd></div>
        <div class="flex justify-between gap-4"><dt class="text-gray-500">Kesinti</dt><dd class="text-red-600">−{{ money_excl_vat($earning['deduction'] ?? 0) }}</dd></div>
      </dl>
    </x-ui.card>

    <x-ui.card title="Hesaplama Özeti">
      <dl class="space-y-3 text-sm">
        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-slate-700">
          <dt class="text-gray-500">İşletmeden Gelir</dt>
          <dd class="font-semibold text-emerald-600">{{ money_excl_vat($earning['revenue']) }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-slate-700">
          <dt class="text-gray-500">Kurye Ödemesi</dt>
          <dd class="font-semibold text-red-600">−{{ money_excl_vat($earning['courier_payment']) }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-slate-700">
          <dt class="text-gray-500">Ek Gider</dt>
          <dd class="text-red-600">−{{ money_excl_vat($earning['extra_expense'] ?? 0) }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-slate-700">
          <dt class="text-gray-500">Ek Gelir</dt>
          <dd class="text-emerald-600">+{{ money_excl_vat($earning['extra_income'] ?? 0) }}</dd>
        </div>
        <div class="flex justify-between gap-4 border-b border-gray-100 pb-3 dark:border-slate-700">
          <dt class="text-gray-500">Kesinti</dt>
          <dd class="text-red-600">−{{ money_excl_vat($earning['deduction'] ?? 0) }}</dd>
        </div>
        <div class="flex justify-between gap-4 pt-1">
          <dt class="font-semibold text-gray-900 dark:text-white">Net Kâr</dt>
          <dd><x-business.profit-display :amount="$earning['profit']" class="text-lg" /></dd>
        </div>
      </dl>
    </x-ui.card>
  </div>

  @if ($earning['description'])
    <x-ui.card title="Notlar" class="mt-6">
      <p class="text-sm text-gray-700 dark:text-slate-300">{{ $earning['description'] }}</p>
    </x-ui.card>
  @endif

  <div class="mt-6">
    <x-ui.button href="{{ route('businesses.earnings.index') }}" variant="secondary">Listeye Dön</x-ui.button>
  </div>
</div>
@endsection
