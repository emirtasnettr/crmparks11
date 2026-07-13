@extends('layouts.app')

@section('title', $business['display_name'] ?? $business['brand_name'])


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
            <x-ui.button variant="secondary" href="{{ route('businesses.edit', $business['id']) }}">Düzenle</x-ui.button>
            <x-ui.button href="{{ route('businesses.index') }}" variant="secondary">Listeye Dön</x-ui.button>
        </div>
    </div>

    <x-entity.tabs default="overview">
        <x-entity.tab-list>
            <x-entity.tab-trigger name="overview" label="Genel Bakış" />
            <x-entity.tab-trigger name="contacts" label="Yetkililer" />
            <x-entity.tab-trigger name="contracts" label="Sözleşmeler" />
            <x-entity.tab-trigger name="assignments" label="Atanan Kuryeler" />
            <x-entity.tab-trigger name="documents" label="Evraklar" />
            <x-entity.tab-trigger name="activities" label="Hareket Geçmişi" />
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
                <x-ui.finance-stat-card :title="$overviewStats['labels']['received']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['received_per_package'])" icon="earning" accent="success" />
                <x-ui.finance-stat-card :title="$overviewStats['labels']['courier']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['courier_per_package'])" icon="courier" accent="warning" />
                <x-ui.finance-stat-card title="Aktif Kurye Sayısı" :value="number_format($overviewStats['active_couriers'])" icon="courier" accent="blue" />
                <x-ui.finance-stat-card :title="$overviewStats['labels']['net']" :value="\App\Core\Helpers\MoneyCalculator::formatVatAmount($overviewStats['net_per_package'])" icon="chart" accent="primary" />
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Genel Bilgiler">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Marka Adı" :value="$business['brand_name']" />
                        <x-entity.detail-row label="Firma Ünvanı" :value="$business['company_name']" />
                        <x-entity.detail-row label="Kayıt No" :value="$business['uuid']" />
                        <x-entity.detail-row label="Çalışma Modeli" :value="$business['pricing_model_label']" />
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
                    </dl>
                </x-ui.card>

                <x-ui.card title="Fiyatlandırma">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row :label="$overviewStats['labels']['customer_detail']" :value="$business['customer_price']" />
                        <x-entity.detail-row :label="$overviewStats['labels']['courier_detail']" :value="$business['courier_price']" />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Notlar">
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $business['notes'] }}</p>
                </x-ui.card>
            </div>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="contacts" x-data="contactPage(@js(['businessId' => $business['id']]))">
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

        <x-entity.tab-panel name="contracts" x-data="contractPage(@js(['businessId' => $business['id']]))">
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

        <x-entity.tab-panel name="assignments" x-data="assignmentPage(@js(['businessId' => $business['id']]))">
            <x-ui.card title="Atanan Kuryeler">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Atama" @click="openModal = true" />
                </x-slot:actions>
                @if (count($business['assignments']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Başlangıç</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Bitiş</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($business['assignments'] as $assignment)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $assignment['courier_type_label'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $assignment['start_date_formatted'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $assignment['end_date_formatted'] }}</td>
                                        <td class="py-2.5">
                                            <x-business.assignment-status-badge :status="$assignment['work_status']" />
                                        </td>
                                        <td class="py-2.5">
                                            <x-business.assignment-row-actions :assignment="$assignment" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Atama kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.business.assignments.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $business['display_name'] ?? $business['brand_name'],
                'lockedBusinessId' => $business['id'],
                'redirectToBusiness' => true,
                'couriers' => $assignmentCouriers,
                'agencies' => $assignmentAgencies,
                'businesses' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="documents" x-data="documentPage(@js(['businessId' => $business['id']]))">
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
    </x-entity.tabs>
</div>
@endsection
