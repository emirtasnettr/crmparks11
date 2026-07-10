<div
    x-show="activeModal === 'bulk'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'bulk'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'bulk'" x-transition class="relative w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Toplu Fatura Oluştur</h3>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.invoices.bulk') }}" class="space-y-4 px-6 py-4">
            @csrf

            <p class="text-sm text-gray-600 dark:text-slate-300">
                Faturası olmayan hakedişlerden toplu fatura oluşturun. Her hakediş için yalnızca bir fatura kesilebilir.
            </p>

            <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-slate-700">
                @forelse ($earningOptions as $option)
                    <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-slate-300">
                        <input
                            type="checkbox"
                            name="earning_ids[]"
                            value="{{ $option['id'] }}"
                            class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <span>
                            <span class="font-mono text-xs">{{ $option['reference'] }}</span>
                            — {{ $option['period_label'] }}
                            <span class="text-gray-500 dark:text-slate-400">({{ number_format($option['amount'], 2) }} ₺)</span>
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500 dark:text-slate-400">Fatura kesilebilecek hakediş bulunamadı.</p>
                @endforelse
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Fatura Türü</label>
                <select name="invoice_type" x-model="bulk.invoice_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($invoiceTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="date" name="invoice_date" label="Fatura Tarihi" x-model="bulk.invoice_date" />
            <x-ui.input type="number" name="vat_rate" step="1" min="0" max="100" label="KDV %" x-model="bulk.vat_rate" />

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Uygula</x-ui.button>
            </div>
        </form>
    </div>
</div>
