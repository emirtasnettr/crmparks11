<div class="space-y-4 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/40">
    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Başvuru Bildirimleri</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
            Yeni başvuru geldiğinde yalnızca seçtiğiniz kullanıcı ve rollere bildirim gider. Boş bırakırsanız kimseye bildirim düşmez.
        </p>
    </div>

    <div class="space-y-1.5">
        <label for="notify_user_ids" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bildirim kullanıcıları</label>
        <select
            id="notify_user_ids"
            name="notify_user_ids[]"
            multiple
            size="6"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
        >
            @foreach ($notifyUsers as $id => $label)
                <option value="{{ $id }}" @selected(in_array((int) $id, array_map('intval', (array) $selectedUserIds), true))>{{ $label }}</option>
            @endforeach
        </select>
        @error('notify_user_ids')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('notify_user_ids.*')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <p class="text-xs text-gray-500 dark:text-slate-400">Ctrl / Cmd ile birden fazla seçebilirsiniz.</p>
    </div>

    <div class="space-y-1.5">
        <label for="notify_roles" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bildirim rolleri</label>
        <select
            id="notify_roles"
            name="notify_roles[]"
            multiple
            size="5"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
        >
            @foreach ($notifyRoles as $name => $label)
                <option value="{{ $name }}" @selected(in_array((string) $name, array_map('strval', (array) $selectedRoles), true))>{{ $label }}</option>
            @endforeach
        </select>
        @error('notify_roles')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('notify_roles.*')
            <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
</div>
