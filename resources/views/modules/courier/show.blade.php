@extends('layouts.app')

@section('title', $courier['full_name'])


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
            @can('courier.update')
                <x-ui.button variant="secondary" href="{{ route('couriers.edit', $courier['id']) }}">Düzenle</x-ui.button>
            @endcan
            @if ($courier['can_delete'] ?? false)
                <form
                    method="POST"
                    action="{{ route('couriers.destroy', $courier['id']) }}"
                    onsubmit="return confirm('Kurye kalıcı olarak silinsin mi?')"
                >
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Sil</x-ui.button>
                </form>
            @endif
            <x-ui.button href="{{ route('couriers.index') }}" variant="secondary">Listeye Dön</x-ui.button>
        </div>
    </div>

    <x-entity.tabs default="overview">
        <x-entity.tab-list>
            <x-entity.tab-trigger name="overview" label="Genel Bakış" />
            <x-entity.tab-trigger name="shift_earnings" label="Vardiya / Hakediş" />
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
                        <x-entity.detail-row label="Kurye ID" :value="$courier['public_id']" />
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

        <x-entity.tab-panel name="shift_earnings">
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.finance-stat-card title="Çalışma" :value="$shiftAttendance['summary']['total_hours'].' sa'" :excl-vat="false" accent="blue" />
                <x-ui.finance-stat-card title="Vardiya Sayısı" :value="(string) $shiftAttendance['summary']['sessions']" :excl-vat="false" accent="violet" />
                <x-ui.finance-stat-card title="Vardiya Hakedişi" :value="$shiftAttendance['summary']['total_earnings_formatted']" accent="success" />
            </div>

            @if (! empty($shiftAttendance['summary']['by_pricing_model']))
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($shiftAttendance['summary']['by_pricing_model'] as $modelSummary)
                        <x-ui.finance-stat-card
                            :title="$modelSummary['pricing_model_label']"
                            :value="$modelSummary['total_earnings_formatted']"
                            :subtitle="$modelSummary['total_hours'].' sa · '.$modelSummary['sessions'].' vardiya'"
                            :accent="$modelSummary['pricing_model'] === 'hourly' ? 'warning' : 'blue'"
                        />
                    @endforeach
                </div>
            @endif

            <x-ui.card title="Vardiya Katılımları">
                <form method="GET" action="{{ route('couriers.show', $courier['id']) }}" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <input type="hidden" name="tab" value="shift_earnings">
                    <x-ui.input type="date" name="attendance_from" label="Başlangıç" :value="$shiftAttendance['from']" />
                    <x-ui.input type="date" name="attendance_to" label="Bitiş" :value="$shiftAttendance['to']" />
                    <div class="flex items-end">
                        <x-ui.button type="submit" variant="secondary" class="w-full sm:w-auto">Filtrele</x-ui.button>
                    </div>
                </form>
                <p class="mb-4 text-sm text-gray-500 dark:text-slate-400">
                    Dönem: {{ $shiftAttendance['summary']['from_formatted'] }} – {{ $shiftAttendance['summary']['to_formatted'] }}.
                    Her gün, o gün geçerli işletme kontratına göre hesaplanır (saatlik ve paket başı karışık olabilir).
                </p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşletme / Vardiya</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Model</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Başlangıç–Bitiş</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Süre</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400 text-right">Kazanç</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @forelse ($shiftAttendance['rows'] as $row)
                                <tr @class([
                                    'bg-amber-50/70 dark:bg-amber-600/10' => $row['status'] === 'in_progress',
                                ])>
                                    <td class="py-2.5 text-gray-900 dark:text-white">{{ $row['work_date_formatted'] }}</td>
                                    <td class="py-2.5">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $row['business_name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ $row['shift_name'] }}</p>
                                    </td>
                                    <td class="py-2.5">
                                        @if (! empty($row['pricing_model']))
                                            <x-business.pricing-badge :model="$row['pricing_model']" />
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-gray-600 dark:text-slate-300">
                                        {{ $row['started_at_formatted'] }}
                                        @if ($row['ended_at_formatted'] !== '—')
                                            – {{ $row['ended_at_formatted'] }}
                                        @endif
                                    </td>
                                    <td class="py-2.5 text-gray-700 dark:text-slate-300">{{ $row['worked_duration_label'] }}</td>
                                    <td class="py-2.5 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $row['earnings_formatted'] }}</td>
                                    <td class="py-2.5">
                                        <span @class([
                                            'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400' => $row['status'] === 'in_progress',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400' => $row['status'] === 'completed',
                                            'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300' => ! in_array($row['status'], ['in_progress', 'completed'], true),
                                        ])>
                                            {{ $row['status_label'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-sm text-gray-500 dark:text-slate-400">Bu dönemde vardiya katılımı yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </x-entity.tab-panel>

        <x-entity.tab-panel name="documents" alpine-page="courierDocumentPage" :alpine-config="['courierId' => $courier['id'], 'maxSizeMb' => config('crmlog.upload.max_size_mb')]">
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
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                @foreach ($courier['documents'] as $document)
                                    <tr>
                                        <td class="py-2.5 font-medium text-gray-900 dark:text-white">{{ $document['document_type_label'] }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['file_name'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['expiry_date_formatted'] ?? '—' }}</td>
                                        <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $document['status_label'] ?? $document['status'] }}</td>
                                        <td class="py-2.5">
                                            <x-courier.document-row-actions :document="$document" />
                                        </td>
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
                'lockedCourierId' => $courier['id'],
                'redirectToCourier' => true,
                'documentTypes' => $documentTypes,
                'couriers' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="bank_accounts" alpine-page="courierBankAccountPage" :alpine-config="['courierId' => $courier['id']]">
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
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
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
                                        <td class="py-2.5">
                                            <x-courier.bank-account-row-actions :account="$account" />
                                        </td>
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
                'lockedCourierId' => $courier['id'],
                'redirectToCourier' => true,
                'banks' => $banks,
                'statuses' => $bankStatuses,
                'couriers' => [],
            ])
        </x-entity.tab-panel>

        <x-entity.tab-panel name="vehicles" alpine-page="courierVehiclePage" :alpine-config="['courierId' => $courier['id']]">
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
                                    <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşlemler</th>
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
                                        <td class="py-2.5">
                                            <x-courier.vehicle-row-actions :vehicle="$vehicle" />
                                        </td>
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
                'lockedCourierId' => $courier['id'],
                'redirectToCourier' => true,
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
