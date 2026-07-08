@extends('layouts.app')

@section('title', $courier['full_name'])

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $courier['full_name'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <x-ui.entity-avatar
                :url="$courier['photo_url'] ?? null"
                :initials="$courier['avatar_initials']"
                :color="$courier['avatar_color']"
                shape="rounded-full"
                size="h-16 w-16"
                text-size="text-lg"
                :alt="$courier['full_name'].' profil fotoğrafı'"
            />
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $courier['full_name'] }}</h1>
                    <x-courier.status-badge :status="$courier['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    {{ $courier['courier_type_label'] }} · {{ $courier['vehicle_type_label'] }} · {{ $courier['phone'] }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button variant="secondary" href="{{ route('couriers.edit', $courier['id']) }}">Düzenle</x-ui.button>
            <x-ui.button href="{{ route('couriers.index') }}" variant="secondary">Listeye Dön</x-ui.button>
        </div>
    </div>

    <x-entity.tabs default="overview">
        <x-entity.tab-list>
            <x-entity.tab-trigger name="overview" label="Genel Bakış" />
            <x-entity.tab-trigger name="work_history" label="Çalışma Geçmişi" />
            <x-entity.tab-trigger name="documents" label="Belgeler" />
            <x-entity.tab-trigger name="bank_accounts" label="Banka Bilgileri" />
            <x-entity.tab-trigger name="vehicles" label="Araç Bilgileri" />
            <x-entity.tab-trigger name="activities" label="Hareket Geçmişi" />
        </x-entity.tab-list>

        <x-entity.tab-panel name="overview">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card title="Kişisel Bilgiler">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Ad Soyad" :value="$courier['full_name']" />
                        <x-entity.detail-row label="T.C. Kimlik No" :value="$courier['tc_number']" />
                        <x-entity.detail-row label="Doğum Tarihi" :value="$courier['birth_date_formatted']" />
                        <x-entity.detail-row label="Telefon" :value="$courier['phone']" />
                        <x-entity.detail-row label="E-Posta" :value="$courier['email']" />
                        <x-entity.detail-row label="Kayıt No" :value="$courier['uuid']" />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Kurye Bilgileri">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Kurye Tipi" :value="$courier['courier_type_label']" />
                        <x-entity.detail-row label="Bağlı Acente" :value="$courier['agency_name'] ?? '—'" />
                        <x-entity.detail-row label="Aktif İşletme" :value="$courier['active_business_name'] ?? '—'" />
                        <x-entity.detail-row label="Araç Tipi" :value="$courier['vehicle_type_label']" />
                        <x-entity.detail-row label="İşe Başlama" :value="$courier['start_date_formatted']" />
                        <x-entity.detail-row label="Durum">
                            <x-courier.status-badge :status="$courier['status']" />
                        </x-entity.detail-row>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Adres Bilgileri">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="İl" :value="$courier['city']" />
                        <x-entity.detail-row label="İlçe" :value="$courier['district']" />
                        <x-entity.detail-row label="Adres" :value="$courier['address']" />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Vergi ve Şirket">
                    <dl class="space-y-3 text-sm">
                        <x-entity.detail-row label="Vergi Dairesi" :value="$courier['tax_office'] ?? '—'" />
                        <x-entity.detail-row label="Vergi No" :value="$courier['tax_number'] ?? '—'" />
                        <x-entity.detail-row label="Şirket Ünvanı" :value="$courier['company_name'] ?? '—'" />
                    </dl>
                </x-ui.card>

                <x-ui.card title="Aktif Araç">
                    @if ($courier['active_vehicle'])
                        <dl class="space-y-3 text-sm">
                            <x-entity.detail-row label="Plaka" :value="$courier['active_vehicle']['plate']" />
                            <x-entity.detail-row label="Marka / Model" :value="($courier['active_vehicle']['brand'] ?? '').' / '.($courier['active_vehicle']['model'] ?? '')" />
                            <x-entity.detail-row label="Model Yılı" :value="$courier['active_vehicle']['model_year']" />
                            <x-entity.detail-row label="Sigorta Bitiş" :value="$courier['active_vehicle']['insurance_expiry_date_formatted'] ?? $courier['active_vehicle']['insurance_expiry_date'] ?? '—'" />
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-slate-400">Kayıtlı araç bulunmuyor.</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Varsayılan Banka Hesabı">
                    @if ($courier['default_bank'])
                        <dl class="space-y-3 text-sm">
                            <x-entity.detail-row label="Banka" :value="$courier['default_bank']['bank_label'] ?? $courier['default_bank']['bank_name'] ?? $courier['default_bank']['bank_key']" />
                            <x-entity.detail-row label="Hesap Sahibi" :value="$courier['default_bank']['account_holder']" />
                            <x-entity.detail-row label="IBAN" :value="$courier['default_bank']['iban_formatted'] ?? $courier['default_bank']['iban']" />
                        </dl>
                    @else
                        <p class="text-sm text-gray-500 dark:text-slate-400">Banka hesabı bulunmuyor.</p>
                    @endif
                </x-ui.card>

                <x-ui.card title="Notlar" class="lg:col-span-2">
                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $courier['notes'] }}</p>
                </x-ui.card>
            </div>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="work_history">
            <x-ui.card title="Çalışma Geçmişi">
                @if (count($courier['work_history']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Başlangıç</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Bitiş</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($courier['work_history'] as $history)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $history['business_name'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $history['start_date_formatted'] ?? $history['start_date'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $history['end_date_formatted'] ?? ($history['end_date'] ?? '—') }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $history['work_status_label'] ?? ($history['status'] ?? '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Çalışma geçmişi bulunmuyor.</p>
                @endif
            </x-ui.card>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="documents" x-data="courierDocumentPage(@js(['courierId' => $courier['id']]))">
            <x-ui.card title="Belgeler">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Belge Yükle" @click="openModal = true" />
                </x-slot:actions>
                @if (count($courier['documents']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Belge</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Dosya</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Geçerlilik</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($courier['documents'] as $document)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['file_name'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['expiry_date_formatted'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['status_label'] ?? $document['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Belge kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.courier.documents.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $courier['full_name'],
                'documentTypes' => $documentTypes,
                'couriers' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="bank_accounts" x-data="courierBankAccountPage(@js(['courierId' => $courier['id']]))">
            <x-ui.card title="Banka Bilgileri">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Hesap" @click="openModal = true" />
                </x-slot:actions>
                @if (count($courier['bank_accounts']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Banka</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Hesap Sahibi</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">IBAN</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Varsayılan</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($courier['bank_accounts'] as $account)
                                    <tr>
                                        <td class="py-2.5 text-gray-900 dark:text-white">{{ $account['bank_name'] ?? $account['bank_key'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $account['account_holder'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $account['iban_formatted'] ?? $account['iban'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $account['is_default'] ? 'Evet' : 'Hayır' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $account['status_label'] ?? $account['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Banka hesabı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.courier.bank-accounts.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $courier['full_name'],
                'banks' => $banks,
                'statuses' => $bankStatuses,
                'couriers' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="vehicles" x-data="courierVehiclePage(@js(['courierId' => $courier['id']]))">
            <x-ui.card title="Araç Bilgileri">
                <x-slot:actions>
                    <x-entity.tab-add-button label="Yeni Araç" @click="openModal = true" />
                </x-slot:actions>
                @if (count($courier['vehicles']))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-slate-700">
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Plaka</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tür</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Marka / Model</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Sigorta Bitiş</th>
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($courier['vehicles'] as $vehicle)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $vehicle['plate'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $vehicle['vehicle_type_label'] ?? $vehicle['vehicle_type'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ ($vehicle['brand'] ?? '').' / '.($vehicle['model'] ?? '') }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $vehicle['insurance_expiry_date_formatted'] ?? $vehicle['insurance_expiry_date'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $vehicle['status_label'] ?? $vehicle['status'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-slate-400">Araç kaydı bulunmuyor.</p>
                @endif
            </x-ui.card>
            @include('modules.courier.vehicles.partials.modal', [
                'hideEntitySelector' => true,
                'presetEntityLabel' => $courier['full_name'],
                'vehicleTypes' => $vehicleTypes,
                'statuses' => $vehicleStatuses,
                'couriers' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="activities">
            <x-ui.card title="Hareket Geçmişi">
                @if (count($courier['activities']))
                    <div class="space-y-3">
                        @foreach ($courier['activities'] as $activity)
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
