<div
    x-show="activeModal === 'statement'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'statement'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'statement'" x-transition class="relative max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cari Ekstresi</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                    <span class="font-bold" x-text="selected?.brand_name ? selected.brand_name + ' — ' : ''"></span>
                    <span x-text="selected?.title"></span>
                    <span class="mx-1">·</span>
                    <span class="font-mono text-xs" x-text="selected?.code"></span>
                </p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <template x-if="selected">
            <div class="px-6 py-4">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <div class="text-sm text-gray-600 dark:text-slate-300">
                        Güncel Bakiye:
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="selected.balance_formatted"></span>
                    </div>
                    <a
                        :href="`{{ url('/finans/cari-hesaplar') }}/${selected.id}/pdf`"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        PDF İndir
                    </a>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Belge No</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Borç</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Alacak</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 text-right">Bakiye</th>
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açıklama</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            <template x-for="movement in selected.movements" :key="movement.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300" x-text="movement.date_formatted"></td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300" x-text="movement.document_no"></td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-white" x-text="movement.type_label"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white" x-text="movement.debit_formatted"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white" x-text="movement.credit_formatted"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums text-gray-900 dark:text-white" x-text="movement.balance_formatted"></td>
                                    <td class="max-w-[220px] truncate px-4 py-3 text-gray-600 dark:text-slate-300" x-text="movement.description"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</div>
