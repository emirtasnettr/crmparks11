@extends('layouts.app')

@section('title', $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'])


@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <x-ui.entity-avatar
                :url="$agency['logo_url'] ?? null"
                :initials="$agency['logo']"
                :color="$agency['logo_color']"
                shape="rounded-2xl"
                size="h-16 w-16"
                text-size="text-lg"
                :alt="($agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name']).' logosu'"
            />
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'] }}</h1>
                    <x-agency.status-badge :status="$agency['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ $agency['company_name'] }} · {{ $agency['authorized_person'] }} · {{ $agency['location'] }} · {{ $agency['active_couriers'] }} kurye
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" href="{{ route('agencies.edit', $agency['id']) }}">Düzenle</x-ui.button>
            <x-ui.button href="{{ route('agencies.index') }}" variant="secondary">Listeye Dön</x-ui.button>
        </div>
    </div>

    <x-entity.tabs default="overview">
        <x-entity.tab-list>
            <x-entity.tab-trigger name="overview" label="Genel Bakış" />
            <x-entity.tab-trigger name="contacts" label="Yetkililer" />
            <x-entity.tab-trigger name="couriers" label="Kuryeler" />
            <x-entity.tab-trigger name="contracts" label="Sözleşmeler" />
            <x-entity.tab-trigger name="documents" label="Evraklar" />
            <x-entity.tab-trigger name="activities" label="Hareket Geçmişi" />
        </x-entity.tab-list>

        <x-entity.tab-panel name="overview">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Firma Bilgileri">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Marka Adı" :value="$agency['brand_name']" />
                        <x-entity.detail-row label="Firma Ünvanı" :value="$agency['company_name']" />
                        <x-entity.detail-row label="Yetkili Kişi" :value="$agency['authorized_person']" />
                        <x-entity.detail-row label="Kayıt No" :value="$agency['uuid']" />
                        <x-entity.detail-row label="Aktif Kurye" :value="$agency['active_couriers']" />
                        <x-entity.detail-row label="Aktif İşletme" :value="$agency['active_businesses']" />
                        <x-entity.detail-row label="Kayıt Tarihi" :value="$agency['created_at_formatted']" />
                        <x-entity.detail-row label="Durum">
                            <x-agency.status-badge :status="$agency['status']" />
                        </x-entity.detail-row>
                    </dl>
                </x-ui.card>

                <x-ui.card title="İletişim ve Adres">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Telefon" :value="$agency['phone']" />
                        <x-entity.detail-row label="Vergi No" :value="$agency['tax_number']" />
                        <x-entity.detail-row label="Konum" :value="$agency['location']" />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Notlar">
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $agency['notes'] }}</p>
                </x-ui.card>
            </div>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="contacts" alpine-page="agencyContactPage" :alpine-config="['agencyId' => $agency['id']]">
            <x-ui.card title="Yetkililer">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Yetkili" @click="openModal = true" />
                </x-slot:actions>
                @if (count($agency['contacts']))
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
                                @foreach ($agency['contacts'] as $contact)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $contact['full_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['title'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['phone'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contact['email'] }}</td>
                                        <td class="py-2.5">
                                            <x-agency.contact-row-actions :contact="$contact" />
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
            @include('modules.agency.contacts.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'],
                'lockedAgencyId' => $agency['id'],
                'redirectToAgency' => true,
                'titles' => $contactTitles,
                'agencies' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel
            name="couriers"
            alpine-page="agencyCourierPage"
            :alpine-config="['agencyId' => $agency['id']]"
            @agency-courier-detail.window="openDetail($event.detail)"
        >
            <x-ui.card title="Kuryeler">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Kurye Ata" @click="openAssignModal = true" />
                </x-slot:actions>
                @if (count($agency['couriers']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Araç</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($agency['couriers'] as $record)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $record['courier_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $record['phone'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $record['vehicle_info'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $record['status'] ?? '—' }}</td>
                                        <td class="py-2.5">
                                            <x-agency.courier-row-actions :record="$record" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Kurye kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.agency.couriers.partials.assign-modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'],
                'lockedAgencyId' => $agency['id'],
                'redirectToAgency' => true,
                'couriers' => $assignCouriers,
                'agencies' => [],
            ])
            @include('modules.agency.couriers.partials.detail-modal')
        </x-entity.tab-panel>

        <x-entity.tab-panel name="contracts" alpine-page="agencyContractPage" :alpine-config="['agencyId' => $agency['id']]">
            <x-ui.card title="Sözleşmeler">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Sözleşme" @click="openModal = true" />
                </x-slot:actions>
                @if (count($agency['contracts']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Sözleşme No</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Başlangıç</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Bitiş</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($agency['contracts'] as $contract)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $contract['contract_number'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['contract_type_label'] ?? $contract['contract_type'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['start_date_formatted'] ?? $contract['start_date'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $contract['end_date_formatted'] ?? $contract['end_date'] }}</td>
                                        <td class="py-2.5">
                                            <x-agency.contract-row-actions :contract="$contract" />
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
            @include('modules.agency.contracts.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'],
                'lockedAgencyId' => $agency['id'],
                'redirectToAgency' => true,
                'contractTypes' => $contractTypes,
                'agencies' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="documents" alpine-page="agencyDocumentPage" :alpine-config="['agencyId' => $agency['id'], 'maxSizeMb' => config('crmlog.upload.max_size_mb')]">
            <x-ui.card title="Evraklar">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Evrak Yükle" @click="openModal = true" />
                </x-slot:actions>
                @if (count($agency['documents']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Belge Türü</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Belge No</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Dosya</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($agency['documents'] as $document)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] ?? $document['document_type'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['document_number'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['file_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['status_label'] ?? ($document['computed_status'] ?? '—') }}</td>
                                        <td class="py-2.5">
                                            <x-agency.document-row-actions :document="$document" />
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
            @include('modules.agency.documents.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $agency['display_name'] ?? $agency['brand_name'] ?? $agency['company_name'],
                'lockedAgencyId' => $agency['id'],
                'redirectToAgency' => true,
                'documentTypes' => $documentTypes,
                'agencies' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="activities">
            <x-ui.card title="Hareket Geçmişi">
                @if (count($agency['activities']))
                    <div class="space-y-3">
                        @foreach ($agency['activities'] as $activity)
                            <div class="flex flex-col gap-1 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action_label'] ?? $activity['action'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-slate-400">
                                    {{ $activity['occurred_at_formatted'] ?? $activity['occurred_at'] }}
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
