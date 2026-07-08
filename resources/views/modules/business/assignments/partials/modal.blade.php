<x-ui.modal title="Yeni Kurye Ataması">
    <form @submit.prevent="saveAssignment" class="space-y-4">
        @php
            $hideEntitySelector = $hideEntitySelector ?? false;
            $presetEntityLabel = $presetEntityLabel ?? null;
        @endphp

        @if ($hideEntitySelector)
            <x-entity.locked-field label="İşletme" :value="$presetEntityLabel" />
        @else
            <div class="space-y-1.5">
                <label for="modal_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select
                    id="modal_business_id"
                    x-model="modal.business_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.business_id ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">İşletme seçin</option>
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                    @endforeach
                </select>
                <p x-show="modalErrors.business_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.business_id"></p>
            </div>
        @endif

        <div class="space-y-1.5">
            <label for="modal_courier_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye *</label>
            <select
                id="modal_courier_id"
                x-model="modal.courier_id"
                @change="onCourierChange"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.courier_id ? 'border-red-300 dark:border-red-500' : ''"
            >
                <option value="">Kurye seçin</option>
                @foreach ($couriers as $courier)
                    <option
                        value="{{ $courier['id'] }}"
                        data-type="{{ $courier['courier_type'] }}"
                        data-agency="{{ $courier['agency_id'] ?? '' }}"
                    >
                        {{ $courier['name'] }} — {{ $courier['phone'] }}
                    </option>
                @endforeach
            </select>
            <p x-show="modalErrors.courier_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.courier_id"></p>
        </div>

        <div class="space-y-1.5">
            <label for="modal_courier_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye Tipi</label>
            <select
                id="modal_courier_type"
                x-model="modal.courier_type"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="independent">Esnaf Kurye</option>
                <option value="agency">Acente Kuryesi</option>
            </select>
        </div>

        <div x-show="modal.courier_type === 'agency'" x-cloak class="space-y-1.5">
            <label for="modal_agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Acente</label>
            <select
                id="modal_agency_id"
                x-model="modal.agency_id"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="">Acente seçin</option>
                @foreach ($agencies as $agency)
                    <option value="{{ $agency['id'] }}">{{ $agency['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
                <label for="modal_start_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                <input
                    id="modal_start_date"
                    type="date"
                    x-model="modal.start_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.start_date ? 'border-red-300 dark:border-red-500' : ''"
                />
                <p x-show="modalErrors.start_date" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.start_date"></p>
            </div>

            <div class="space-y-1.5">
                <label for="modal_end_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Tarihi (Opsiyonel)</label>
                <input
                    id="modal_end_date"
                    type="date"
                    x-model="modal.end_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
            </div>
        </div>

        <x-ui.textarea name="notes" label="Görev Notu" rows="3" x-model="modal.notes" />

        <div class="space-y-1.5">
            <label for="modal_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
            <select
                id="modal_status"
                x-model="modal.status"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
            </select>
        </div>

        <div x-show="modalSaved" x-cloak>
            <x-ui.alert type="success">
                Atama bilgileri doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.
            </x-ui.alert>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">
                İptal
            </x-ui.button>
            <x-ui.button type="submit">
                Kaydet
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
