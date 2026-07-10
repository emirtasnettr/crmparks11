@if ($expense['can_update'] ?? false)
    <x-ui.card title="Kaydı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('finance.expenses.update', $expense['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="space-y-1.5">
                <label for="edit_expense_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Gider Türü *</label>
                <select id="edit_expense_type" name="expense_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($expenseTypes as $key => $label)
                        <option value="{{ $key }}" @selected($expense['expense_type'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-1.5">
                    <label for="edit_courier_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Kurye</label>
                    <select id="edit_courier_id" name="courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier['id'] }}" @selected($expense['courier_id'] == $courier['id'])>{{ $courier['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="edit_agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Acente</label>
                    <select id="edit_agency_id" name="agency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency['id'] }}" @selected($expense['agency_id'] == $agency['id'])>{{ $agency['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-ui.input type="date" name="expense_date" label="Gider Tarihi *" :value="$expense['expense_date']" />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="amount" label="Tutar *" :value="$expense['amount']" />
                <x-ui.input type="number" step="1" min="0" max="100" name="vat_rate" label="KDV (%)" :value="$expense['vat_rate']" />
            </div>
            <x-ui.input type="text" name="document_no" label="Belge No" :value="$expense['document_no']" />

            <div class="space-y-1.5">
                <label for="edit_expense_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_expense_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $expense['description'] }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="edit_payment_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Durumu</label>
                <select id="edit_payment_status" name="payment_status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" @selected($expense['payment_status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('finance.expenses.show', $expense['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif
