@php
    $canFinancialAdjust = $canFinancialAdjust ?? false;
    $financialAdjustments = $financialAdjustments ?? [];
    $adjustStoreUrl = $adjustStoreUrl;
    $earningLineId = $earningLineId ?? null;
    $adjustmentHasErrors = $errors->hasAny(['direction', 'amount', 'reason', 'earning_line_id']);
@endphp

@if ($canFinancialAdjust)
    <div
        x-data="{ open: {{ $adjustmentHasErrors ? 'true' : 'false' }}, direction: @js(old('direction', 'credit')) }"
        @keydown.escape.window="open = false"
        class="contents"
    >
        <x-ui.button type="button" variant="secondary" @click="open = true">
            Tutar Düzelt
        </x-ui.button>

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
        >
            <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-gray-900/50"></div>

            <div
                x-show="open"
                x-transition
                class="relative w-full max-w-lg overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tutar Düzelt</h3>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">Neden zorunludur ve kayıt altına alınır.</p>
                    </div>
                    <button type="button" @click="open = false" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ $adjustStoreUrl }}" class="space-y-4 px-6 py-4">
                    @csrf
                    @if ($earningLineId)
                        <input type="hidden" name="earning_line_id" value="{{ $earningLineId }}">
                    @endif

                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">İşlem *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-slate-600"
                                   :class="direction === 'credit' ? 'border-emerald-400 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10' : ''">
                                <input type="radio" name="direction" value="credit" x-model="direction" class="text-primary-600">
                                <span>Ekle</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-slate-600"
                                   :class="direction === 'debit' ? 'border-red-400 bg-red-50 dark:border-red-500/40 dark:bg-red-500/10' : ''">
                                <input type="radio" name="direction" value="debit" x-model="direction" class="text-primary-600">
                                <span>Düşür</span>
                            </label>
                        </div>
                        @error('direction')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-ui.input
                        type="number"
                        name="amount"
                        label="Tutar (TL) *"
                        step="0.01"
                        min="0.01"
                        :value="old('amount')"
                        required
                    />
                    @error('amount')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="space-y-1.5">
                        <label for="financial_adjustment_reason" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Neden *</label>
                        <textarea
                            id="financial_adjustment_reason"
                            name="reason"
                            rows="3"
                            required
                            minlength="5"
                            maxlength="2000"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                            placeholder="Düzeltme gerekçesini yazın..."
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-slate-700">
                        <x-ui.button type="button" variant="secondary" @click="open = false">Vazgeç</x-ui.button>
                        <x-ui.button type="submit">Kaydet</x-ui.button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
