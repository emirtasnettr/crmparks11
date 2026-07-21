{{-- Personel: vardiya bitir / yerine kurye --}}
<div
    x-show="openEndModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="closeEndModal()"></div>
    <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vardiyayı Bitir</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            <span x-text="endForm.courier_name"></span>
            <span x-show="endForm.shift_name"> · <span x-text="endForm.shift_name"></span></span>
        </p>

        <form method="POST" action="{{ route('shift-planning.attendance.end') }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="business_id" :value="endForm.business_id">
            <input type="hidden" name="attendance_id" :value="endForm.attendance_id">
            <input type="hidden" name="work_date" :value="endForm.work_date">
            <template x-if="endForm.return_to">
                <input type="hidden" name="return_to" :value="endForm.return_to">
            </template>
            <template x-if="endForm.week">
                <input type="hidden" name="week" :value="endForm.week">
            </template>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş saati *</label>
                <input
                    type="datetime-local"
                    name="ended_at"
                    x-model="endForm.ended_at"
                    required
                    :min="endForm.min_ended_at"
                    :max="endForm.shift_end_at"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                >
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Hakediş bu saate göre hesaplanır.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                    Sebep
                    <span x-show="needsEndReason()" class="text-rose-600">*</span>
                </label>
                <select
                    name="end_reason"
                    x-model="endForm.end_reason"
                    :required="needsEndReason()"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                >
                    <option value="">Seçin</option>
                    <template x-for="(label, code) in endReasons" :key="code">
                        <option :value="code" x-text="label"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Erken bitiş veya yerine kurye eklerken zorunlu.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Yerine kurye</label>
                <input
                    type="search"
                    x-model="replacementSearch"
                    placeholder="Kurye ara..."
                    class="mb-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                >
                <select
                    name="replacement_courier_id"
                    x-model="endForm.replacement_courier_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                >
                    <option value="">Yerine ekleme</option>
                    <template x-for="courier in filteredReplacementCouriers()" :key="'rep-'+courier.id">
                        <option :value="courier.id" x-text="courier.name + (courier.phone ? ' · ' + courier.phone : '')"></option>
                    </template>
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Seçilirse bitiş saatinden vardiya sonuna kadar yeni kurye başlatılır.</p>
            </div>

            <template x-if="endForm.pricing_model === 'per_package'">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Paket sayısı *</label>
                    <input
                        type="number"
                        name="package_count"
                        x-model="endForm.package_count"
                        min="1"
                        max="100000"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                </div>
            </template>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Not</label>
                <textarea
                    name="notes"
                    rows="2"
                    x-model="endForm.notes"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="secondary" x-on:click="closeEndModal()">Vazgeç</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
