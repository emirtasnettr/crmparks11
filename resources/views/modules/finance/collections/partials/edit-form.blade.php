@if ($collection['can_update'] ?? false)
    <x-ui.card title="Kaydı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('finance.collections.update', $collection['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="edit_collection_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                <select id="edit_collection_business_id" name="business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($businesses as $business)
                        <option value="{{ $business['id'] }}" @selected($collection['business_id'] == $business['id'])>{{ $business['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="edit_revenue_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gelir Kaydı</label>
                <select id="edit_revenue_id" name="revenue_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Bağlı değil</option>
                    @foreach ($revenueOptions as $revenue)
                        <option value="{{ $revenue['id'] }}" @selected($collection['revenue_id'] == $revenue['id'])>{{ $revenue['reference'] }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="text" name="invoice_no" label="Fatura No" :value="$collection['invoice_no']" />
            <x-ui.input type="date" name="due_date" label="Vade Tarihi *" :value="$collection['due_date']" />
            <x-ui.input type="number" step="0.01" min="0" name="total_amount" label="Toplam Tutar *" :value="$collection['total_amount']" />

            <div class="space-y-1.5">
                <label for="edit_collection_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_collection_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $collection['description'] }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('finance.collections.show', $collection['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif
