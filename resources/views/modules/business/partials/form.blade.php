@php
    use App\Modules\Business\Support\BusinessFeatures;
@endphp

@php
    $cancelUrl = $cancelUrl ?? route('businesses.index');
    $submitLabel = $submitLabel ?? 'Kaydet';
    $isEdit = $isEdit ?? false;
    $formAction = $formAction ?? '#';
    $formMethod = strtoupper($formMethod ?? 'POST');
    $useServerSubmit = $formAction !== '#';
    $logoUrl = $logoUrl ?? null;
@endphp

<form
    id="business-form"
    method="POST"
    action="{{ $formAction }}"
    enctype="multipart/form-data"
    @if ($useServerSubmit)
        @submit="handleSubmit($event)"
    @else
        @submit.prevent="submit"
    @endif
    class="space-y-6"
>
    @csrf
    @if (in_array($formMethod, ['PUT', 'PATCH', 'DELETE'], true))
        @method($formMethod)
    @endif
    {{-- Kart 1: Genel Bilgiler --}}
    <x-ui.card title="Genel Bilgiler">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input
                name="company_name"
                label="Firma Ünvanı *"
                x-model="form.company_name"
                ::class="errors.company_name ? 'border-red-300' : ''"
            />
            <x-ui.input
                name="brand_name"
                label="Marka Adı *"
                x-model="form.brand_name"
                ::class="errors.brand_name ? 'border-red-300' : ''"
            />
            <x-ui.input
                name="phone"
                type="tel"
                label="Telefon Numarası *"
                placeholder="0212 000 00 00"
                x-model="form.phone"
            />
            <x-ui.input name="email" type="email" label="E-Posta" x-model="form.email" />
            <x-ui.input name="website" type="url" label="Web Sitesi" placeholder="https://" x-model="form.website" />
            <x-ui.file-upload name="logo" label="Logo Yükle" :current-url="$logoUrl" />
        </div>
        <p x-show="errors.company_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.company_name"></p>
        <p x-show="errors.brand_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.brand_name"></p>
        <p x-show="errors.phone" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.phone"></p>
    </x-ui.card>

    {{-- Kart 2: Vergi Bilgileri --}}
    <x-ui.card title="Vergi Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input name="tax_office" label="Vergi Dairesi" x-model="form.tax_office" />
            <x-ui.input name="tax_number" label="Vergi Numarası" x-model="form.tax_number" />
        </div>
    </x-ui.card>

    {{-- Kart 3: Adres Bilgileri --}}
    <x-ui.card title="Adres Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="city" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İl</label>
                <input type="hidden" name="city" :value="form.city">
                <select
                    id="city"
                    x-model="form.city"
                    @change="onCityChange"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">İl seçin</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="district" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İlçe</label>
                <input type="hidden" name="district" :value="form.district">
                <select
                    id="district"
                    x-model="form.district"
                    :disabled="!form.city"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:disabled:bg-slate-700/50"
                >
                    <option value="" x-text="form.city ? 'İlçe seçin' : 'Önce il seçin'"></option>
                    <template x-for="district in districts" :key="district">
                        <option :value="district" x-text="district" :selected="form.district === district"></option>
                    </template>
                </select>
            </div>

            <div class="md:col-span-2">
                <x-ui.textarea name="address" label="Açık Adres" rows="3" x-model="form.address" />
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 4: Çalışma Modeli --}}
    <x-ui.card title="Çalışma Modeli">
        <x-ui.radio-group
            name="pricing_model"
            label="Çalışma Modeli *"
            :options="$pricingModels"
            :selected="($formValues ?? [])['pricing_model'] ?? 'per_package'"
            x-model="form.pricing_model"
        />

        <p x-show="errors.pricing_model" x-cloak class="mb-4 text-sm text-red-600 dark:text-red-400" x-text="errors.pricing_model"></p>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div x-show="form.pricing_model === 'per_package'" x-cloak>
                <x-ui.input
                    name="customer_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['per_package']['customer'] }}"
                    x-model="form.customer_price"
                    ::disabled="form.pricing_model !== 'per_package'"
                />
            </div>
            <div x-show="form.pricing_model === 'per_package'" x-cloak>
                <x-ui.input
                    name="courier_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['per_package']['courier'] }}"
                    x-model="form.courier_price"
                    ::disabled="form.pricing_model !== 'per_package'"
                />
            </div>

            <div x-show="form.pricing_model === 'monthly_fixed'" x-cloak>
                <x-ui.input
                    name="customer_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['monthly_fixed']['customer'] }}"
                    x-model="form.customer_price"
                    ::disabled="form.pricing_model !== 'monthly_fixed'"
                />
            </div>
            <div x-show="form.pricing_model === 'monthly_fixed'" x-cloak>
                <x-ui.input
                    name="courier_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['monthly_fixed']['courier'] }}"
                    x-model="form.courier_price"
                    ::disabled="form.pricing_model !== 'monthly_fixed'"
                />
            </div>

            <div x-show="form.pricing_model === 'hourly'" x-cloak>
                <x-ui.input
                    name="customer_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['hourly']['customer'] }}"
                    x-model="form.customer_price"
                    ::disabled="form.pricing_model !== 'hourly'"
                />
            </div>
            <div x-show="form.pricing_model === 'hourly'" x-cloak>
                <x-ui.input
                    name="courier_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['hourly']['courier'] }}"
                    x-model="form.courier_price"
                    ::disabled="form.pricing_model !== 'hourly'"
                />
            </div>

            <div x-show="form.pricing_model === 'daily'" x-cloak>
                <x-ui.input
                    name="customer_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['daily']['customer'] }}"
                    x-model="form.customer_price"
                    ::disabled="form.pricing_model !== 'daily'"
                />
            </div>
            <div x-show="form.pricing_model === 'daily'" x-cloak>
                <x-ui.input
                    name="courier_price"
                    type="number"
                    step="0.01"
                    min="0"
                    label="{{ $pricingFieldLabels['daily']['courier'] }}"
                    x-model="form.courier_price"
                    ::disabled="form.pricing_model !== 'daily'"
                />
            </div>
        </div>
    </x-ui.card>

    @if (BusinessFeatures::earningsEnabled())
    {{-- Kart 5: Hakediş Bilgisi --}}
    <x-ui.card title="Hakediş Bilgisi">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="earning_period" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Hakediş Periyodu *</label>
                <select
                    id="earning_period"
                    name="earning_period"
                    x-model="form.earning_period"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="errors.earning_period ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Periyot seçin</option>
                    @foreach ($earningPeriods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p x-show="errors.earning_period" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.earning_period"></p>
            </div>
        </div>
    </x-ui.card>
    @endif

    {{-- Kart 6: Durum --}}
    <x-ui.card title="Durum">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.select
                name="status"
                label="Durum *"
                :options="$statuses"
                x-model="form.status"
            />
            <div>
                <x-ui.input
                    name="planned_courier_count"
                    type="number"
                    min="1"
                    step="1"
                    label="Planlanan Kurye Sayısı *"
                    placeholder="Örn. 5"
                    x-model="form.planned_courier_count"
                    ::class="errors.planned_courier_count ? 'border-red-300' : ''"
                />
                <p x-show="errors.planned_courier_count" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.planned_courier_count"></p>
            </div>

            <div x-show="form.status === 'inactive'" x-cloak>
                <x-ui.input
                    name="contract_end_date"
                    type="date"
                    label="Sözleşme Bitiş Tarihi *"
                    x-model="form.contract_end_date"
                    ::class="errors.contract_end_date ? 'border-red-300' : ''"
                />
                <p x-show="errors.contract_end_date" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.contract_end_date"></p>
            </div>

            <div x-show="form.status === 'pending' || form.status === 'contract_stage'" x-cloak>
                <x-ui.input
                    name="estimated_opening_date"
                    type="date"
                    label="Tahmini Açılış Tarihi *"
                    x-model="form.estimated_opening_date"
                    ::class="errors.estimated_opening_date ? 'border-red-300' : ''"
                />
                <p x-show="errors.estimated_opening_date" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.estimated_opening_date"></p>
            </div>

            <div x-show="form.status === 'opening_stage'" x-cloak>
                <x-ui.input
                    name="start_date"
                    type="date"
                    label="Başlangıç Tarihi *"
                    x-model="form.start_date"
                    ::class="errors.start_date ? 'border-red-300' : ''"
                />
                <p x-show="errors.start_date" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.start_date"></p>
            </div>
        </div>

        <p x-show="form.status !== 'active'" x-cloak class="mt-3 text-xs text-gray-500 dark:text-slate-400">
            Not alanı isteğe bağlıdır; durumla ilgili açıklama eklemek için aşağıdaki Notlar bölümünü kullanabilirsiniz.
        </p>
    </x-ui.card>

    {{-- Kart 7: Notlar --}}
    <x-ui.card title="Notlar">
        <x-ui.textarea name="notes" rows="5" placeholder="İşletme hakkında ek notlar..." x-model="form.notes" />
    </x-ui.card>

    <div x-show="submitted && !isEdit" x-cloak>
        <x-ui.alert type="success">
            {{ $isEdit ? 'Değişiklikler doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.' : 'Form doğrulandı. Kayıt işlemi backend bağlantısı sonrası aktif olacaktır.' }}
        </x-ui.alert>
    </div>

    {{-- Alt Butonlar --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <x-ui.button type="button" variant="secondary" href="{{ $cancelUrl }}">
            İptal
        </x-ui.button>
        <x-ui.button type="submit" ::disabled="submitting">
            <span x-show="!submitting">{{ $submitLabel }}</span>
            <span x-show="submitting" x-cloak>{{ $isEdit ? 'Güncelleniyor...' : 'Kaydediliyor...' }}</span>
        </x-ui.button>
    </div>
</form>
