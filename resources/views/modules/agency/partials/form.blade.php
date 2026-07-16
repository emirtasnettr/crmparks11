@php
    $cancelUrl = $cancelUrl ?? route('agencies.index');
    $submitLabel = $submitLabel ?? 'Kaydet';
    $isEdit = $isEdit ?? false;
    $formAction = $formAction ?? '#';
    $formMethod = strtoupper($formMethod ?? 'POST');
    $useServerSubmit = $formAction !== '#';
    $logoUrl = $logoUrl ?? null;
@endphp

<form
    id="agency-form"
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
        <div class="mb-4">
            <x-ui.file-upload name="logo" label="Logo" :current-url="$logoUrl" />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input
                name="company_name"
                label="Firma Ünvanı *"
                x-model="form.company_name"
                ::class="errors.company_name ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input
                name="brand_name"
                label="Marka Adı *"
                x-model="form.brand_name"
                ::class="errors.brand_name ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input
                name="phone"
                type="tel"
                label="Telefon *"
                placeholder="0212 000 00 00"
                x-model="form.phone"
                ::class="errors.phone ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input
                name="tax_number"
                label="Vergi Numarası *"
                x-model="form.tax_number"
                ::class="errors.tax_number ? 'border-red-300 dark:border-red-500' : ''"
            />
        </div>

        <p x-show="errors.company_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.company_name"></p>
        <p x-show="errors.brand_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.brand_name"></p>
        <p x-show="errors.phone" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.phone"></p>
        <p x-show="errors.tax_number" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.tax_number"></p>
    </x-ui.card>

    {{-- Kart 2: Adres Bilgileri --}}
    <x-ui.card title="Adres Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="city" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İl *</label>
                <input type="hidden" name="city" :value="form.city">
                <select
                    id="city"
                    x-model="form.city"
                    @change="onCityChange"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="errors.city ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">İl seçin</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
                <p x-show="errors.city" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.city"></p>
            </div>

            <div class="space-y-1.5">
                <label for="district" class="block text-sm font-medium text-gray-700 dark:text-slate-300">İlçe *</label>
                <input type="hidden" name="district" :value="form.district">
                <select
                    id="district"
                    x-model="form.district"
                    :disabled="!form.city"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:disabled:bg-slate-700/50"
                    :class="errors.district ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="" x-text="form.city ? 'İlçe seçin' : 'Önce il seçin'"></option>
                    <template x-for="district in districts" :key="district">
                        <option :value="district" x-text="district" :selected="form.district === district"></option>
                    </template>
                </select>
                <p x-show="errors.district" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.district"></p>
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 3: Durum --}}
    <x-ui.card title="Durum">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.select
                name="status"
                label="Durum"
                :options="$statuses"
                x-model="form.status"
            />
        </div>
    </x-ui.card>

    {{-- Kart 4: Notlar --}}
    <x-ui.card title="Notlar">
        <x-ui.textarea name="notes" rows="5" placeholder="Acente hakkında ek notlar..." x-model="form.notes" />
    </x-ui.card>

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
