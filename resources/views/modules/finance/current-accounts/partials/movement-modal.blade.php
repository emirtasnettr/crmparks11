<div
    x-show="activeModal === 'movement'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'movement'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'movement'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Cari Hareketi</h3>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveMovement()" class="space-y-4 px-6 py-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Cari *</label>
                <select
                    x-model="movement.account_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="movementErrors.account_id ? 'border-red-300' : ''"
                >
                    <option value="">Cari seçin</option>
                    @foreach ($accountOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['code'] }} — {{ $option['title'] }}</option>
                    @endforeach
                </select>
                <p x-show="movementErrors.account_id" x-cloak class="text-sm text-red-600" x-text="movementErrors.account_id"></p>
            </div>

            <x-ui.input type="date" label="İşlem Tarihi *" x-model="movement.transaction_date" />

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşlem Türü *</label>
                <select
                    x-model="movement.type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="movementErrors.type ? 'border-red-300' : ''"
                >
                    <option value="">İşlem türü seçin</option>
                    @foreach ($transactionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p x-show="movementErrors.type" x-cloak class="text-sm text-red-600" x-text="movementErrors.type"></p>
            </div>

            <x-ui.input type="text" label="Belge No" x-model="movement.document_no" placeholder="BEL-2026-0001" />
            <x-ui.input type="number" step="0.01" min="0" label="Tutar (₺, KDV Hariç) *" x-model="movement.amount" />
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea
                    x-model="movement.description"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    placeholder="Hareket açıklaması"
                ></textarea>
            </div>

            <div x-show="movementSaved" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                Hareket kaydı oluşturuldu. Gerçek entegrasyonda ekstre otomatik güncellenecektir.
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
