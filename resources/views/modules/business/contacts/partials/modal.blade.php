<x-ui.modal title="Yeni Yetkili">
    <form
        method="POST"
        action="{{ $formAction ?? route('businesses.contacts.store') }}"
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
                <label for="modal_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme</label>
                <select
                    id="modal_business_id"
                    name="business_id"
                    x-model="modal.business_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">İşletme seçin</option>
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <p x-show="modalErrors.business_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.business_id"></p>
        @endif

        <x-ui.input
            name="full_name"
            label="Ad Soyad *"
            x-model="modal.full_name"
        />
        <p x-show="modalErrors.full_name" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.full_name"></p>

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

        <x-ui.input
            name="phone"
            type="tel"
            label="Telefon *"
            x-model="modal.phone"
        />
        <p x-show="modalErrors.phone" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.phone"></p>

        <x-ui.input
            name="email"
            type="email"
            label="E-Posta"
            x-model="modal.email"
        />

        <x-ui.toggle
            name="is_default"
            label="Varsayılan Yetkili"
            x-model="modal.is_default"
        />

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
