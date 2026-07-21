<div
    x-show="activeModal === 'single'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'single'" x-transition.opacity @click="closeModals" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'single'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Hakediş</h3>
            <button type="button" @click="closeModals" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveSingle" class="space-y-4 px-6 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye *</label>
                    <select x-model="single.courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="singleErrors.courier_id ? 'border-red-300' : ''">
                        <option value="">Kurye seçin</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier['id'] }}">{{ $courier['name'] }}</option>
                        @endforeach
                    </select>
                    <p x-show="singleErrors.courier_id" x-cloak class="text-sm text-red-600" x-text="singleErrors.courier_id"></p>
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                    <select x-model="single.business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="singleErrors.business_id ? 'border-red-300' : ''">
                        <option value="">İşletme seçin</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                    <p x-show="singleErrors.business_id" x-cloak class="text-sm text-red-600" x-text="singleErrors.business_id"></p>
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Hakediş Tarihi *</label>
                    <input
                        type="date"
                        x-model="single.work_date"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        :class="singleErrors.work_date ? 'border-red-300' : ''"
                    />
                    <p x-show="singleErrors.work_date" x-cloak class="text-sm text-red-600" x-text="singleErrors.work_date"></p>
                </div>

                <x-ui.input name="package_count" type="number" min="0" label="Paket Sayısı" x-model="single.package_count" @input="updateEarningAmount" />
                <x-ui.input name="unit_price" type="number" step="0.01" min="0" label="Birim Ücret (₺, KDV Hariç)" x-model="single.unit_price" @input="updateEarningAmount" />
                <x-ui.input name="earning_amount" type="number" step="0.01" min="0" label="Hakediş Tutarı (₺, KDV Hariç)" x-model="single.earning_amount" />
                <x-ui.input name="extra_payment" type="number" step="0.01" min="0" label="Ek Ödeme (₺, KDV Hariç)" x-model="single.extra_payment" />
                <x-ui.input name="deduction" type="number" step="0.01" min="0" label="Kesinti (₺, KDV Hariç)" x-model="single.deduction" />

                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Net Ödeme</label>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <div class="text-lg font-bold text-emerald-700 dark:text-emerald-400" x-text="formatMoney(calcNet().net)"></div>
                        <p class="mt-0.5 text-[10px] font-normal text-emerald-700/70 dark:text-emerald-400/70">KDV hariç</p>
                    </div>
                </div>

                <div class="space-y-1.5 sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                    <select x-model="single.payment_status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-ui.textarea name="description" label="Açıklama" rows="3" x-model="single.description" />

            <div x-show="singleSaved" x-cloak>
                <x-ui.alert type="success">Hakediş bilgileri doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.</x-ui.alert>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <x-ui.button type="button" variant="secondary" @click="closeModals">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
