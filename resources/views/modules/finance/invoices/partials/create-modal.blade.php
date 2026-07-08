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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Fatura</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Fatura no otomatik oluşturulacaktır (FTR-2026-XXXXXX)</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveInvoice()" class="space-y-4 px-6 py-4">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select x-model="form.business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.business_id ? 'border-red-300' : ''">
                    <option value="">İşletme seçin</option>
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                    @endforeach
                </select>
                <p x-show="errors.business_id" x-cloak class="text-sm text-red-600" x-text="errors.business_id"></p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Hakediş *</label>
                <select x-model="form.earning_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.earning_id ? 'border-red-300' : ''">
                    <option value="">Hakediş seçin</option>
                    @foreach ($earningOptions as $earning)
                        <option value="{{ $earning['id'] }}" data-business="{{ $earning['business_id'] }}">
                            {{ $earning['reference'] }} — {{ $earning['period_label'] }}
                        </option>
                    @endforeach
                </select>
                <p x-show="errors.earning_id" x-cloak class="text-sm text-red-600" x-text="errors.earning_id"></p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Fatura Türü</label>
                <select x-model="form.invoice_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($invoiceTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="date" label="Fatura Tarihi" x-model="form.invoice_date" />
                <x-ui.input type="date" label="Vade Tarihi" x-model="form.due_date" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" label="Ara Toplam (₺, KDV Hariç)" x-model="form.subtotal" @input="calcTotals()" />
                <x-ui.input type="number" step="1" min="0" max="100" label="KDV %" x-model="form.vat_rate" @input="calcTotals()" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">KDV Tutarı</p>
                    <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white" x-text="formatMoney(vatAmount)"></p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Genel Toplam</p>
                    <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white" x-text="formatMoney(grandTotal)"></p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea x-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
            </div>

            <div x-show="saved" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                Fatura kaydı oluşturuldu. Gerçek entegrasyonda cari hesaba gelir hareketi ve tahsilat kaydı otomatik oluşturulacaktır.
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
