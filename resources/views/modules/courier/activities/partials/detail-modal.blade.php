<div
    x-show="openModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openModal" x-transition.opacity @click="closeModal" class="fixed inset-0 bg-gray-900/50"></div>

    <div
        x-show="openModal"
        x-transition
        class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Hareket Detayı</h3>
            <button type="button" @click="closeModal" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-4 px-6 py-4" x-show="selected">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">İşlem Türü</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white" x-text="selected?.action_label"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white" x-text="selected?.courier_name"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">İşlem Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.occurred_at_formatted"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">İşlemi Yapan Kullanıcı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white" x-text="selected?.user_name"></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">IP Adresi</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white" x-text="selected?.ip_address"></dd>
                </div>
                <div class="flex flex-col gap-1">
                    <dt class="text-gray-500 dark:text-slate-400">Tarayıcı Bilgisi</dt>
                    <dd class="text-sm text-gray-900 dark:text-white" x-text="selected?.user_agent"></dd>
                </div>
            </dl>

            <template x-if="selected?.old_value">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Eski Değer</p>
                    <p class="text-sm text-gray-700 dark:text-slate-300" x-text="selected?.old_value"></p>
                </div>
            </template>

            <template x-if="selected?.new_value">
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Yeni Değer</p>
                    <p class="text-sm text-emerald-800 dark:text-emerald-300" x-text="selected?.new_value"></p>
                </div>
            </template>

            <div class="rounded-lg border border-gray-200 p-3 dark:border-slate-700">
                <p class="mb-1 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Açıklama</p>
                <p class="text-sm text-gray-700 dark:text-slate-300" x-text="selected?.description"></p>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="button" variant="secondary" @click="closeModal">Kapat</x-ui.button>
            </div>
        </div>
    </div>
</div>
