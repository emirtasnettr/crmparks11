<div
    x-show="activeModal === 'new-account'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'new-account'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'new-account'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Cari Hesap</h3>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.current-accounts.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="rounded-lg border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-700 dark:border-primary-800/50 dark:bg-primary-900/20 dark:text-primary-300">
                Cari kodu otomatik oluşturulacaktır. Örnek: <span class="font-mono">CAR-000051</span>
            </div>

            <div class="space-y-1.5">
                <label for="new_account_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Cari Tipi *</label>
                <select id="new_account_type" name="type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($accountTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input type="text" name="title" label="Cari Ünvanı *" :value="old('title')" />
            <x-ui.input type="text" name="phone" label="Telefon *" :value="old('phone')" />
            <x-ui.input type="email" name="email" label="E-posta" :value="old('email')" />
            <x-ui.input type="text" name="tax_number" label="Vergi No / TCKN" :value="old('tax_number')" />

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
