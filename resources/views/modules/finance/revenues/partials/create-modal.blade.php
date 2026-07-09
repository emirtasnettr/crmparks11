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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Gelir</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Gelir no otomatik oluşturulacaktır (GLR-2026-XXXXXX)</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.revenues.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="space-y-1.5">
                <label for="revenue_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select id="revenue_business_id" name="business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">İşletme seçin</option>
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}" @selected(old('business_id') == $business['id'])>{{ $business['name'] }}</option>
                    @endforeach
                </select>
                @error('business_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="revenue_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gelir Türü *</label>
                <select id="revenue_type" name="revenue_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Gelir türü seçin</option>
                    @foreach ($revenueTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('revenue_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('revenue_type')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input type="text" name="period_label" label="Hakediş Dönemi" :value="old('period_label')" placeholder="Örn. Haziran 2026" />
            <x-ui.input type="text" name="invoice_no" label="Fatura No" :value="old('invoice_no')" placeholder="FTR-2026-0001" />
            <x-ui.input type="date" name="revenue_date" label="Gelir Tarihi" :value="old('revenue_date', now()->toDateString())" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="amount" label="Tutar (₺, KDV Hariç) *" :value="old('amount')" />
                <x-ui.input type="number" step="1" min="0" max="100" name="vat_rate" label="KDV Oranı (%)" :value="old('vat_rate', 20)" />
            </div>

            <div class="space-y-1.5">
                <label for="revenue_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="revenue_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Gelir açıklaması">{{ old('description') }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="collection_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Tahsil Durumu</label>
                <select id="collection_status" name="collection_status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($collectionStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('collection_status', 'pending') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
