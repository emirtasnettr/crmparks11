<div
    x-show="openDetailModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openDetailModal" x-transition.opacity @click="closeDetailModal" class="fixed inset-0 bg-gray-900/50"></div>

    <div
        x-show="openDetailModal"
        x-transition
        class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kurye Detayı</h3>
            <button type="button" @click="closeDetailModal" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-4 px-6 py-4" x-show="selected">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-bold text-white" :class="selected?.avatar_color">
                    <span x-text="selected?.avatar_initials"></span>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white" x-text="selected?.courier_name"></p>
                    <p class="text-sm text-gray-500 dark:text-slate-400" x-text="selected?.agency_name"></p>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.phone"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.courier_type_label"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Aktif İşletme</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white" x-text="selected?.active_business_name || '—'"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Acenteye Katılış</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.join_date_formatted"></dd>
                </div>
            </dl>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800/50">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Araç Bilgileri</p>
                <p class="text-sm text-gray-700 dark:text-slate-300" x-text="selected?.vehicle_info"></p>
            </div>

            <div class="rounded-lg border border-gray-200 p-3 dark:border-slate-700">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Çalışma Süresi</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="selected?.work_duration"></p>
            </div>

            <template x-if="selected?.notes">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-slate-700">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Notlar</p>
                    <p class="text-sm text-gray-700 dark:text-slate-300" x-text="selected?.notes"></p>
                </div>
            </template>

            <div class="flex justify-end pt-2">
                <x-ui.button type="button" variant="secondary" @click="closeDetailModal">Kapat</x-ui.button>
            </div>
        </div>
    </div>
</div>
