@if ($payment['can_update'] ?? false)
    <x-ui.card title="Kaydı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('finance.payments.update', $payment['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @if (empty($payment['earning_id']))
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="edit_recipient_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alıcı Türü *</label>
                        <select id="edit_recipient_type" name="recipient_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            @foreach ($recipientTypes as $key => $label)
                                <option value="{{ $key }}" @selected($payment['recipient_type'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label for="edit_recipient_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alıcı *</label>
                        <select id="edit_recipient_id" name="recipient_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                            @foreach ($recipientsByType[$payment['recipient_type']] ?? [] as $recipient)
                                <option value="{{ $recipient['id'] }}" @selected($payment['recipient_id'] == $recipient['id'])>{{ $recipient['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @else
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800/50 dark:bg-amber-900/20 dark:text-amber-200">
                    Hakediş kaynaklı ödemelerde alıcı bilgisi değiştirilemez.
                </p>
                <input type="hidden" name="recipient_type" value="{{ $payment['recipient_type'] }}">
                <input type="hidden" name="recipient_id" value="{{ $payment['recipient_id'] }}">
            @endif

            <x-ui.input type="date" name="payment_date" label="Ödeme Tarihi *" :value="$payment['scheduled_date']" />
            <x-ui.input type="number" step="0.01" min="0" name="total_amount" label="Toplam Tutar *" :value="$payment['total_amount']" />

            <div class="space-y-1.5">
                <label for="edit_payment_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_payment_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $payment['description'] }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('finance.payments.show', $payment['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif
