<div
    x-show="activeModal === 'bulk'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'bulk'" x-transition.opacity @click="closeModals" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'bulk'" x-transition class="relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Toplu Hakediş / Excel Yükle</h3>
            <button type="button" @click="closeModals" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('agencies.earnings.import') }}" enctype="multipart/form-data" class="space-y-4 px-6 py-4">
            @csrf

            <p class="text-sm text-gray-600 dark:text-slate-300">
                Şablonu indirip satırları doldurun. <code class="text-xs">isletme_id</code>, <code class="text-xs">kurye_id</code>, dönem ve çalışma modeli zorunludur.
            </p>

            <x-ui.button href="{{ route('agencies.earnings.template') }}" variant="secondary" class="w-full">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Şablonu İndir
            </x-ui.button>

            <x-ui.file-upload
                name="file"
                label="Excel Dosyası Seç"
                accept=".xlsx,.xls,.csv"
                :max-size-mb="5"
                hint="XLSX, XLS, CSV (maks. 5MB)"
                :error="$errors->first('file')"
            />

            <div class="flex flex-col-reverse gap-2 pt-2 sm:flex-row sm:justify-end">
                <x-ui.button type="button" variant="secondary" @click="closeModals">İptal</x-ui.button>
                <x-ui.button type="submit">İçe Aktar</x-ui.button>
            </div>
        </form>
    </div>
</div>
