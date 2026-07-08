<div
    x-show="openAssignModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="openAssignModal" x-transition.opacity @click="closeAssignModal" class="fixed inset-0 bg-gray-900/50"></div>

    <div
        x-show="openAssignModal"
        x-transition
        class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Kurye Ata</h3>
            <button type="button" @click="closeAssignModal" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveAssignment" class="space-y-4 px-6 py-4">
            @php
                $hideEntitySelector = $hideEntitySelector ?? false;
                $presetEntityLabel = $presetEntityLabel ?? null;
            @endphp

            @if ($hideEntitySelector)
                <x-entity.locked-field label="Acente" :value="$presetEntityLabel" />
            @else
                <div class="space-y-1.5">
                    <label for="assign_agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Acente *</label>
                    <select
                        id="assign_agency_id"
                        x-model="assignModal.agency_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        :class="assignErrors.agency_id ? 'border-red-300 dark:border-red-500' : ''"
                    >
                        <option value="">Acente seçin</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency['id'] }}">{{ $agency['name'] }}</option>
                        @endforeach
                    </select>
                    <p x-show="assignErrors.agency_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="assignErrors.agency_id"></p>
                </div>
            @endif

            <div class="space-y-1.5">
                <label for="assign_courier_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye *</label>
                <select
                    id="assign_courier_id"
                    x-model="assignModal.courier_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="assignErrors.courier_id ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Kurye seçin</option>
                    @foreach ($couriers as $courier)
                        <option value="{{ $courier['id'] }}">{{ $courier['name'] }} — {{ $courier['phone'] }}</option>
                    @endforeach
                </select>
                <p x-show="assignErrors.courier_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="assignErrors.courier_id"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="assign_start_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                    <input
                        id="assign_start_date"
                        type="date"
                        x-model="assignModal.start_date"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                        :class="assignErrors.start_date ? 'border-red-300 dark:border-red-500' : ''"
                    />
                    <p x-show="assignErrors.start_date" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="assignErrors.start_date"></p>
                </div>

                <div class="space-y-1.5">
                    <label for="assign_end_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Tarihi (Opsiyonel)</label>
                    <input
                        id="assign_end_date"
                        type="date"
                        x-model="assignModal.end_date"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    />
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="assign_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select
                    id="assign_status"
                    x-model="assignModal.status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="active">Aktif</option>
                    <option value="on_leave">İzinli</option>
                    <option value="inactive">Pasif</option>
                </select>
            </div>

            <x-ui.textarea name="notes" label="Notlar" rows="3" x-model="assignModal.notes" />

            <div x-show="assignSaved" x-cloak>
                <x-ui.alert type="success">
                    Atama bilgileri doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.
                </x-ui.alert>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <x-ui.button type="button" variant="secondary" @click="closeAssignModal">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
