<x-ui.modal title="Yeni Sözleşme">
    <form
        method="POST"
        action="{{ $formAction ?? route('agencies.contracts.store') }}"
        enctype="multipart/form-data"
        @submit="handleSubmit($event)"
        class="space-y-4"
    >
        @csrf
        @php
            $hideEntitySelector = $hideEntitySelector ?? false;
            $presetEntityLabel = $presetEntityLabel ?? null;
            $redirectToAgency = $redirectToAgency ?? false;
        @endphp

        @if ($redirectToAgency)
            <input type="hidden" name="redirect_to_agency" value="1">
        @endif

        @if ($hideEntitySelector)
            <x-entity.locked-field label="Acente" :value="$presetEntityLabel" />
            <input type="hidden" name="agency_id" value="{{ $lockedAgencyId ?? '' }}" x-bind:value="modal.agency_id || '{{ $lockedAgencyId ?? '' }}'">
        @else
            <div class="space-y-1.5">
                <label for="modal_agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Acente *</label>
                <select
                    id="modal_agency_id"
                    name="agency_id"
                    x-model="modal.agency_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="modalErrors.agency_id ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Acente seçin</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency['id'] }}">{{ $agency['name'] }}</option>
                    @endforeach
                </select>
                <p x-show="modalErrors.agency_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.agency_id"></p>
            </div>
        @endif

        <x-ui.input
            name="contract_number"
            label="Sözleşme No"
            placeholder="ACS-2026-000"
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

        <x-ui.file-upload
            name="contract_file"
            label="Dosya Yükle (PDF)"
            accept="application/pdf,.pdf"
            :max-size-mb="config('crmlog.upload.max_size_mb')"
            :hint="'PDF (maks. '.config('crmlog.upload.max_size_mb').'MB)'"
        />

        <input type="hidden" name="auto_renewal" :value="modal.auto_renewal ? 1 : 0">
        <x-ui.toggle name="auto_renewal_toggle" label="Otomatik Yenileme" x-model="modal.auto_renewal" />

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
            <x-ui.button type="button" variant="secondary" @click="closeModal">İptal</x-ui.button>
            <x-ui.button type="submit" ::disabled="submitting">Kaydet</x-ui.button>
        </div>
    </form>
</x-ui.modal>
