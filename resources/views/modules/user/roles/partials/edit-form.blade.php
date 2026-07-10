@if ($role['can_update'] ?? false)
    <x-ui.card title="Rolü Düzenle" id="edit" class="mt-6">
        <form method="POST" action="{{ route('roles.update', $role['id']) }}" class="space-y-4">
            @csrf
            @method('PUT')

            @if ($role['is_system'])
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800/50 dark:bg-amber-900/20 dark:text-amber-200">
                    Sistem rolünde yalnızca açıklama ve durum güncellenebilir.
                </p>
                <input type="hidden" name="display_name" value="{{ $role['display_name'] }}">
                <p class="text-sm text-gray-700 dark:text-slate-300"><span class="font-medium">Rol Adı:</span> {{ $role['display_name'] }}</p>
            @else
                <x-ui.input type="text" name="display_name" label="Rol Adı *" :value="$role['display_name']" />
            @endif

            <div class="space-y-1.5">
                <label for="edit_role_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="edit_role_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">{{ $role['description'] }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="edit_role_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select id="edit_role_status" name="status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected($role['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <x-ui.button href="{{ route('roles.show', $role['id']) }}" variant="secondary">İptal</x-ui.button>
                <x-ui.button type="submit">Güncelle</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif

@if ($role['can_delete'] ?? false)
    <x-ui.card title="Rolü Sil" class="mt-6">
        <p class="mb-4 text-sm text-gray-600 dark:text-slate-300">Bu rol kalıcı olarak silinir. Atanmış kullanıcı varsa silinemez.</p>
        <form method="POST" action="{{ route('roles.destroy', $role['id']) }}" onsubmit="return confirm('Bu rol silinsin mi?')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">Rolü Sil</x-ui.button>
        </form>
    </x-ui.card>
@endif
