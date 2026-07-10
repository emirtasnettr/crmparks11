@if ($invoice['can_update'] ?? false)
    <x-ui.card title="Kaydı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('finance.invoices.update', $invoice['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="edit_invoice_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select id="edit_invoice_business_id" name="business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}" @selected($invoice['business_id'] == $business['id'])>{{ $business['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="edit_invoice_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Fatura Türü</label>
                <select id="edit_invoice_type" name="invoice_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($invoiceTypes as $key => $label)
                        <option value="{{ $key }}" @selected($invoice['invoice_type'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input type="date" name="invoice_date" label="Fatura Tarihi *" :value="$invoice['invoice_date']" />
                <x-ui.input type="date" name="due_date" label="Vade Tarihi *" :value="$invoice['due_date']" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="subtotal" label="Ara Toplam *" :value="$invoice['subtotal']" />
                <x-ui.input type="number" step="1" min="0" max="100" name="vat_rate" label="KDV (%)" :value="$invoice['vat_rate']" />
            </div>

            <div class="space-y-1.5">
                <label for="edit_invoice_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_invoice_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $invoice['description'] }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('finance.invoices.show', $invoice['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif
