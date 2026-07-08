<x-ui.modal title="Yeni Banka Hesabı">
    <form @submit.prevent="saveAccount" class="space-y-4">
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
            <label for="modal_bank_key" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Banka Adı *</label>
            <select
                id="modal_bank_key"
                x-model="modal.bank_key"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.bank_key ? 'border-red-300 dark:border-red-500' : ''"
            >
                <option value="">Banka seçin</option>
                @foreach ($banks as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <p x-show="modalErrors.bank_key" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.bank_key"></p>
        </div>

        <x-ui.input name="account_holder" label="Hesap Sahibi *" x-model="modal.account_holder" />

        <div class="space-y-1.5">
            <label for="modal_iban" class="block text-sm font-medium text-gray-700 dark:text-slate-300">IBAN *</label>
            <input
                id="modal_iban"
                type="text"
                x-model="modal.iban"
                @input="formatIbanInput"
                placeholder="TR00 0000 0000 0000 0000 0000 00"
                maxlength="32"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-sm uppercase dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                :class="modalErrors.iban ? 'border-red-300 dark:border-red-500' : ''"
            />
            <p x-show="modalErrors.iban" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="modalErrors.iban"></p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.input name="branch_code" label="Şube Kodu" x-model="modal.branch_code" />
            <x-ui.input name="account_number" label="Hesap No" x-model="modal.account_number" />
        </div>

        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-slate-700">
            <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Varsayılan Hesap</p>
                <p class="text-xs text-gray-500 dark:text-slate-400">Ödemeler bu hesaba yapılır</p>
            </div>
            <button
                type="button"
                @click="modal.is_default = !modal.is_default"
                :class="modal.is_default ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-600'"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors"
                role="switch"
                :aria-checked="modal.is_default"
            >
                <span
                    :class="modal.is_default ? 'translate-x-5' : 'translate-x-0.5'"
                    class="pointer-events-none inline-block h-5 w-5 translate-y-0.5 transform rounded-full bg-white shadow transition"
                ></span>
            </button>
        </div>

        <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
            <select x-model="modal.status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-ui.textarea name="notes" label="Notlar" rows="3" x-model="modal.notes" />

        <div x-show="modalSaved" x-cloak>
            <x-ui.alert type="success">
                Banka hesabı bilgileri doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.
            </x-ui.alert>
        </div>

        <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
            <x-ui.button type="button" variant="secondary" @click="closeModal">İptal</x-ui.button>
            <x-ui.button type="submit">Kaydet</x-ui.button>
        </div>
    </form>
</x-ui.modal>
