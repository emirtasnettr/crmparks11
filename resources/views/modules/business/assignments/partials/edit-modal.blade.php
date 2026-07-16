<div
    x-show="openEditModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="openEditModal"
        x-transition.opacity
        @click="openEditModal = false"
        class="fixed inset-0 bg-gray-900/50"
    ></div>

    <div
        x-show="openEditModal"
        x-transition
        class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
    >
        <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Atamayı Düzenle</h3>
            <button
                type="button"
                @click="openEditModal = false"
                class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-4">
            <form
                method="POST"
                action="{{ route('businesses.assignments.update', $assignment['id']) }}"
                class="space-y-4"
            >
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input
                        type="date"
                        name="start_date"
                        label="Başlangıç Tarihi *"
                        :value="$assignment['start_date']"
                        required
                    />
                    <x-ui.input
                        type="date"
                        name="end_date"
                        label="Bitiş Tarihi"
                        :value="$assignment['end_date']"
                    />
                </div>

                <div class="space-y-1.5">
                    <label for="assignment_edit_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                    <select
                        id="assignment_edit_status"
                        name="status"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    >
                        <option value="active" @selected($assignment['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($assignment['status'] === 'inactive')>Pasif</option>
                    </select>
                </div>

                <x-ui.textarea name="notes" label="Görev Notu" rows="3" :value="$assignment['notes'] ?? ''" />

                <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                    <x-ui.button type="button" variant="secondary" @click="openEditModal = false">
                        İptal
                    </x-ui.button>
                    <x-ui.button type="submit">Kaydet</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
