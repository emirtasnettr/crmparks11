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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Tahsilat</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Tahsilat no otomatik oluşturulacaktır (TAH-2026-XXXXXX)</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveCollection()" class="space-y-4 px-6 py-4">
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
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gelir Kaydı</label>
                <select x-model="form.revenue_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Gelir kaydı seçin</option>
                    @foreach ($revenueOptions as $revenue)
                        <option value="{{ $revenue['id'] }}">{{ $revenue['reference'] }} — {{ $revenue['invoice_no'] ?? 'Fatura yok' }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="text" label="Fatura" x-model="form.invoice_no" placeholder="FTR-2026-0001" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="date" label="Tahsilat Tarihi" x-model="form.collection_date" />
                <x-ui.input type="date" label="Vade Tarihi" x-model="form.due_date" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" label="Toplam Tutar (₺, KDV Hariç) *" x-model="form.total_amount" @input="calcRemaining()" />
                <x-ui.input type="number" step="0.01" min="0" label="Tahsil Edilen Tutar (₺, KDV Hariç) *" x-model="form.collected_amount" @input="calcRemaining()" />
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Kalan Tutar</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white" x-text="formatMoney(remainingAmount)"></p>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Yöntemi</label>
                <select x-model="form.payment_method" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Ödeme yöntemi seçin</option>
                    @foreach ($paymentMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="text" label="Referans No" x-model="form.payment_reference" />
            <x-ui.input type="text" label="Banka" x-model="form.bank" placeholder="Örn. Garanti BBVA" />

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea x-model="form.description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"></textarea>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Dekont Yükle</label>
                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-slate-300 dark:file:bg-primary-600/10 dark:file:text-primary-400" />
            </div>

            <div x-show="saved" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                Tahsilat kaydı oluşturuldu. Gerçek entegrasyonda cari hesaba otomatik hareket yansıyacaktır.
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
