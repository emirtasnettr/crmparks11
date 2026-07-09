<x-ui.modal title="Yeni Sözleşme">
    <form
        method="POST"
        action="{{ $formAction ?? route('businesses.contracts.store') }}"
        enctype="multipart/form-data"
        @submit="handleSubmit($event)"
        class="space-y-4"
    >
        @csrf
        @php
            $hideEntitySelector = $hideEntitySelector ?? false;
            $presetEntityLabel = $presetEntityLabel ?? null;
            $redirectToBusiness = $redirectToBusiness ?? false;
        @endphp

        @if ($redirectToBusiness)
            <input type="hidden" name="redirect_to_business" value="1">
        @endif

        @if ($hideEntitySelector)
            <x-entity.locked-field label="İşletme" :value="$presetEntityLabel" />
            <input type="hidden" name="business_id" value="{{ $lockedBusinessId ?? '' }}" x-bind:value="modal.business_id || '{{ $lockedBusinessId ?? '' }}'">
        @else
            <div class="space-y-1.5">
                <label for="modal_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select
                    id="modal_business_id"
                    name="business_id"
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

        <x-ui.input
            name="contract_number"
            label="Sözleşme No"
            placeholder="SZL-2026-000"
            x-model="modal.contract_number"
        />

        <div class="space-y-1.5">
            <label for="modal_contract_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Sözleşme Türü</label>
            <select
                id="modal_contract_type"
                name="contract_type"
                x-model="modal.contract_type"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="">Tür seçin</option>
                @foreach ($contractTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1.5">
                <label for="modal_start_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                <input
                    id="modal_start_date"
                    name="start_date"
                    type="date"
                    x-model="modal.start_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.start_date ? 'border-red-300 dark:border-red-500' : ''"
                />
                <p x-show="modalErrors.start_date" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.start_date"></p>
            </div>

            <div class="space-y-1.5">
                <label for="modal_end_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Tarihi *</label>
                <input
                    id="modal_end_date"
                    name="end_date"
                    type="date"
                    x-model="modal.end_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.end_date ? 'border-red-300 dark:border-red-500' : ''"
                />
                <p x-show="modalErrors.end_date" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.end_date"></p>
            </div>
        </div>

        <x-ui.file-upload name="contract_file" label="Dosya Yükle (PDF)" accept="application/pdf,.pdf" />

        <x-ui.textarea name="notes" label="Notlar" rows="3" x-model="modal.notes" />

        <div class="space-y-1.5">
            <label for="modal_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
            <select
                id="modal_status"
                name="status"
                x-model="modal.status"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="draft">Taslak</option>
                <option value="active">Aktif</option>
            </select>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" ::disabled="submitting">
                Kaydet
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
