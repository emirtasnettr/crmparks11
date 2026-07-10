@if ($user['can_update'] ?? false)
    <x-ui.card title="Kullanıcıyı Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('users.update', $user['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="text" name="first_name" label="Ad *" :value="$user['first_name']" />
                <x-ui.input type="text" name="last_name" label="Soyad *" :value="$user['last_name']" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="tel" name="phone" label="Telefon *" :value="$user['phone']" />
                <x-ui.input type="email" name="email" label="E-Posta *" :value="$user['email']" />
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input type="password" name="password" label="Yeni Şifre" placeholder="Değiştirmek istemiyorsanız boş bırakın" />
                <x-ui.input type="password" name="password_confirmation" label="Şifre Tekrar" />
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Roller *</label>
                <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-2 dark:border-slate-600">
                    @foreach ($roles as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" name="roles[]" value="{{ $key }}" @checked(in_array($key, $user['roles'], true)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı İşletme</label>
                    <select name="linked_business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business['id'] }}" @selected($user['linked_business_id'] == $business['id'])>{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Kurye</label>
                    <select name="linked_courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier['id'] }}" @selected($user['linked_courier_id'] == $courier['id'])>{{ $courier['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Acente</label>
                    <select name="linked_agency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçilmedi</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency['id'] }}" @selected($user['linked_agency_id'] == $agency['id'])>{{ $agency['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="edit_user_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select id="edit_user_status" name="status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($user['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('users.show', $user['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif

@if ($user['can_delete'] ?? false)
    <x-ui.card title="Hesabı Pasife Al" class="mt-6">
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">Bu işlem kullanıcıyı soft-delete ile pasife alır.</p>
        <form method="POST" action="{{ route('users.destroy', $user['id']) }}" onsubmit="return confirm('Bu kullanıcı pasife alınsın mı?')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">Pasife Al</x-ui.button>
        </form>
    </x-ui.card>
@endif

@if ($user['can_force_delete'] ?? false)
    <x-ui.card title="Kalıcı Olarak Sil" class="mt-6 border-red-200 dark:border-red-900/50">
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">
            Bu işlem kullanıcıyı veritabanından tamamen siler ve geri alınamaz. Yalnızca Süper Admin yapabilir.
        </p>
        <form
            method="POST"
            action="{{ route('users.force-destroy', $user['id']) }}"
            onsubmit="return confirm('Bu kullanıcı kalıcı olarak silinsin mi? Bu işlem geri alınamaz.')"
        >
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">Kalıcı Olarak Sil</x-ui.button>
        </form>
    </x-ui.card>
@endif
