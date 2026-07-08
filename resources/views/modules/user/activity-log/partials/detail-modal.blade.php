<div
    x-show="activeModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal" x-transition.opacity @click="closeModal()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal" x-transition class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivite Detayı</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Salt okunur denetim kaydı — değiştirilemez</p>
            </div>
            <button type="button" @click="closeModal()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <template x-if="selected">
            <div class="space-y-4 px-6 py-4">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">Aktivite Türü</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.activity_type_label"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">İşlem Tarihi</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.occurred_at"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">Kullanıcı</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.user_name"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">Rol</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.role_label"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">Modül</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.module_label"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">IP Adresi</dt>
                        <dd class="mt-0.5 font-mono text-xs font-medium text-gray-900 dark:text-white" x-text="selected.ip_address"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">Tarayıcı Bilgisi</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.browser"></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-slate-400">İşletim Sistemi</dt>
                        <dd class="mt-0.5 font-medium text-gray-900 dark:text-white" x-text="selected.operating_system"></dd>
                    </div>
                </dl>

                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Eski Veri</p>
                    <pre class="max-h-40 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" x-text="selected.old_values_json"></pre>
                </div>

                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Yeni Veri</p>
                    <pre class="max-h-40 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" x-text="selected.new_values_json"></pre>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Açıklama</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-slate-300" x-text="selected.description"></p>
                </div>

                <template x-if="selected.session_insights">
                    <div class="rounded-lg border border-dashed border-gray-200 px-4 py-3 text-xs text-gray-500 dark:border-slate-600 dark:text-slate-400">
                        Oturum altyapısı hazır — aktif oturum: <span x-text="selected.session_insights?.active_session_count ?? 0"></span>
                    </div>
                </template>

                <div class="flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                    <template x-if="selected.user_profile_route">
                        <a :href="selected.user_profile_route" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                            Kullanıcı Profiline Git
                        </a>
                    </template>
                    <x-ui.button type="button" variant="secondary" @click="closeModal()">Kapat</x-ui.button>
                </div>
            </div>
        </template>
    </div>
</div>
