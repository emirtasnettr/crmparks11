@extends('layouts.app')

@section('title', $business['display_name'] ?? $business['brand_name'])

@php
    use App\Modules\Business\Support\BusinessCardVisibility;

    $canViewRestrictedTabs = BusinessCardVisibility::canViewRestrictedTabs();
    $defaultTab = 'overview';
@endphp

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <x-ui.entity-avatar
                :url="$business['logo_url'] ?? null"
                :initials="$business['logo']"
                :color="$business['logo_color']"
                shape="rounded-2xl"
                size="h-16 w-16"
                text-size="text-lg"
                :alt="($business['display_name'] ?? $business['brand_name']).' logosu'"
            />
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $business['display_name'] ?? $business['brand_name'] }}</h1>
                    <x-business.status-badge :status="$business['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ $business['company_name'] }} · {{ $business['location'] }} · {{ $business['active_couriers'] }} aktif / {{ number_format($business['planned_courier_count'] ?? 0) }} planlanan kurye
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (BusinessCardVisibility::canManageBusinessProfile())
                <x-ui.button variant="secondary" href="{{ route('businesses.edit', $business['id']) }}">Düzenle</x-ui.button>
            @endif
            @if ($business['can_delete'] ?? false)
                <form
                    method="POST"
                    action="{{ route('businesses.destroy', $business['id']) }}"
                    onsubmit="return confirm('İşletme kalıcı olarak silinsin mi? Bu işlem geri alınamaz.')"
                >
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Sil</x-ui.button>
                </form>
            @endif
            @can('business.view')
                @if (\App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses())
                    <x-ui.button href="{{ route('businesses.index') }}" variant="secondary">Listeye Dön</x-ui.button>
                @endif
            @endcan
        </div>
    </div>

    <x-entity.tabs :default="$defaultTab">
        <x-entity.tab-list>
            <x-entity.tab-trigger name="overview" label="Genel Bakış" />
            @if ($canViewRestrictedTabs)
                <x-entity.tab-trigger name="contacts" label="Yetkililer" />
                <x-entity.tab-trigger name="commercial-contracts" label="Kontrat" />
                <x-entity.tab-trigger name="contracts" label="Sözleşmeler" />
                <x-entity.tab-trigger name="documents" label="Evraklar" />
                <x-entity.tab-trigger name="activities" label="Hareket Geçmişi" />
            @endif
        </x-entity.tab-list>

        <x-entity.tab-panel name="overview">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Operasyon Özeti</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                        {{ $dateFilters['range_label'] }} dönemi için {{ $overviewStats['labels']['subtitle'] }}
                    </p>
                </div>

                <form method="GET" action="{{ route('businesses.show', $business['id']) }}" class="flex flex-wrap items-end justify-end gap-2">
                    <div>
                        <label for="overview_start_date" class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Başlangıç</label>
                        <input
                            id="overview_start_date"
                            type="date"
                            name="start_date"
                            value="{{ $dateFilters['start_date'] }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label for="overview_end_date" class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Bitiş</label>
                        <input
                            id="overview_end_date"
                            type="date"
                            name="end_date"
                            value="{{ $dateFilters['end_date'] }}"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                    </div>
                    <x-ui.button type="submit" variant="secondary">Uygula</x-ui.button>
                </form>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                    <x-ui.finance-stat-card :title="$overviewStats['labels']['received']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['received_per_package'])" icon="earning" accent="success" />
                @endif
                <x-ui.finance-stat-card :title="$overviewStats['labels']['courier']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['courier_per_package'])" icon="courier" accent="warning" />
                <x-ui.finance-stat-card title="Aktif Kurye Sayısı" :value="number_format($overviewStats['active_couriers'])" icon="courier" accent="blue" />
                @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                    <x-ui.finance-stat-card :title="$overviewStats['labels']['net']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['net_per_package'])" icon="chart" accent="primary" />
                @endif
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Genel Bilgiler">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Marka Adı" :value="$business['brand_name']" />
                        <x-entity.detail-row label="Firma Ünvanı" :value="$business['company_name']" />
                        <x-entity.detail-row label="İşletme ID" :value="$business['public_id']" />
                        @if (! empty($business['earning_period_label']))
                            <x-entity.detail-row label="Fatura Periyodu" :value="$business['earning_period_label']" />
                            <x-entity.detail-row label="İlk Fatura Tarihi" :value="$business['first_invoice_date_formatted'] ?? '—'" />
                        @endif
                        <x-entity.detail-row label="Planlanan Kurye" :value="number_format($business['planned_courier_count'] ?? 0)" />
                        <x-entity.detail-row label="Aktif Kurye" :value="$business['active_couriers']" />
                        <x-entity.detail-row label="Kayıt Tarihi" :value="$business['created_at_formatted']" />
                        <x-entity.detail-row label="Durum">
                            <x-business.status-badge :status="$business['status']" />
                        </x-entity.detail-row>
                        @if (($business['status'] ?? '') === 'inactive' && ! empty($business['contract_end_date_formatted']))
                            <x-entity.detail-row label="Sözleşme Bitiş" :value="$business['contract_end_date_formatted']" />
                        @endif
                        @if (in_array($business['status'] ?? '', ['pending', 'contract_stage'], true) && ! empty($business['estimated_opening_date_formatted']))
                            <x-entity.detail-row label="Tahmini Açılış" :value="$business['estimated_opening_date_formatted']" />
                        @endif
                        @if (($business['status'] ?? '') === 'opening_stage' && ! empty($business['start_date_formatted']))
                            <x-entity.detail-row label="Başlangıç Tarihi" :value="$business['start_date_formatted']" />
                        @endif
                    </dl>
                </x-ui.card>

                <x-ui.card title="İletişim ve Adres">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Telefon" :value="$business['phone']" />
                        <x-entity.detail-row label="E-Posta" :value="$business['email']" />
                        <x-entity.detail-row label="Web Sitesi" :value="$business['website']" />
                        <x-entity.detail-row label="Vergi Dairesi" :value="$business['tax_office']" />
                        <x-entity.detail-row label="Vergi No" :value="$business['tax_number']" />
                        <x-entity.detail-row label="Konum" :value="$business['location']" />
                        <x-entity.detail-row label="Adres" :value="$business['address']" />
                        <x-entity.detail-row
                            label="Harita Konumu"
                            :value="!empty($business['has_location']) ? ($business['latitude'].', '.$business['longitude']) : 'Haritada işaretlenmedi'"
                        />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Aktif Kontrat">
                    @if (! empty($business['active_commercial_contract']))
                        @php $activeContract = $business['active_commercial_contract']; @endphp
                        <dl class="space-y-3 text-sm">
                            <x-entity.detail-row label="Kontrat Tipi" :value="$activeContract['work_type_label']" />
                            @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                                <x-entity.detail-row label="İşletmeden Alınan" :value="$activeContract['business_amount_formatted']" />
                            @endif
                            <x-entity.detail-row label="Kuryeye Verilen" :value="$activeContract['courier_amount_formatted']" />
                            @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                                <x-entity.detail-row label="Net Kazanç" :value="$activeContract['net_profit_formatted']" />
                            @endif
                            <x-entity.detail-row label="Ödeme Periyodu" :value="$activeContract['payment_period_label']" />
                            @if (($activeContract['work_type'] ?? '') === 'per_package' && ($activeContract['guaranteed_package_count'] ?? null) !== null)
                                <x-entity.detail-row label="Saatlik Garanti Paket Sayısı" :value="$activeContract['guaranteed_package_count']" />
                            @endif
                            <x-entity.detail-row label="Başlangıç" :value="$activeContract['start_date_formatted']" />
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-slate-400">
                            Aktif kontrat yok. Çalışma tipi ve ücretler Kontrat sekmesinden tanımlanır.
                        </p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Notlar">
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $business['notes'] }}</p>
                </x-ui.card>
            </div>
        </x-entity.tab-panel>

        @if ($canViewRestrictedTabs)
        <x-entity.tab-panel name="contacts" alpine-page="contactPage" :alpine-config="['businessId' => $business['id']]">
            <x-ui.card title="Yetkililer">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Yetkili" @click="openModal = true" />
                </x-slot:actions>
                @if (count($business['contacts']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Ad Soyad</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Görev</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">E-Posta</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($business['contacts'] as $contact)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $contact['full_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['title'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['phone'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['email'] }}</td>
                                        <td class="py-2.5">
                                            <x-business.contact-row-actions :contact="$contact" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Yetkili kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.business.contacts.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $business['display_name'] ?? $business['brand_name'],
                'lockedBusinessId' => $business['id'],
                'redirectToBusiness' => true,
                'titles' => $contactTitles,
                'businesses' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="commercial-contracts">
            <div
                x-data="commercialContractPage(@js([
                    'contractsById' => collect($business['commercial_contracts'] ?? [])->keyBy('id'),
                    'routes' => [
                        'store' => route('businesses.commercial-contracts.store'),
                        'update' => url('/isletmeler/kontratlar'),
                    ],
                    'today' => now()->toDateString(),
                ]))"
            >
                <x-ui.card title="Kontratlar">
                    <x-slot:actions>
                        @can('business.update')
                            <x-entity.tab-add-button label="Yeni Kontrat" @click="openCreate()" />
                        @endcan
                    </x-slot:actions>

                    @if (! empty($business['active_commercial_contract']))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-sm dark:border-emerald-900/40 dark:bg-emerald-950/20">
                            <p class="font-medium text-emerald-900 dark:text-emerald-200">Aktif kontrat</p>
                            <p class="mt-1 text-emerald-800 dark:text-emerald-300">
                                {{ $business['active_commercial_contract']['work_type_label'] }} ·
                                {{ $business['active_commercial_contract']['start_date_formatted'] }} –
                                {{ $business['active_commercial_contract']['end_date_formatted'] }} ·
                                Alınan {{ $business['active_commercial_contract']['business_amount_formatted'] }} ·
                                Verilen {{ $business['active_commercial_contract']['courier_amount_formatted'] }} ·
                                Net {{ $business['active_commercial_contract']['net_profit_formatted'] }}
                            </p>
                        </div>
                    @endif

                    @if (count($business['commercial_contracts'] ?? []))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-slate-700">
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Çalışma</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Alınan</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Verilen</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Net</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Periyot</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                    @foreach ($business['commercial_contracts'] as $commercial)
                                        <tr>
                                            <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $commercial['start_date_formatted'] }} – {{ $commercial['end_date_formatted'] }}</td>
                                            <td class="py-2.5 text-gray-900 dark:text-white">{{ $commercial['work_type_label'] }}</td>
                                            <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $commercial['business_amount_formatted'] }}</td>
                                            <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $commercial['courier_amount_formatted'] }}</td>
                                            <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $commercial['net_profit_formatted'] }}</td>
                                            <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $commercial['payment_period_label'] }}</td>
                                            <td class="py-2.5">
                                                <span @class([
                                                    'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                                    'bg-emerald-100 text-emerald-800' => $commercial['is_active'],
                                                    'bg-gray-100 text-gray-600' => ! $commercial['is_active'],
                                                ])>{{ $commercial['status_label'] }}</span>
                                            </td>
                                            <td class="py-2.5 text-right whitespace-nowrap">
                                                <a href="{{ $commercial['show_url'] }}" class="text-xs font-medium text-primary-600 hover:underline">Detay</a>
                                                @if ($commercial['can_update'] ?? false)
                                                    <button type="button" class="ml-2 text-xs font-medium text-primary-600 hover:underline" x-on:click="openEdit({{ $commercial['id'] }})">Düzenle</button>
                                                @endif
                                                @if ($commercial['is_active'])
                                                    @can('business.update')
                                                        <form method="POST" action="{{ route('businesses.commercial-contracts.end', $commercial['id']) }}" class="inline" onsubmit="return confirm('Kontrat sonlandırılsın mı? Geçmiş kayıtlar korunur.')">
                                                            @csrf
                                                            <button type="submit" class="ml-2 text-xs font-medium text-rose-600 hover:underline">Sonlandır</button>
                                                        </form>
                                                    @endcan
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-slate-400">Henüz kontrat yok. Vardiya hakedişi için aktif kontrat tanımlayın.</p>
                    @endif
                </x-ui.card>

                @include('modules.business.commercial-contracts.partials.modal', [
                    'presetBusinessId' => $business['id'],
                    'presetBusinessLabel' => $business['display_name'] ?? $business['brand_name'],
                    'workTypes' => $commercialWorkTypes,
                    'paymentPeriods' => $commercialPaymentPeriods,
                ])
            </div>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="contracts" alpine-page="contractPage" :alpine-config="['businessId' => $business['id']]">
            <x-ui.card title="Sözleşmeler">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Sözleşme" @click="openModal = true" />
                </x-slot:actions>
                @if (count($business['contracts']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Sözleşme No</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Başlangıç</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Bitiş</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($business['contracts'] as $contract)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $contract['contract_number'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['contract_type_label'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['start_date_formatted'] ?? $contract['start_date'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['end_date_formatted'] ?? $contract['end_date'] }}</td>
                                        <td class="py-2.5">
                                            <x-business.contract-status-badge :status="$contract['status']" />
                                        </td>
                                        <td class="py-2.5">
                                            <x-business.contract-row-actions :contract="$contract" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Sözleşme kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.business.contracts.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $business['display_name'] ?? $business['brand_name'],
                'lockedBusinessId' => $business['id'],
                'redirectToBusiness' => true,
                'contractTypes' => $contractTypes,
                'businesses' => [],
            ])
        </x-entity.tab-panel>
        @endif

        @if ($canViewRestrictedTabs)
        <x-entity.tab-panel name="documents" alpine-page="documentPage" :alpine-config="['businessId' => $business['id'], 'maxSizeMb' => config('crmlog.upload.max_size_mb')]">
            <x-ui.card title="Evraklar">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Evrak Yükle" @click="openModal = true" />
                </x-slot:actions>
                @if (count($business['documents']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Evrak Adı</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Dosya</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Yükleyen</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($business['documents'] as $document)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $document['name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['document_type_label'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['file_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['uploaded_by'] }}</td>
                                        <td class="py-2.5">
                                            <x-business.document-row-actions :document="$document" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Evrak kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.business.documents.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $business['display_name'] ?? $business['brand_name'],
                'lockedBusinessId' => $business['id'],
                'redirectToBusiness' => true,
                'documentTypes' => $documentTypes,
                'businesses' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="activities">
            <x-ui.card title="Hareket Geçmişi">
                @if (count($business['activities']))
                    <div class="space-y-3">
                        @foreach ($business['activities'] as $activity)
                            <div class="flex flex-col gap-1 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action_label'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $activity['occurred_at_formatted'] }} · {{ $activity['user_name'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Hareket kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
        </x-entity.tab-panel>
        @endif
    </x-entity.tabs>
</div>
@endsection
