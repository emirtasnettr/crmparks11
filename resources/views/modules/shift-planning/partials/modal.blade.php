{{-- Vardiya oluştur / düzenle --}}
<div
    x-show="openShiftModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openShiftModal" x-transition.opacity @click="closeShiftModal()" class="fixed inset-0 bg-gray-900/50"></div>
    <div
        x-show="openShiftModal"
        x-transition
        class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="shiftMode === 'edit' ? 'Vardiya Düzenle' : 'Yeni Vardiya'"></h3>
            <button type="button" @click="closeShiftModal()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <form method="POST" :action="shiftFormAction()" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT" x-bind:disabled="shiftMode !== 'edit'">
                <input type="hidden" name="business_id" :value="selectedBusinessId">
                <input type="hidden" name="week" :value="week.week_start">

                <div class="space-y-1.5">
                    <label for="shift_name" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Vardiya Adı *</label>
                    <input
                        id="shift_name"
                        name="name"
                        type="text"
                        x-model="shiftForm.name"
                        placeholder="Örn. Sabah, Öğle, Gece"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="shift_start_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                        <input
                            id="shift_start_date"
                            name="start_date"
                            type="date"
                            x-model="shiftForm.start_date"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            required
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label for="shift_end_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Tarihi *</label>
                        <input
                            id="shift_end_date"
                            name="end_date"
                            type="date"
                            x-model="shiftForm.end_date"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="shift_start_time" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Saati *</label>
                        <input
                            id="shift_start_time"
                            name="start_time"
                            type="time"
                            x-model="shiftForm.start_time"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            required
                        />
                    </div>
                    <div class="space-y-1.5">
                        <label for="shift_end_time" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Saati *</label>
                        <input
                            id="shift_end_time"
                            name="end_time"
                            type="time"
                            x-model="shiftForm.end_time"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            required
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-gray-700 dark:text-slate-300">Aralıktaki günler *</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Seçilen tarih aralığında hangi hafta günlerinde geçerli olsun?</p>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <template x-for="day in weekDayOptions" :key="day.iso">
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-2.5 py-2 text-sm dark:border-slate-600">
                                <input
                                    type="checkbox"
                                    name="days_of_week[]"
                                    :value="String(day.iso)"
                                    x-model="shiftForm.days_of_week"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span class="text-gray-700 dark:text-slate-300" x-text="day.label"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <x-ui.textarea name="notes" label="Not" rows="2" x-model="shiftForm.notes" />

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        x-model="shiftForm.is_active"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    >
                    Aktif vardiya
                </label>

                <div class="space-y-2" x-show="shiftMode === 'create'">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-slate-300">Kuryeler (opsiyonel)</p>
                        <p class="text-xs text-gray-500 dark:text-slate-400">
                            Seçerseniz, tarih aralığındaki tüm uygun günlere bu kuryeler atanır. Sonra her günü ayrı da düzenleyebilirsiniz.
                        </p>
                    </div>
                    <template x-if="availableCouriers.length === 0">
                        <p class="text-sm text-gray-500 dark:text-slate-400">
                            Bu işletmeye atanmış aktif kurye yok.
                        </p>
                    </template>
                    <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-slate-600" x-show="availableCouriers.length > 0">
                        <template x-for="courier in availableCouriers" :key="'create-' + courier.id">
                            <label class="flex cursor-pointer items-start gap-3 rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <input
                                    type="checkbox"
                                    name="courier_ids[]"
                                    :value="String(courier.id)"
                                    x-model="shiftForm.courier_ids"
                                    class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                >
                                <span>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white" x-text="courier.name"></span>
                                    <span class="block text-xs text-gray-500 dark:text-slate-400" x-text="courier.phone"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                    <x-ui.button type="button" variant="secondary" @click="closeShiftModal()">İptal</x-ui.button>
                    <x-ui.button type="submit" x-text="shiftMode === 'edit' ? 'Kaydet' : 'Oluştur'"></x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Belirli güne kurye ata --}}
<div
    x-show="openCourierModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openCourierModal" x-transition.opacity @click="closeCourierModal()" class="fixed inset-0 bg-gray-900/50"></div>
    <div
        x-show="openCourierModal"
        x-transition
        class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Güne Kurye Ata</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                    <span x-text="courierForm.shift_name"></span>
                    ·
                    <span x-text="formatWorkDate(courierForm.work_date)"></span>
                </p>
            </div>
            <button type="button" @click="closeCourierModal()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-4">
            <form method="POST" :action="courierFormAction()" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="week" :value="week.week_start">
                <input type="hidden" name="work_date" :value="courierForm.work_date">

                <template x-if="availableCouriers.length === 0">
                    <p class="text-sm text-gray-500 dark:text-slate-400">
                        Bu işletmeye atanmış aktif kurye yok. Önce Atanan Kuryeler sayfasından kurye atayın.
                    </p>
                </template>

                <div class="max-h-64 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-slate-600" x-show="availableCouriers.length > 0">
                    <template x-for="courier in availableCouriers" :key="courier.id">
                        <label class="flex cursor-pointer items-start gap-3 rounded-md px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                            <input
                                type="checkbox"
                                name="courier_ids[]"
                                :value="String(courier.id)"
                                x-model="courierForm.courier_ids"
                                class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                            <span>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white" x-text="courier.name"></span>
                                <span class="block text-xs text-gray-500 dark:text-slate-400" x-text="courier.phone"></span>
                            </span>
                        </label>
                    </template>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                    <x-ui.button type="button" variant="secondary" @click="closeCourierModal()">İptal</x-ui.button>
                    <x-ui.button type="submit" x-bind:disabled="availableCouriers.length === 0">Kaydet</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
