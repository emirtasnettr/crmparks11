@extends('layouts.app')

@section('title', 'İşletmeler')


@section('content')
<div x-data="businessListPage(@js($businessesForModal))" @business-detail.window="openDetail($event.detail)">
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">İşletmeler</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Sistemde kayıtlı tüm işletmeleri buradan yönetebilirsiniz.
        </p>
    </div>

    <x-ui.button href="{{ route('businesses.create') }}">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Yeni İşletme
    </x-ui.button>
</div>

{{-- Filtre Alanı --}}
<x-ui.card :padding="false">
    <form method="GET" action="{{ route('businesses.index') }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.input
                name="search"
                label="İşletme Ara"
                placeholder="Firma ünvanı, marka adı veya telefon"
                :value="$filters['search']"
            />

            <x-ui.select
                name="status"
                label="Durum"
                :selected="$filters['status']"
                :options="array_merge(['all' => 'Tümü'], $statuses)"
            />

            <x-ui.select
                name="city"
                label="İl"
                :selected="$filters['city']"
                :options="array_merge(['all' => 'Tümü'], collect($cities)->mapWithKeys(fn ($c) => [$c => $c])->all())"
            />

            <x-ui.select
                name="pricing_model"
                label="Çalışma Modeli"
                :selected="$filters['pricing_model']"
                :options="[
                    'all' => 'Tümü',
                    'per_package' => 'Paket Başı',
                    'fixed' => 'Sabit Ücret',
                    'hourly' => 'Saatlik',
                    'daily' => 'Günlük',
                ]"
            />
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('businesses.index') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

{{-- Tablo --}}
<x-ui.card :padding="false" class="mt-6">
  {{-- Tablo Üstü --}}
  <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
    <p class="text-sm font-medium text-gray-900 dark:text-white">
      <span class="text-lg font-bold">{{ number_format($total) }}</span>
      İşletme
    </p>

    <x-ui.export-button :href="route('businesses.export', request()->query())" />
  </div>

  <div class="overflow-x-auto">
    <table class="w-full min-w-[1100px] text-left text-sm">
      <thead>
        <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Marka Adı</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Firma Ünvanı</th>
          @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletmeden Alınan Ücret</th>
          @endif
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kuryeye Verilen Ücret</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İl / İlçe</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Çalışma Modeli</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktif Kurye</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
          <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
        @forelse ($businesses as $business)
          <tr
            role="link"
            tabindex="0"
            class="cursor-pointer transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50"
            data-href="{{ route('businesses.show', $business['id']) }}"
            onclick="window.location.href = this.dataset.href"
            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = this.dataset.href; }"
          >
            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
              {{ $business['display_name'] ?? $business['brand_name'] }}
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
              {{ $business['company_name'] }}
            </td>
            @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
              <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white">
                {{ $business['customer_price_label'] }}
              </td>
            @endif
            <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white">
              {{ $business['courier_price_label'] }}
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
              {{ $business['phone'] }}
            </td>
            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
              {{ $business['city'] }} / {{ $business['district'] }}
            </td>
            <td class="px-4 py-3">
              <x-business.pricing-badge :model="$business['pricing_model']" />
            </td>
            <td class="px-4 py-3 text-gray-900 dark:text-white">
              {{ $business['active_couriers'] }}
            </td>
            <td class="px-4 py-3">
              <x-business.status-badge :status="$business['status']" />
            </td>
            <td class="px-4 py-3 sm:px-6" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
              <x-business.row-actions :business="$business" />
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
              Filtrelere uygun işletme bulunamadı.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <x-ui.pagination
    :total="$total"
    :page="$page"
    :per-page="$perPage"
    :last-page="$lastPage"
  />
</x-ui.card>

@include('modules.business.partials.detail-modal')
</div>
@endsection
