<?php

/**
 * @var array<string, string> $workTypes
 * @var array<string, string> $paymentPeriods
 * @var int|null $presetBusinessId
 * @var string|null $presetBusinessLabel
 */
$workTypes = $workTypes ?? \App\Modules\Business\Data\BusinessCommercialContractFormData::workTypes();
$paymentPeriods = $paymentPeriods ?? \App\Modules\Business\Data\BusinessCommercialContractFormData::paymentPeriods();
$presetBusinessId = $presetBusinessId ?? null;
$presetBusinessLabel = $presetBusinessLabel ?? null;
?>

<div
    x-show="openCommercialContractModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div class="fixed inset-0 bg-gray-900/50" x-on:click="openCommercialContractModal = false"></div>
    <div class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Kontrat</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Yeni kontrat kaydedildiğinde mevcut aktif kontrat sonlanır; geçmiş tutarlar korunur.
        </p>

        <form method="POST" action="{{ route('businesses.commercial-contracts.store') }}" class="mt-4 space-y-4"
              x-data="{
                  workType: 'hourly',
                  businessAmount: '',
                  courierAmount: '',
                  get netProfit() {
                      const a = parseFloat(this.businessAmount);
                      const b = parseFloat(this.courierAmount);
                      if (Number.isNaN(a) || Number.isNaN(b)) return '';
                      return (a - b).toFixed(2);
                  }
              }">
            @csrf
            @if ($presetBusinessId)
                <input type="hidden" name="business_id" value="{{ $presetBusinessId }}">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme</label>
                    <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">{{ $presetBusinessLabel }}</p>
                </div>
            @endif

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                    <input type="date" name="start_date" required value="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Bitiş Tarihi</label>
                    <input type="date" name="end_date" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Çalışma Şekli *</label>
                <select name="work_type" x-model="workType" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    @foreach ($workTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">İşletmeden Alınan * <span class="font-normal text-gray-400">(KDV hariç)</span></label>
                    <input type="number" step="0.01" min="0" name="business_amount" required x-model="businessAmount" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Kuryeye Verilen * <span class="font-normal text-gray-400">(KDV hariç)</span></label>
                    <input type="number" step="0.01" min="0" name="courier_amount" required x-model="courierAmount" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Net Kazanç <span class="font-normal text-gray-400">(KDV hariç)</span></label>
                <input type="text" :value="netProfit" readonly class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300">
            </div>

            <div x-show="workType === 'per_package'" x-cloak>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Saatlik Garanti Paket Ücreti <span class="font-normal text-gray-400">(opsiyonel, KDV hariç)</span></label>
                <input type="number" step="0.01" min="0" name="guaranteed_hourly_package_fee" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <p class="mt-1 text-xs text-gray-500">Yalnızca paket başı çalışmada. Girilirse vardiya hakedişi bu saatlik tutardan hesaplanır.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Ödeme Periyodu *</label>
                <select name="payment_period" required class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    @foreach ($paymentPeriods as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'monthly')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Not</label>
                <textarea name="notes" rows="2" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="secondary" x-on:click="openCommercialContractModal = false">Vazgeç</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
