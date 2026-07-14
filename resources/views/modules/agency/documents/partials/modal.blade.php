<x-ui.modal title="Evrak Yükle">
    <form
        method="POST"
        action="{{ $formAction ?? route('agencies.documents.store') }}"
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

        <div class="space-y-1.5">
            <label for="modal_document_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Evrak Türü *</label>
            <select
                id="modal_document_type"
                name="document_type"
                x-model="modal.document_type"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.document_type ? 'border-red-300 dark:border-red-500' : ''"
            >
                <option value="">Tür seçin</option>
                @foreach ($documentTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <p x-show="modalErrors.document_type" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.document_type"></p>
        </div>

        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Dosya Yükle *</label>
            <label class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-8 transition-colors hover:border-primary-400 hover:bg-gray-50 dark:border-slate-600 dark:hover:border-primary-500 dark:hover:bg-slate-800/50"
                :class="modalErrors.file ? 'border-red-300 dark:border-red-500' : ''"
            >
                <svg class="mb-3 h-10 w-10 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-700 dark:text-slate-300" x-text="selectedFileName || 'Dosya seçmek için tıklayın'"></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">PDF, Word veya Resim (maks. {{ config('crmlog.upload.max_size_mb') }}MB)</p>
                <input
                    type="file"
                    name="file"
                    class="sr-only"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,application/pdf,image/*"
                    @change="onFileSelect($event)"
                />
            </label>
            <p x-show="modalErrors.file" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.file"></p>
        </div>

        <div class="space-y-1.5">
            <label for="modal_expiry_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Geçerlilik Tarihi</label>
            <input
                id="modal_expiry_date"
                name="expires_at"
                type="date"
                x-model="modal.expires_at"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            />
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">İptal</x-ui.button>
            <x-ui.button type="submit" ::disabled="submitting">Yükle</x-ui.button>
        </div>
    </form>
</x-ui.modal>
