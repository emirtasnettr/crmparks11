<div
    x-show="activeModal === 'create'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'create'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'create'" x-transition class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Ödeme</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Ödeme no otomatik oluşturulacaktır (ODM-2026-XXXXXX)</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('finance.payments.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="space-y-1.5">
                <label for="payment_recipient_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alıcı Türü *</label>
                <select id="payment_recipient_type" name="recipient_type" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Alıcı türü seçin</option>
                    @foreach ($recipientTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('recipient_type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('recipient_type')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="payment_recipient_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Alıcı *</label>
                <select id="payment_recipient_id" name="recipient_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Alıcı seçin</option>
                    @foreach ($recipientsByType as $type => $recipients)
                        <optgroup label="{{ $recipientTypes[$type] }}">
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient['id'] }}" @selected(old('recipient_id') == $recipient['id'] && old('recipient_type') === $type)>{{ $recipient['name'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                @error('recipient_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1.5">
                <label for="payment_earning_line_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Hakediş Kaydı</label>
                <select id="payment_earning_line_id" name="earning_line_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Hakediş kaydı seçin</option>
                    @foreach ($earningOptions as $earning)
                        <option value="{{ $earning['id'] }}" @selected(old('earning_line_id') == $earning['id'])>{{ $earning['reference'] }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input type="date" name="payment_date" label="Ödeme Tarihi *" :value="old('payment_date', now()->toDateString())" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="number" step="0.01" min="0" name="total_amount" label="Toplam Tutar (₺, KDV Hariç) *" :value="old('total_amount')" />
                <x-ui.input type="number" step="0.01" min="0" name="paid_amount" label="Ödenen Tutar (₺, KDV Hariç)" :value="old('paid_amount')" />
            </div>

            <div class="space-y-1.5">
                <label for="payment_method" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Yöntemi</label>
                <select id="payment_method" name="payment_method" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    <option value="">Ödeme yöntemi seçin</option>
                    @foreach ($paymentMethods as $key => $label)
                        <option value="{{ $key }}" @selected(old('payment_method') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('payment_method')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input type="text" name="bank_account" label="Banka Hesabı" :value="old('bank_account')" placeholder="Örn. Garanti BBVA — TR..." />
            <x-ui.input type="text" name="payment_reference" label="Referans No" :value="old('payment_reference')" />

            <div class="space-y-1.5">
                <label for="payment_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="payment_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ old('description') }}</textarea>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
