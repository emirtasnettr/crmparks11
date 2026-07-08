<div
    x-show="activeModal === 'single'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'single'" x-transition.opacity @click="closeModals" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'single'" x-transition class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tekli Hakediş</h3>
            <button type="button" @click="closeModals" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="px-6 py-4">
            <form @submit.prevent="saveSingleEarning" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                        <select x-model="single.business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="singleErrors.business_id ? 'border-red-300' : ''">
                            <option value="">İşletme seçin</option>
                            @foreach ($businesses as $business)
                                <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                            @endforeach
                        </select>
                        <p x-show="singleErrors.business_id" x-cloak class="text-sm text-red-600" x-text="singleErrors.business_id"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye *</label>
                        <select x-model="single.courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="singleErrors.courier_id ? 'border-red-300' : ''">
                            <option value="">Kurye seçin</option>
                            @foreach ($couriers as $courier)
                                <option value="{{ $courier['id'] }}">{{ $courier['name'] }}</option>
                            @endforeach
                        </select>
                        <p x-show="singleErrors.courier_id" x-cloak class="text-sm text-red-600" x-text="singleErrors.courier_id"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ay *</label>
                        <select x-model="single.period_month" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Ay seçin</option>
                            @foreach ($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <p x-show="singleErrors.period_month" x-cloak class="text-sm text-red-600" x-text="singleErrors.period_month"></p>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Yıl *</label>
                        <select x-model="single.period_year" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            <option value="">Yıl seçin</option>
                            @foreach ([2025, 2026, 2027] as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <p x-show="singleErrors.period_year" x-cloak class="text-sm text-red-600" x-text="singleErrors.period_year"></p>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Çalışma Modeli</label>
                    <select x-model="single.pricing_model" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($pricingModels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="single.pricing_model === 'per_package'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="package_count" type="number" min="0" label="Paket Sayısı" x-model="single.package_count" />
                    <x-ui.input name="revenue_unit_price" type="number" step="0.01" min="0" label="İşletmeden Birim Ücret (₺, KDV Hariç)" x-model="single.revenue_unit_price" />
                    <x-ui.input name="courier_unit_price" type="number" step="0.01" min="0" label="Kurye Birim Ücreti (₺, KDV Hariç)" x-model="single.courier_unit_price" />
                </div>

                <div x-show="single.pricing_model !== 'per_package'" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="revenue_total" type="number" step="0.01" min="0" label="İşletmeden Alınacak (₺, KDV Hariç)" x-model="single.revenue_total" />
                    <x-ui.input name="courier_payment" type="number" step="0.01" min="0" label="Kurye Ödemesi (₺, KDV Hariç)" x-model="single.courier_payment" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.input name="extra_income" type="number" step="0.01" min="0" label="Ek Gelir (₺, KDV Hariç)" x-model="single.extra_income" />
                    <x-ui.input name="extra_expense" type="number" step="0.01" min="0" label="Ek Gider (₺, KDV Hariç)" x-model="single.extra_expense" />
                    <x-ui.input name="deduction" type="number" step="0.01" min="0" label="Kesinti (₺, KDV Hariç)" x-model="single.deduction" />
                </div>

                <x-ui.textarea name="description" label="Açıklama" rows="2" x-model="single.description" />

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-600 dark:bg-slate-800/50">
                    <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Hesaplama Özeti</p>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Toplam Gelir</p>
                            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400" x-text="formatMoney(calcSingle().revenue)"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Toplam Gider</p>
                            <p class="text-lg font-bold text-red-600 dark:text-red-400" x-text="formatMoney(calcSingle().expense)"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-slate-400">Toplam Kâr</p>
                            <p class="text-lg font-bold" :class="calcSingle().profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" x-text="formatMoney(calcSingle().profit)"></p>
                        </div>
                    </div>
                </div>

                <div x-show="singleSaved" x-cloak>
                    <x-ui.alert type="success">Hakediş doğrulandı. Kayıt backend bağlantısı sonrası aktif olacaktır.</x-ui.alert>
                </div>

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <x-ui.button type="button" variant="secondary" @click="closeModals">İptal</x-ui.button>
                    <x-ui.button type="submit">Kaydet</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
