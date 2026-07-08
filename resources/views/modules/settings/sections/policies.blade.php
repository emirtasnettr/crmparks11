<div class="space-y-6">
    @foreach ($policies as $key => $policy)
        <x-ui.card>
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $policy['label'] }}</h2>
                    @if (! empty($policy['public_url']))
                        <a href="{{ $policy['public_url'] }}" target="_blank" class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                            {{ $policy['public_url'] }}
                        </a>
                    @endif
                </div>
                <p class="text-xs text-gray-500 dark:text-slate-400">
                    Son güncelleme: {{ $policy['updated_at_formatted'] }}
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Sayfa Başlığı</label>
                    <input
                        type="text"
                        :name="'{{ $key }}[title]'"
                        x-model="policies.{{ $key }}.title"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">İçerik</label>
                    <div
                        x-ref="editor_{{ $key }}"
                        class="min-h-[220px] rounded-xl border border-gray-300 bg-white dark:border-slate-600 dark:bg-slate-900"
                    ></div>
                    <input type="hidden" x-ref="input_{{ $key }}" :name="'{{ $key }}[content]'" :value="policies.{{ $key }}.content">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">Kalın, liste, bağlantı ve başlık gibi HTML biçimlendirmeleri desteklenir.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Meta Title</label>
                        <input
                            type="text"
                            :name="'{{ $key }}[meta_title]'"
                            x-model="policies.{{ $key }}.meta_title"
                            maxlength="70"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Meta Description</label>
                        <textarea
                            :name="'{{ $key }}[meta_description]'"
                            x-model="policies.{{ $key }}.meta_description"
                            maxlength="160"
                            rows="2"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        ></textarea>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endforeach
</div>
