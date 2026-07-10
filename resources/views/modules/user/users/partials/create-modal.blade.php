        <form method="POST" action="{{ route('users.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="text" name="first_name" label="Ad *" :value="old('first_name')" />
                <x-ui.input type="text" name="last_name" label="Soyad *" :value="old('last_name')" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="tel" name="phone" label="Telefon *" :value="old('phone')" placeholder="05XX XXX XX XX" />
                <x-ui.input type="email" name="email" label="E-Posta *" :value="old('email')" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="password" name="password" label="Şifre *" />
                <x-ui.input type="password" name="password_confirmation" label="Şifre Tekrar *" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Rol * <span class="font-normal text-gray-500">(birden fazla seçilebilir)</span></label>
                <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-2 dark:border-slate-600">
                    @foreach ($roles as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, old('roles', []), true)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('roles')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı İşletme</label>
                    <select name="linked_business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business['id'] }}" @selected(old('linked_business_id') == $business['id'])>{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Kurye</label>
                    <select name="linked_courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier['id'] }}" @selected(old('linked_courier_id') == $courier['id'])>{{ $courier['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Acente</label>
                    <select name="linked_agency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency['id'] }}" @selected(old('linked_agency_id') == $agency['id'])>{{ $agency['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="user_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select id="user_status" name="status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', 'active') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>