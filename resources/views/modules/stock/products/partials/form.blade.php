<form id="stock-product-form" method="POST" action="{{ $formAction }}" class="space-y-6">
    @csrf
    @if (($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    <x-ui.card title="Ürün Bilgileri">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-ui.input name="name" label="Ürün Adı *" :value="$formValues['name'] ?? ''" required />
            </div>
            <x-ui.input name="sku" label="SKU / Kod" :value="$formValues['sku'] ?? ''" placeholder="Örn. KSK-001" />
            <x-ui.select
                name="unit"
                label="Birim *"
                :selected="$formValues['unit'] ?? 'adet'"
                :options="$units"
            />
            <x-ui.input name="quantity" type="number" label="Stok Adedi *" :value="$formValues['quantity'] ?? '0'" min="0" required />
            <x-ui.select
                name="status"
                label="Durum *"
                :selected="$formValues['status'] ?? 'active'"
                :options="$statuses"
            />
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea
                    name="description"
                    rows="3"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >{{ $formValues['description'] ?? '' }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Notlar</label>
                <textarea
                    name="notes"
                    rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >{{ $formValues['notes'] ?? '' }}</textarea>
            </div>
        </div>
    </x-ui.card>
</form>
