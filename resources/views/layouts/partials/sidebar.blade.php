@php
    use App\Core\Menu\SidebarMenu;

    $menuItems = SidebarMenu::items();
    $expandedGroups = SidebarMenu::initialExpandedGroups();
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 w-64 transform border-r border-gray-200 bg-white transition-transform duration-200 dark:border-slate-700 dark:bg-slate-800 lg:translate-x-0"
    :class="{ '-translate-x-full': !open, 'translate-x-0': open }"
>
    <div class="flex h-16 items-center border-b border-gray-200 px-6 dark:border-slate-700 {{ $branding['has_logo'] ? '' : 'gap-3' }}">
        @if ($branding['has_logo'])
            <x-app.brand-logo size="sidebar" />
        @else
            <x-app.brand-logo size="md" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $branding['system_name'] }}</p>
                <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ $branding['short_description'] ?: 'Operasyon Yönetimi' }}</p>
            </div>
        @endif
    </div>

    <nav class="scrollbar-thin h-[calc(100vh-4rem)] overflow-y-auto p-4">
        <ul class="space-y-1">
            @foreach ($menuItems as $item)
                @if (! SidebarMenu::canView($item))
                    @continue
                @endif

                @if (($item['type'] ?? 'link') === 'group')
                    @if (! empty($item['disabled']))
                        <li>
                            <div
                                class="flex w-full cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-400 opacity-60 dark:text-slate-500"
                                aria-disabled="true"
                                title="Bu modül şimdilik pasif"
                            >
                                <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span class="flex-1 text-left">{{ $item['label'] }}</span>
                            </div>
                        </li>
                    @else
                    @php
                        $groupActive = SidebarMenu::isActive($item['active'] ?? []);
                        $visibleChildren = collect($item['children'] ?? [])->filter(fn (array $child) => SidebarMenu::canView($child));
                    @endphp

                    @if ($visibleChildren->isNotEmpty())
                        <li x-data="{ open: expanded['{{ $item['key'] }}'] ?? false }">
                            <button
                                type="button"
                                @click="open = !open; expanded['{{ $item['key'] }}'] = open"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $groupActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700' }}"
                            >
                                <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span class="flex-1 text-left">{{ $item['label'] }}</span>
                                <svg
                                    class="h-4 w-4 shrink-0 transition-transform"
                                    :class="open ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <ul x-show="open" x-collapse class="mt-1 space-y-0.5 border-l border-gray-200 pl-3 ml-5 dark:border-slate-600">
                                @foreach ($visibleChildren as $child)
                                    @php $childActive = SidebarMenu::isActive($child['active'] ?? [$child['route']]); @endphp
                                    <li>
                                        <a
                                            href="{{ route($child['route']) }}"
                                            class="block rounded-lg px-3 py-2 text-sm transition-colors {{ $childActive ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-600/10 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' }}"
                                        >
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @endif
                @else
                    @php $linkActive = SidebarMenu::isActive($item['active'] ?? [$item['route']]); @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $linkActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700' }}"
                        >
                            <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>
</aside>

<div
    x-show="open"
    x-transition.opacity
    @click="open = false"
    class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
    x-cloak
></div>
