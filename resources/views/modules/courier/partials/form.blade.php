@php
    $cancelUrl = $cancelUrl ?? route('couriers.index');
    $submitLabel = $submitLabel ?? 'Kaydet';
    $isEdit = $isEdit ?? false;
    $formAction = $formAction ?? '#';
    $formMethod = strtoupper($formMethod ?? 'POST');
    $useServerSubmit = $formAction !== '#';
    $photoUrl = $photoUrl ?? null;
@endphp

<form
    id="courier-form"
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
            <div class="md:col-span-2">
                <x-ui.file-upload name="profile_photo" label="Profil Fotoğrafı" accept="image/png,image/jpeg,image/jpg,image/webp" :current-url="$photoUrl" />
            </div>

            <x-ui.input
                name="first_name"
                label="Ad *"
                x-model="form.first_name"
                ::class="errors.first_name ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input
                name="last_name"
                label="Soyad *"
                x-model="form.last_name"
                ::class="errors.last_name ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input name="tc_number" label="TC Kimlik No" placeholder="11 haneli" x-model="form.tc_number" maxlength="11" />
            <div class="space-y-1.5">
                <label for="birth_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Doğum Tarihi</label>
                <input
                    id="birth_date"
                    name="birth_date"
                    type="date"
                    x-model="form.birth_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                />
            </div>
            <x-ui.input
                name="phone"
                type="tel"
                label="Telefon *"
                placeholder="05xx xxx xx xx"
                x-model="form.phone"
                ::class="errors.phone ? 'border-red-300 dark:border-red-500' : ''"
            />
            <x-ui.input
                name="email"
                type="email"
                label="E-Posta *"
                x-model="form.email"
                ::class="errors.email ? 'border-red-300 dark:border-red-500' : ''"
            />
        </div>

        <p x-show="errors.first_name" x-cloak class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="errors.first_name"></p>
        <p x-show="errors.last_name" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.last_name"></p>
        <p x-show="errors.phone" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.phone"></p>
        <p x-show="errors.email" x-cloak class="mt-1 text-sm text-red-600 dark:text-red-400" x-text="errors.email"></p>
    </x-ui.card>

    {{-- Kart 2: Kurye Bilgileri --}}
    <x-ui.card title="Kurye Bilgileri">
        <x-ui.radio-group
            name="courier_type"
            label="Kurye Tipi *"
            :options="$courierTypes"
            selected="independent"
            x-model="form.courier_type"
        />

        <p x-show="errors.courier_type" x-cloak class="mb-4 text-sm text-red-600 dark:text-red-400" x-text="errors.courier_type"></p>

        <div x-show="form.courier_type === 'agency'" x-cloak class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="agency_id" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Acente *</label>
                <select
                    id="agency_id"
                    name="agency_id"
                    x-model="form.agency_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="errors.agency_id ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Acente seçin</option>
                    @foreach ($agencies as $agency)
                        <option value="{{ $agency['id'] }}">{{ $agency['name'] }}</option>
                    @endforeach
                </select>
                <p x-show="errors.agency_id" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.agency_id"></p>
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 3: Vergi Bilgileri --}}
    <x-ui.card title="Vergi Bilgileri" x-show="form.courier_type === 'independent'" x-cloak>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui.input name="tax_office" label="Vergi Dairesi" x-model="form.tax_office" />
            <x-ui.input name="tax_number" label="Vergi Numarası" x-model="form.tax_number" />
            <div class="md:col-span-2">
                <x-ui.input name="company_name" label="Şirket Ünvanı" x-model="form.company_name" />
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 4: Adres Bilgileri --}}
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

    {{-- Kart 5: Araç Bilgileri --}}
    <x-ui.card title="Araç Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="vehicle_type" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Araç Tipi *</label>
                <select
                    id="vehicle_type"
                    name="vehicle_type"
                    x-model="form.vehicle_type"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="errors.vehicle_type ? 'border-red-300 dark:border-red-500' : ''"
                >
                    <option value="">Araç tipi seçin</option>
                    @foreach ($vehicleTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p x-show="errors.vehicle_type" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.vehicle_type"></p>
            </div>

            <x-ui.input name="plate" label="Plaka" placeholder="34 ABC 123" x-model="form.plate" />
            <x-ui.input name="vehicle_brand" label="Marka" x-model="form.vehicle_brand" />
            <x-ui.input name="vehicle_model" label="Model" x-model="form.vehicle_model" />
        </div>
    </x-ui.card>

    {{-- Kart 6: Banka Bilgileri --}}
    <x-ui.card title="Banka Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="bank_name" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Banka Adı</label>
                <select
                    id="bank_name"
                    name="bank_name"
                    x-model="form.bank_name"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    <option value="">Banka seçin</option>
                    @foreach ($banks as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <x-ui.input name="iban" label="IBAN" placeholder="TR00 0000 0000 0000 0000 0000 00" x-model="form.iban" />
            <div class="md:col-span-2">
                <x-ui.input name="account_holder" label="Hesap Sahibi" x-model="form.account_holder" />
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 7: Çalışma Bilgileri --}}
    <x-ui.card title="Çalışma Bilgileri">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Başlangıç Tarihi *</label>
                <input
                    id="start_date"
                    name="start_date"
                    type="date"
                    x-model="form.start_date"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    :class="errors.start_date ? 'border-red-300 dark:border-red-500' : ''"
                />
                <p x-show="errors.start_date" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="errors.start_date"></p>
            </div>

            <div class="space-y-1.5">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select
                    id="status"
                    name="status"
                    x-model="form.status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    {{-- Kart 8: Notlar --}}
    <x-ui.card title="Notlar">
        <x-ui.textarea name="notes" rows="5" placeholder="Kurye hakkında ek notlar..." x-model="form.notes" />
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
