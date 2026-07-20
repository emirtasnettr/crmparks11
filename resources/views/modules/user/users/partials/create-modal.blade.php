<div
    x-show="activeModal === 'create'"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="activeModal === 'create'" x-transition.opacity @click="closeModals()" class="fixed inset-0 bg-gray-900/50"></div>

    <div x-show="activeModal === 'create'" x-transition class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Yeni Kullanıcı</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Personel ve portal hesapları için. Kurye hesapları Kuryeler modülünden oluşur.</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

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
                    @foreach ($assignableRoles as $key => $label)
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

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
    </div>
</div>
