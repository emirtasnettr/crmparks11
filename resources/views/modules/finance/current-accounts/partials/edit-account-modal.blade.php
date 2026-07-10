<div
    x-show="activeModal === 'edit-account'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'edit-account'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'edit-account'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cari Hesabı Düzenle</h3>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" :action="editAccountId ? `${routes.update}/${editAccountId}` : '#'" class="space-y-4 px-6 py-4">
            @csrf
            <input type="hidden" name="_method" value="PUT">

            <template x-if="editAccount && !editAccount.entity_id">
                <div class="space-y-1.5">
                    <label for="edit_account_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Cari Tipi *</label>
                    <select id="edit_account_type" name="type" x-model="editAccount.type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($accountTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </template>
            <template x-if="editAccount && editAccount.entity_id">
                <input type="hidden" name="type" :value="editAccount.type">
            </template>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Cari Ünvanı *</label>
                <input type="text" name="title" x-model="editAccount.title" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Telefon *</label>
                <input type="text" name="phone" x-model="editAccount.phone" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">E-posta</label>
                <input type="email" name="email" x-model="editAccount.email" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Vergi No / TCKN</label>
                <input type="text" name="tax_number" x-model="editAccount.tax_number" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Şehir</label>
                <input type="text" name="city" x-model="editAccount.city" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Adres</label>
                <input type="text" name="address" x-model="editAccount.address" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
            </div>

            <div class="space-y-1.5">
                <label for="edit_account_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select id="edit_account_status" name="status" x-model="editAccount.status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </div>
</div>
