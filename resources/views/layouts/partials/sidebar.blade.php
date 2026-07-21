@php
    use App\Core\Menu\SidebarMenu;

    $menuItems = collect(SidebarMenu::items())
        ->filter(fn (array $item) => SidebarMenu::canView($item))
        ->values();
@endphp

{{-- Desktop sidebar --}}
<aside
    class="app-sidebar sticky top-0 z-30 h-screen min-w-0 shrink-0 flex-col border-r border-gray-200 bg-white"
    aria-label="Ana menü"
>
    <div class="flex h-16 w-64 shrink-0 items-center justify-between gap-2 border-b border-gray-200 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($branding['has_logo'])
                <span
                    class="app-sidebar-brand-logo"
                    role="img"
                    aria-label="{{ $branding['system_name'] }}"
                ></span>
            @else
                <x-app.brand-logo size="md" surface="light" />
                <span class="truncate text-sm font-semibold text-gray-900">
                    {{ $branding['system_name'] }}
                </span>
            @endif
        </a>

        <button
            type="button"
            @click.prevent="setSidebarCollapsed(true)"
            class="shrink-0 rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Menüyü kapat"
            title="Menüyü kapat"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden p-3">
        <ul class="space-y-1">
            @foreach ($menuItems as $item)
                @if (($item['type'] ?? 'link') === 'group')
                    @if (! empty($item['disabled']))
                        @continue
                    @endif

                    @php
                        $visibleChildren = collect($item['children'] ?? [])
                            ->filter(fn (array $child) => SidebarMenu::canView($child))
                            ->values();
                        $groupActive = SidebarMenu::isActive($item['active'] ?? []);
                    @endphp

                    @if ($visibleChildren->isEmpty())
                        @continue
                    @endif

                    <li x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $groupActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            <span class="flex-1 truncate text-left">{{ $item['label'] }}</span>
                            <svg
                                class="h-4 w-4 shrink-0 {{ $groupActive ? 'rotate-180' : '' }}"
                                :class="open ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <ul
                            class="mt-1 ml-5 space-y-0.5 border-l border-gray-200 pl-3"
                            @if (! $groupActive) x-cloak @endif
                            x-show="open"
                        >
                            @foreach ($visibleChildren as $child)
                                @php $childActive = SidebarMenu::isActive($child['active'] ?? [$child['route']]); @endphp
                                <li>
                                    <a
                                        href="{{ route($child['route']) }}"
                                        class="block truncate rounded-lg px-3 py-2 text-sm transition-colors {{ $childActive ? 'bg-primary-50 font-medium text-primary-700' : 'text-gray-600 hover:bg-gray-100' }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @else
                    @php $linkActive = SidebarMenu::isActive($item['active'] ?? [$item['route']]); @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $linkActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>
</aside>

{{-- Mobile drawer (same menu items) --}}
<div
    x-show="mobileOpen"
    x-cloak
    class="fixed inset-0 z-50 lg:hidden"
>
    <div
        class="absolute inset-0 bg-gray-900/50"
        @click="mobileOpen = false"
        x-transition.opacity
    ></div>

    <aside
        class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col border-r border-gray-200 bg-white shadow-xl"
        x-show="mobileOpen"
        x-transition:enter="transition transform ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2" @click="mobileOpen = false">
                <x-app.brand-logo size="md" surface="light" />
                <span class="truncate text-sm font-semibold text-gray-900">{{ $branding['system_name'] }}</span>
            </a>
            <button
                type="button"
                @click="mobileOpen = false"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                aria-label="Menüyü kapat"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto p-3">
            <ul class="space-y-1">
                @foreach ($menuItems as $item)
                    @if (($item['type'] ?? 'link') === 'group')
                        @if (! empty($item['disabled']))
                            @continue
                        @endif

                        @php
                            $visibleChildren = collect($item['children'] ?? [])
                                ->filter(fn (array $child) => SidebarMenu::canView($child))
                                ->values();
                            $groupActive = SidebarMenu::isActive($item['active'] ?? []);
                        @endphp

                        @if ($visibleChildren->isEmpty())
                            @continue
                        @endif

                        <li x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $groupActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
                            >
                                <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span class="flex-1 text-left">{{ $item['label'] }}</span>
                                <svg
                                    class="h-4 w-4 shrink-0 {{ $groupActive ? 'rotate-180' : '' }}"
                                    :class="open ? 'rotate-180' : ''"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <ul
                                class="mt-1 ml-5 space-y-0.5 border-l border-gray-200 pl-3"
                                @if (! $groupActive) x-cloak @endif
                                x-show="open"
                            >
                                @foreach ($visibleChildren as $child)
                                    @php $childActive = SidebarMenu::isActive($child['active'] ?? [$child['route']]); @endphp
                                    <li>
                                        <a
                                            href="{{ route($child['route']) }}"
                                            @click="mobileOpen = false"
                                            class="block rounded-lg px-3 py-2 text-sm transition-colors {{ $childActive ? 'bg-primary-50 font-medium text-primary-700' : 'text-gray-600 hover:bg-gray-100' }}"
                                        >
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        @php $linkActive = SidebarMenu::isActive($item['active'] ?? [$item['route']]); @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @click="mobileOpen = false"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $linkActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
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
</div>
