@if ($revenue['can_update'] ?? false)
    <x-ui.card title="Kaydı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('finance.revenues.update', $revenue['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="edit_revenue_business_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme *</label>
                    <select id="edit_revenue_business_id" name="business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($businesses as $business)
                            <option value="{{ $business['id'] }}" @selected($revenue['business_id'] == $business['id'])>{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="edit_revenue_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gelir Türü *</label>
                    <select id="edit_revenue_type" name="revenue_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($revenueTypes as $key => $label)
                            <option value="{{ $key }}" @selected($revenue['revenue_type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-ui.input type="text" name="period_label" label="Hakediş Dönemi" :value="$revenue['period_label']" />
            <x-ui.input type="text" name="invoice_no" label="Fatura No" :value="$revenue['invoice_no']" />
            <x-ui.input type="date" name="revenue_date" label="Gelir Tarihi" :value="$revenue['revenue_date']" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="amount" label="Tutar (₺, KDV Hariç) *" :value="$revenue['amount']" />
                <x-ui.input type="number" step="1" min="0" max="100" name="vat_rate" label="KDV Oranı (%)" :value="$revenue['vat_rate']" />
            </div>

            <div class="space-y-1.5">
                <label for="edit_revenue_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_revenue_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $revenue['description'] }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="edit_collection_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Tahsil Durumu</label>
                <select id="edit_collection_status" name="collection_status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($collectionStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($revenue['collection_status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('finance.revenues.show', $revenue['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif
