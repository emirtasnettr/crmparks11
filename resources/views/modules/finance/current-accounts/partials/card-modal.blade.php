<div
    x-show="activeModal === 'card'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'card'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'card'" x-transition class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cari Kartı</h3>
                <p class="mt-0.5 font-mono text-xs text-gray-400" x-text="selected?.code"></p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <template x-if="selected">
            <div class="space-y-6 px-6 py-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Cari Bilgileri</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white" x-text="selected.title"></p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                            <span x-text="selected.type_label"></span>
                            <span class="mx-1">·</span>
                            <span x-text="selected.status_label"></span>
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">İletişim Bilgileri</p>
                        <dl class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                                <dd class="text-gray-900 dark:text-white" x-text="selected.phone"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 dark:text-slate-400">E-posta</dt>
                                <dd class="text-gray-900 dark:text-white" x-text="selected.email || '—'"></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 dark:text-slate-400">Şehir</dt>
                                <dd class="text-gray-900 dark:text-white" x-text="selected.city"></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/50 dark:bg-red-900/20">
                        <p class="text-xs font-medium text-red-700 dark:text-red-400">Toplam Borç</p>
                        <p class="mt-1 text-xl font-bold text-red-700 dark:text-red-300" x-text="selected.total_debit_formatted"></p>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800/50 dark:bg-emerald-900/20">
                        <p class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Toplam Alacak</p>
                        <p class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-300" x-text="selected.total_credit_formatted"></p>
                    </div>
                    <div class="rounded-lg border border-primary-200 bg-primary-50 p-4 dark:border-primary-800/50 dark:bg-primary-900/20">
                        <p class="text-xs font-medium text-primary-700 dark:text-primary-400">Net Bakiye</p>
                        <p class="mt-1 text-xl font-bold text-primary-700 dark:text-primary-300" x-text="selected.balance_formatted"></p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Son Fatura</p>
                        <template x-if="selected.last_invoice">
                            <div class="mt-2 text-sm text-gray-600 dark:text-slate-300">
                                <p class="font-mono" x-text="selected.last_invoice.document_no"></p>
                                <p x-text="selected.last_invoice.date + ' · ' + selected.last_invoice.amount_formatted"></p>
                            </div>
                        </template>
                        <template x-if="!selected.last_invoice">
                            <p class="mt-2 text-sm text-gray-400">Kayıt bulunamadı</p>
                        </template>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4 dark:border-slate-700">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Son Hakediş</p>
                        <template x-if="selected.last_earning">
                            <div class="mt-2 text-sm text-gray-600 dark:text-slate-300">
                                <p class="font-mono" x-text="selected.last_earning.document_no"></p>
                                <p x-text="selected.last_earning.date + ' · ' + selected.last_earning.amount_formatted"></p>
                            </div>
                        </template>
                        <template x-if="!selected.last_earning">
                            <p class="mt-2 text-sm text-gray-400">Kayıt bulunamadı</p>
                        </template>
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Son Hareketler</p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-slate-400">Belge No</th>
                                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                                    <th class="px-3 py-2 font-medium text-gray-500 dark:text-slate-400 text-right">Bakiye</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                                <template x-for="movement in selected.recent_movements" :key="movement.id">
                                    <tr>
                                        <td class="px-3 py-2 text-gray-600 dark:text-slate-300" x-text="movement.date_formatted"></td>
                                        <td class="px-3 py-2 font-mono text-xs text-gray-600 dark:text-slate-300" x-text="movement.document_no"></td>
                                        <td class="px-3 py-2 text-gray-900 dark:text-white" x-text="movement.type_label"></td>
                                        <td class="px-3 py-2 text-right font-medium tabular-nums text-gray-900 dark:text-white" x-text="movement.balance_formatted"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
