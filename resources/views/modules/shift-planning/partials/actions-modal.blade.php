<div
    x-show="openActionsModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openActionsModal" x-transition.opacity @click="closeActionsModal()" class="fixed inset-0 bg-gray-900/50"></div>
    <div
        x-show="openActionsModal"
        x-transition
        class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="actionsShift?.name"></h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                    <span x-text="actionsShift?.time_range"></span>
                    ·
                    <span x-text="formatWorkDate(actionsWorkDate)"></span>
                </p>
            </div>
            <button type="button" @click="closeActionsModal()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="space-y-4 px-6 py-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Bu günün kuryeleri</p>
                <template x-if="dayCourierCount() === 0">
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bu güne henüz kurye atanmamış.</p>
                </template>
                <ul class="mt-2 space-y-1" x-show="dayCourierCount() > 0">
                    <template x-for="courier in dayCouriers()" :key="courier.id">
                        <li class="text-sm text-gray-800 dark:text-slate-200" x-text="courier.name"></li>
                    </template>
                </ul>
            </div>

            <div class="flex flex-wrap gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <template x-if="canUpdate">
                    <x-ui.button type="button" variant="secondary" @click="editFromActions()">Vardiyayı Düzenle</x-ui.button>
                </template>
                <template x-if="canUpdate">
                    <x-ui.button type="button" @click="assignFromActions()">Bu Güne Kurye Ata</x-ui.button>
                </template>
                <template x-if="canDelete">
                    <x-ui.button type="button" variant="danger" @click="openDeleteConfirm()">Sil</x-ui.button>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- Silme kapsamı seçimi --}}
<div
    x-show="openDeleteModal"
    x-cloak
    class="fixed inset-0 z-[60] flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openDeleteModal" x-transition.opacity @click="closeDeleteModal()" class="fixed inset-0 bg-gray-900/50"></div>
    <div
        x-show="openDeleteModal"
        x-transition
        class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Vardiya silinsin mi?</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                <span x-text="actionsShift?.name"></span>
                ·
                <span x-text="actionsShift?.time_range"></span>
            </p>
        </div>

        <div class="space-y-3 px-6 py-4">
            <p class="text-sm text-gray-600 dark:text-slate-300">
                Aynı saatteki vardiyaların tamamı mı silinsin, yoksa sadece seçtiğiniz gün mü?
            </p>

            <form method="POST" :action="destroyFormAction()" class="space-y-3">
                @csrf
                @method('DELETE')
                <input type="hidden" name="week" :value="week.week_start">
                <input type="hidden" name="work_date" :value="actionsWorkDate">

                <button
                    type="submit"
                    name="scope"
                    value="day"
                    class="flex w-full flex-col rounded-lg border border-gray-200 px-4 py-3 text-left transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-slate-600 dark:hover:border-primary-500/40 dark:hover:bg-primary-600/10"
                >
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">Sadece bu gün</span>
                    <span class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">
                        <span x-text="formatWorkDate(actionsWorkDate)"></span> tarihindeki vardiya silinir. Diğer günler kalır.
                    </span>
                </button>

                <button
                    type="submit"
                    name="scope"
                    value="all"
                    class="flex w-full flex-col rounded-lg border border-red-200 px-4 py-3 text-left transition hover:bg-red-50 dark:border-red-500/40 dark:hover:bg-red-500/10"
                >
                    <span class="text-sm font-semibold text-red-700 dark:text-red-300">Aynı saatteki tüm günler</span>
                    <span class="mt-0.5 text-xs text-red-600/80 dark:text-red-300/80">
                        Tarih aralığındaki bu vardiyanın tamamı silinir.
                    </span>
                </button>
            </form>

            <div class="flex justify-end pt-1">
                <x-ui.button type="button" variant="secondary" @click="closeDeleteModal()">Vazgeç</x-ui.button>
            </div>
        </div>
    </div>
</div>
