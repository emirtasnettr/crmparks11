<aside class="w-full shrink-0 xl:w-72">
    <x-ui.card :padding="false">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-slate-700">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">⚙️ Sistem Ayarları</p>
        </div>
        <nav class="p-2">
            @foreach ($categories as $key => $category)
                <a
                    href="{{ route('settings.index', ['section' => $key]) }}"
                    @class([
                        'mb-0.5 flex items-center rounded-lg px-3 py-2.5 text-sm transition-colors',
                        'bg-primary-50 font-medium text-primary-700 dark:bg-primary-900/20 dark:text-primary-300' => $section === $key,
                        'text-gray-600 hover:bg-gray-50 dark:text-slate-400 dark:hover:bg-slate-800/50' => $section !== $key,
                    ])
                >
                    {{ $category['label'] }}
                </a>
            @endforeach
        </nav>
    </x-ui.card>
</aside>
