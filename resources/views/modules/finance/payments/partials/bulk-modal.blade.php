<div
    x-show="activeModal === 'bulk'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'bulk'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'bulk'" x-transition class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Toplu Ödeme</h3>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.payments.bulk') }}" class="space-y-4 px-6 py-4">
            @csrf

            <p class="text-sm text-gray-600 dark:text-slate-300">
                Listeden seçtiğiniz bekleyen/kısmi ödemelerin kalan tutarını tek seferde kapatır.
                <span class="font-medium" x-text="selectedIds.length + ' kayıt seçili'"></span>
            </p>

            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>

            <x-ui.input type="date" name="payment_date" label="Ödeme Tarihi" x-model="bulk.payment_date" :error="$errors->first('payment_date')" />

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Yöntemi</label>
                <select name="payment_method" x-model="bulk.payment_method" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($paymentMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit" ::disabled="selectedIds.length === 0">Uygula</x-ui.button>
            </div>
        </form>
    </div>
</div>
