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
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">E-posta ve telefon benzersiz olmalıdır. Roller çoklu atanabilir.</p>
            </div>
            <button type="button" @click="closeModals()" class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form @submit.prevent="saveUser()" class="space-y-4 px-6 py-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Ad *</label>
                    <input type="text" x-model="form.first_name" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.first_name ? 'border-red-300' : ''" />
                    <p x-show="errors.first_name" x-cloak class="text-sm text-red-600" x-text="errors.first_name"></p>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Soyad *</label>
                    <input type="text" x-model="form.last_name" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.last_name ? 'border-red-300' : ''" />
                    <p x-show="errors.last_name" x-cloak class="text-sm text-red-600" x-text="errors.last_name"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Telefon *</label>
                    <input type="tel" x-model="form.phone" placeholder="05XX XXX XX XX" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.phone ? 'border-red-300' : ''" />
                    <p x-show="errors.phone" x-cloak class="text-sm text-red-600" x-text="errors.phone"></p>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">E-Posta *</label>
                    <input type="email" x-model="form.email" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.email ? 'border-red-300' : ''" />
                    <p x-show="errors.email" x-cloak class="text-sm text-red-600" x-text="errors.email"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Şifre *</label>
                    <input type="password" x-model="form.password" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.password ? 'border-red-300' : ''" />
                    <p x-show="errors.password" x-cloak class="text-sm text-red-600" x-text="errors.password"></p>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Şifre Tekrar *</label>
                    <input type="password" x-model="form.password_confirmation" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white" :class="errors.password_confirmation ? 'border-red-300' : ''" />
                    <p x-show="errors.password_confirmation" x-cloak class="text-sm text-red-600" x-text="errors.password_confirmation"></p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Rol * <span class="font-normal text-gray-500">(birden fazla seçilebilir)</span></label>
                <div class="grid grid-cols-1 gap-2 rounded-lg border border-gray-200 p-3 sm:grid-cols-2 dark:border-slate-600">
                    @foreach ($roles as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" value="{{ $key }}" x-model="form.roles" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800" />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p x-show="errors.roles" x-cloak class="text-sm text-red-600" x-text="errors.roles"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı İşletme</label>
                    <select x-model="form.linked_business_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçiniz (opsiyonel)</option>
                        @foreach ($businesses as $business)
                            <option value="{{ $business['id'] }}">{{ $business['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Kurye</label>
                    <select x-model="form.linked_courier_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçiniz (opsiyonel)</option>
                        @foreach ($couriers as $courier)
                            <option value="{{ $courier['id'] }}">{{ $courier['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Bağlı Acente</label>
                    <select x-model="form.linked_agency_id" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        <option value="">Seçiniz (opsiyonel)</option>
                        @foreach ($agencies as $agency)
                            <option value="{{ $agency['id'] }}">{{ $agency['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Profil Fotoğrafı</label>
                    <input type="file" accept="image/*" class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 dark:text-slate-400 dark:file:bg-slate-700 dark:file:text-slate-300" />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                    <select x-model="form.status" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div x-show="saved" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300">
                Kullanıcı kaydı oluşturuldu. Gerçek entegrasyonda Spatie Permission rolleri atanacak ve soft delete desteklenecektir.
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closeModals()">İptal</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </form>
    </div>
</div>
