<div
    class="relative w-full max-w-md flex-1"
    x-data="globalSearch(@js(route('search')))"
    @keydown.escape.window="close()"
>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <input
        type="search"
        x-model="query"
        @input.debounce.300ms="search()"
        @focus="open = query.length >= 2"
        placeholder="İşletme, kurye, acente ara..."
        class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-700/50 dark:text-white dark:placeholder:text-slate-400"
        autocomplete="off"
    />

    <div
        x-show="open"
        x-cloak
        @click.outside="close()"
        x-transition
        class="absolute left-0 right-0 z-50 mt-2 max-h-96 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-slate-600 dark:bg-slate-800"
    >
        <template x-if="loading">
            <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-slate-400">Aranıyor...</div>
        </template>

        <template x-if="!loading && query.length >= 2 && total === 0">
            <div class="px-4 py-6 text-center text-sm text-gray-500 dark:text-slate-400">Sonuç bulunamadı.</div>
        </template>

        <template x-for="group in groups" :key="group.key">
            <div class="border-b border-gray-100 last:border-0 dark:border-slate-700">
                <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400" x-text="group.label"></p>
                <template x-for="item in group.items" :key="item.type + '-' + item.id">
                    <a
                        :href="item.url"
                        class="block px-4 py-2.5 transition-colors hover:bg-gray-50 dark:hover:bg-slate-700/50"
                        @click="close()"
                    >
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.title"></p>
                        <p class="text-xs text-gray-500 dark:text-slate-400" x-text="item.subtitle"></p>
                    </a>
                </template>
            </div>
        </template>
    </div>
</div>
