<div
    class="relative"
    x-data="globalSearch(@js(route('search')))"
    @keydown.escape.window="closePanel()"
>
    <button
        type="button"
        @click="togglePanel()"
        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
        title="Ara"
        aria-label="Ara"
        :aria-expanded="panelOpen.toString()"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
    </button>

    <div
        x-show="panelOpen"
        x-cloak
        @click.outside="closePanel()"
        x-transition
        class="absolute right-0 top-full z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-xl border border-gray-200 bg-white p-3 shadow-lg"
    >
        <div class="relative">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="search"
                x-ref="input"
                x-model="query"
                @input.debounce.300ms="search()"
                placeholder="İşletme, kurye, acente ara..."
                class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-500 focus:border-primary-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                autocomplete="off"
            />
        </div>

        <div
            x-show="resultsOpen"
            x-cloak
            class="mt-2 max-h-80 overflow-y-auto rounded-lg border border-gray-100"
        >
            <template x-if="loading">
                <div class="px-4 py-6 text-center text-sm text-gray-500">Aranıyor...</div>
            </template>

            <template x-if="!loading && query.trim().length >= 2 && total === 0">
                <div class="px-4 py-6 text-center text-sm text-gray-500">Sonuç bulunamadı.</div>
            </template>

            <template x-for="group in groups" :key="group.key">
                <div class="border-b border-gray-100 last:border-0">
                    <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="group.label"></p>
                    <template x-for="item in group.items" :key="item.type + '-' + item.id">
                        <a
                            :href="item.url"
                            class="block px-4 py-2.5 transition-colors hover:bg-gray-50"
                            @click="closePanel()"
                        >
                            <p class="text-sm font-medium text-gray-900" x-text="item.title"></p>
                            <p class="text-xs text-gray-500" x-text="item.subtitle"></p>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
