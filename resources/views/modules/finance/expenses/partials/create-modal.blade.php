<div
    x-show="activeModal === 'create'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'create'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'create'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Gider</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Gider no otomatik oluşturulacaktır (GDR-2026-XXXXXX)</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.expenses.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="space-y-1.5">
                <label for="expense_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gider Türü *</label>
                <select id="expense_type" name="expense_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Gider türü seçin</option>
                    @foreach ($expenseTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('expense_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('expense_type')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="expense_courier_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye (Opsiyonel)</label>
                <select id="expense_courier_id" name="courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Kurye seçin</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier['id'] }}" @selected(old('courier_id') == $courier['id'])>{{ $courier['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="expense_agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Acente (Opsiyonel)</label>
                <select id="expense_agency_id" name="agency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Acente seçin</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency['id'] }}" @selected(old('agency_id') == $agency['id'])>{{ $agency['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="date" name="expense_date" label="Gider Tarihi *" :value="old('expense_date', now()->toDateString())" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="amount" label="Tutar (₺, KDV Hariç) *" :value="old('amount')" />
                <x-ui.input type="number" step="1" min="0" max="100" name="vat_rate" label="KDV (%)" :value="old('vat_rate', 20)" />
            </div>

            <div class="space-y-1.5">
                <label for="expense_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="expense_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Gider açıklaması">{{ old('description') }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Durumu</label>
                <select id="payment_status" name="payment_status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_status', 'pending') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="text" name="document_no" label="Belge No" :value="old('document_no')" placeholder="BLG-2026-0001" />

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
