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

        <form method="POST" action="{{ route('finance.current-accounts.movements.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="space-y-1.5">
                <label for="movement_current_account_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Cari *</label>
                <select
                    id="movement_current_account_id"
                    name="current_account_id"
                    x-model="movement.account_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Cari seçin</option>
                    @foreach ($accountOptions as $option)
                        <option value="{{ $option['id'] }}" @selected(old('current_account_id') == $option['id'])>{{ $option['code'] }} — {{ $option['title'] }}</option>
                    @endforeach
                </select>
                @error('current_account_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input type="date" name="transaction_date" label="İşlem Tarihi *" :value="old('transaction_date', now()->toDateString())" />

            <div class="space-y-1.5">
                <label for="movement_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşlem Türü *</label>
                <select
                    id="movement_type"
                    name="type"
                    x-model="movement.type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">İşlem türü seçin</option>
                    @foreach ($transactionTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input type="text" name="document_no" label="Belge No" :value="old('document_no')" placeholder="BEL-2026-0001" />
            <x-ui.input type="number" step="0.01" min="0" name="amount" label="Tutar (₺, KDV Hariç) *" :value="old('amount')" />
            <div class="space-y-1.5">
                <label for="movement_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea
                    id="movement_description"
                    name="description"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    placeholder="Hareket açıklaması"
                >{{ old('description') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
