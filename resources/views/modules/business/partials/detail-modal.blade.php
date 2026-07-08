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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">İşletme Detayı</h3>
            <button type="button" @click="closeDetailModal" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-4 px-6 py-4" x-show="selected">
            <div class="flex items-center gap-3">
                <template x-if="selected?.logo_url">
                    <img :src="selected.logo_url" alt="" class="h-12 w-12 shrink-0 rounded-lg border border-gray-200 object-cover dark:border-slate-700" />
                </template>
                <template x-if="!selected?.logo_url">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg text-sm font-bold text-white" :class="selected?.logo_color">
                        <span x-text="selected?.logo"></span>
                    </div>
                </template>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white" x-text="selected?.company_name"></p>
                    <p class="text-sm text-gray-500 dark:text-slate-400" x-text="selected?.brand_name"></p>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.phone"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Konum</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.location"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Çalışma Modeli</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.pricing_model_label"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Aktif Kurye</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.active_couriers"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.status_label"></dd>
                </div>
            </dl>

            <div class="grid grid-cols-2 gap-2 pt-2">
                <a :href="selected?.contacts_url" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Yetkililer</a>
                <a :href="selected?.contracts_url" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Sözleşmeler</a>
                <a :href="selected?.assignments_url" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Atanan Kuryeler</a>
                <a :href="selected?.documents_url" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Evraklar</a>
                <a :href="selected?.activities_url" class="rounded-lg border border-gray-200 px-3 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700">Hareket Geçmişi</a>
            </div>
        </div>
    </div>
</div>
