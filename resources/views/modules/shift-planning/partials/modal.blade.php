{{-- Vardiya oluştur / düzenle --}}
<div
    x-show="openShiftModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="closeShiftModal()"></div>
    <div class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="shiftMode === 'create' ? 'Yeni Vardiya' : 'Vardiya Düzenle'"></h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Saatler her gün için sabittir.</p>

        <form method="POST" :action="shiftFormAction()" class="mt-4 space-y-4">
            @csrf
            <template x-if="shiftMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>
            <input type="hidden" name="business_id" :value="selectedBusinessId">
            <input type="hidden" name="week" value="{{ $week['week_start'] }}">

            <x-ui.input name="name" label="Vardiya Adı *" x-model="shiftForm.name" required />
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç *</label>
                    <input type="time" name="start_time" x-model="shiftForm.start_time" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş *</label>
                    <input type="time" name="end_time" x-model="shiftForm.end_time" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Tarih başlangıç *</label>
                    <input type="date" name="start_date" x-model="shiftForm.start_date" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Tarih bitiş *</label>
                    <input type="date" name="end_date" x-model="shiftForm.end_date" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400">Varsayılan aralık 1 aydır; istediğiniz tarih aralığını seçebilirsiniz.</p>
            <x-ui.input name="required_headcount" type="number" label="Kişi Sayısı *" x-model="shiftForm.required_headcount" min="1" max="100" required />
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Not</label>
                <textarea name="notes" rows="2" x-model="shiftForm.notes" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" x-model="shiftForm.is_active" class="rounded border-gray-300 text-primary-600">
                Aktif
            </label>

            <template x-if="shiftMode === 'create'">
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-slate-300">Kadro (opsiyonel)</p>
                    <input
                        type="search"
                        x-model="courierSearch"
                        placeholder="Kurye ara..."
                        class="mb-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                    <div class="max-h-40 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-slate-700">
                        <template x-if="eligibleCouriersLoading">
                            <p class="p-3 text-sm text-gray-500">Uygun kuryeler yükleniyor...</p>
                        </template>
                        <template x-if="!eligibleCouriersLoading && filteredCreateCouriers().length === 0">
                            <p class="p-3 text-sm text-gray-500">Bu tarih/saatte uygun kurye yok.</p>
                        </template>
                        <template x-for="courier in filteredCreateCouriers()" :key="courier.id">
                            <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <input type="checkbox" name="courier_ids[]" :value="courier.id" x-model="shiftForm.courier_ids" class="rounded border-gray-300 text-primary-600">
                                <span class="min-w-0 flex-1 truncate" x-text="courier.name"></span>
                                <span class="shrink-0 text-xs text-gray-400" x-text="courier.phone"></span>
                            </label>
                        </template>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">Çakışan vardiyası olan kuryeler listede gösterilmez.</p>
                </div>
            </template>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="secondary" x-on:click="closeShiftModal()">Vazgeç</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>

{{-- Kadro ata --}}
<div
    x-show="openCourierModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="closeCourierModal()"></div>
    <div class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vardiya Kadrosu</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            <span x-text="courierForm.shift_name"></span> · en fazla <span x-text="courierForm.required_headcount"></span> kişi
        </p>

        <form method="POST" :action="courierFormAction()" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="week" value="{{ $week['week_start'] }}">

            <input
                type="search"
                x-model="assignCourierSearch"
                placeholder="Kurye ara..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
            >

            <div class="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-gray-200 p-2 dark:border-slate-700">
                <template x-if="eligibleCouriersLoading">
                    <p class="p-3 text-sm text-gray-500">Uygun kuryeler yükleniyor...</p>
                </template>
                <template x-if="!eligibleCouriersLoading && filteredAssignCouriers().length === 0">
                    <p class="p-3 text-sm text-gray-500">Bu tarih/saatte uygun kurye yok.</p>
                </template>
                <template x-for="courier in filteredAssignCouriers()" :key="'roster-'+courier.id">
                    <label class="flex items-center gap-2 rounded px-2 py-1.5 text-sm hover:bg-gray-50 dark:hover:bg-slate-700/50">
                        <input type="checkbox" name="courier_ids[]" :value="courier.id" x-model="courierForm.courier_ids" class="rounded border-gray-300 text-primary-600">
                        <span class="min-w-0 flex-1 truncate" x-text="courier.name"></span>
                        <span class="shrink-0 text-xs text-gray-400" x-text="courier.phone"></span>
                    </label>
                </template>
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400">Çakışan vardiyası olan kuryeler listede gösterilmez.</p>

            <div class="flex justify-end gap-2">
                <x-ui.button type="button" variant="secondary" x-on:click="closeCourierModal()">Vazgeç</x-ui.button>
                <x-ui.button type="submit">Kadroyu Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>

{{-- Sil --}}
<div
    x-show="openDeleteModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="closeDeleteModal()"></div>
    <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vardiyayı Sil</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">Bu vardiya şablonu ve kadrosu silinecek.</p>
        <form method="POST" :action="destroyFormAction()" class="mt-4 flex justify-end gap-2">
            @csrf
            @method('DELETE')
            <input type="hidden" name="week" value="{{ $week['week_start'] }}">
            <x-ui.button type="button" variant="secondary" x-on:click="closeDeleteModal()">Vazgeç</x-ui.button>
            <x-ui.button type="submit" variant="danger">Sil</x-ui.button>
        </form>
    </div>
</div>
