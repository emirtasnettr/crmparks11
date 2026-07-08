<x-ui.modal title="Yeni Araç">
    <form @submit.prevent="saveVehicle" class="space-y-4">
        @php
            $hideEntitySelector = $hideEntitySelector ?? false;
            $presetEntityLabel = $presetEntityLabel ?? null;
        @endphp

        @if ($hideEntitySelector)
            <x-entity.locked-field label="Kurye" :value="$presetEntityLabel" />
        @else
            <div class="space-y-1.5">
                <label for="modal_courier_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye *</label>
                <select
                    id="modal_courier_id"
                    x-model="modal.courier_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.courier_id ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Kurye seçin</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier['id'] }}">{{ $courier['name'] }}</option>
                    @endforeach
                </select>
                <p x-show="modalErrors.courier_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.courier_id"></p>
            </div>
        @endif

        <div class="space-y-1.5">
            <label for="modal_vehicle_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Araç Tipi *</label>
            <select
                id="modal_vehicle_type"
                x-model="modal.vehicle_type"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.vehicle_type ? 'border-red-300 dark:border-red-500' : ''"
            >
                <option value="">Araç tipi seçin</option>
                @foreach ($vehicleTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <p x-show="modalErrors.vehicle_type" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.vehicle_type"></p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div x-show="!isPedestrian" x-cloak class="space-y-1.5 sm:col-span-2">
                <x-ui.input name="plate" label="Plaka" placeholder="34 AB 1234" x-model="modal.plate" />
            </div>

            <x-ui.input name="brand" label="Marka" x-model="modal.brand" />
            <x-ui.input name="model" label="Model" x-model="modal.model" />
            <x-ui.input name="model_year" type="number" min="1990" max="2030" label="Model Yılı" x-model="modal.model_year" />
            <x-ui.input name="color" label="Renk" x-model="modal.color" />

            <div x-show="!isPedestrian" x-cloak class="space-y-1.5 sm:col-span-2">
                <x-ui.input name="license_number" label="Ruhsat No" placeholder="RUH-34-AB-1234" x-model="modal.license_number" />
            </div>

            <div x-show="!isPedestrian" x-cloak class="space-y-1.5">
                <x-ui.input name="insurance_policy_number" label="Sigorta Poliçe No" x-model="modal.insurance_policy_number" />
            </div>

            <div x-show="!isPedestrian" x-cloak class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Sigorta Bitiş Tarihi</label>
                <input
                    type="date"
                    x-model="modal.insurance_expiry_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
            </div>

            <div class="space-y-1.5 sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select x-model="modal.status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-ui.textarea name="notes" label="Notlar" rows="3" x-model="modal.notes" />

        <div x-show="modalSaved" x-cloak>
            <x-ui.alert type="success">
                Araç bilgileri doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.
            </x-ui.alert>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">İptal</x-ui.button>
            <x-ui.button type="submit">Kaydet</x-ui.button>
        </div>
    </form>
</x-ui.modal>
