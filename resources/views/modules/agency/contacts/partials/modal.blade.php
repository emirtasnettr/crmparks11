<x-ui.modal title="Yeni Yetkili">
    <form
        method="POST"
        action="{{ $formAction ?? route('agencies.contacts.store') }}"
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-ui.input name="first_name" label="Ad *" x-model="modal.first_name" />
                <p x-show="modalErrors.first_name" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="modalErrors.first_name"></p>
            </div>
            <div>
                <x-ui.input name="last_name" label="Soyad *" x-model="modal.last_name" />
                <p x-show="modalErrors.last_name" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="modalErrors.last_name"></p>
            </div>
        </div>

        <div class="space-y-1.5">
            <label for="modal_title" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Görev *</label>
            <select
                id="modal_title"
                name="title"
                x-model="modal.title"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.title ? 'border-red-300 dark:border-red-500' : ''"
            >
                <option value="">Görev seçin</option>
                @foreach ($titles as $title)
                    <option value="{{ $title }}">{{ $title }}</option>
                @endforeach
            </select>
            <p x-show="modalErrors.title" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.title"></p>
        </div>

        <x-ui.input name="phone" type="tel" label="Telefon *" x-model="modal.phone" />
        <p x-show="modalErrors.phone" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.phone"></p>

        <x-ui.input name="email" type="email" label="E-Posta" x-model="modal.email" />

        <x-ui.toggle name="is_default" label="Varsayılan Yetkili" x-model="modal.is_default" />

        <div class="space-y-1.5">
            <label for="modal_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
            <select
                id="modal_status"
                name="status"
                x-model="modal.status"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
            >
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
            </select>
        </div>

        <x-ui.textarea name="notes" label="Notlar" rows="3" x-model="modal.notes" />

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" x-bind:disabled="submitting">
                Kaydet
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
