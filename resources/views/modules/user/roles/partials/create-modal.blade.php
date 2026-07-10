        <form method="POST" action="{{ route('roles.store') }}" class="space-y-4 px-6 py-4">
            @csrf

            <x-ui.input type="text" name="display_name" label="Rol Adı *" :value="old('display_name')" placeholder="Örn. Bölge Koordinatörü" />

            <div class="space-y-1.5">
                <label for="role_description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <textarea id="role_description" name="description" rows="3" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" placeholder="Rol açıklaması">{{ old('description') }}</textarea>
            </div>

            <div class="space-y-1.5">
                <label for="role_status" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select id="role_status" name="status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
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