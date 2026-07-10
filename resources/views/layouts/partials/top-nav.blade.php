@php
    use App\Core\Menu\SidebarMenu;

    $menuItems = collect(SidebarMenu::items())
        ->filter(fn (array $item) => SidebarMenu::canView($item))
        ->values();
@endphp

<header class="sticky top-0 z-40 overflow-visible border-b border-gray-200 bg-white/95 backdrop-blur-sm">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-700"
            aria-label="Menüyü aç"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2.5">
            @if ($branding['has_logo'])
                <x-app.brand-logo size="sm" />
            @else
                <x-app.brand-logo size="md" />
                <span class="hidden max-w-[9rem] truncate text-sm font-semibold text-gray-900 dark:text-white sm:inline">
                    {{ $branding['system_name'] }}
                </span>
            @endif
        </a>

        <nav class="hidden min-w-0 flex-1 items-center gap-0.5 lg:flex" aria-label="Ana menü">
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

                    <div
                        class="relative shrink-0"
                        x-data="{ open: false }"
                        @keydown.escape.window="open = false"
                        @click.outside="open = false"
                    >
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-colors {{ $groupActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
                            :aria-expanded="open.toString()"
                        >
                            <x-ui.icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                            <span>{{ $item['label'] }}</span>
                            <svg
                                class="h-3.5 w-3.5 shrink-0 opacity-70 transition-transform"
                                :class="open ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition
                            class="absolute left-0 top-full z-[60] mt-1 min-w-[13rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                        >
                            @foreach ($visibleChildren as $child)
                                @php $childActive = SidebarMenu::isActive($child['active'] ?? [$child['route']]); @endphp
                                <a
                                    href="{{ route($child['route']) }}"
                                    @click="open = false"
                                    class="block px-3 py-2 text-sm transition-colors {{ $childActive ? 'bg-primary-50 font-medium text-primary-700' : 'text-gray-700 hover:bg-gray-50' }}"
                                >
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php $linkActive = SidebarMenu::isActive($item['active'] ?? [$item['route']]); @endphp
                    <a
                        href="{{ route($item['route']) }}"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-colors {{ $linkActive ? 'bg-primary-50 text-primary-700' : 'text-gray-700 hover:bg-gray-100' }}"
                    >
                        <x-ui.icon :name="$item['icon']" class="h-4 w-4 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
            @include('layouts.partials.global-search')

            @include('layouts.partials.notification-bell')

            <div x-data="{ open: false }" class="relative">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center rounded-full p-0.5 hover:bg-gray-100"
                    title="{{ auth()->user()->name }}"
                    aria-label="{{ auth()->user()->name }}"
                >
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">
                        {{ auth()->user()->initials() }}
                    </div>
                </button>

                <div
                    x-show="open"
                    @click.outside="open = false"
                    x-transition
                    x-cloak
                    class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-800"
                >
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Çıkış Yap
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- Mobile drawer --}}
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
        class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col border-r border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
        x-show="mobileOpen"
        x-transition:enter="transition transform ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <div class="flex h-16 items-center justify-between border-b border-gray-200 px-4 dark:border-slate-700">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2" @click="mobileOpen = false">
                <x-app.brand-logo size="md" />
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $branding['system_name'] }}</span>
            </a>
            <button
                type="button"
                @click="mobileOpen = false"
                class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700"
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
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $groupActive ? 'bg-primary-50 text-primary-700 dark:bg-primary-600/10 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-700' }}"
                            >
                                <x-ui.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                <span class="flex-1 text-left">{{ $item['label'] }}</span>
                                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <ul x-show="open" x-collapse class="mt-1 space-y-0.5 border-l border-gray-200 pl-3 ml-5 dark:border-slate-600">
                                @foreach ($visibleChildren as $child)
                                    @php $childActive = SidebarMenu::isActive($child['active'] ?? [$child['route']]); @endphp
                                    <li>
                                        <a
                                            href="{{ route($child['route']) }}"
                                            @click="mobileOpen = false"
                                            class="block rounded-lg px-3 py-2 text-sm transition-colors {{ $childActive ? 'bg-primary-50 font-medium text-primary-700 dark:bg-primary-600/10 dark:text-primary-400' : 'text-gray-600 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-white' }}"
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
</div>
