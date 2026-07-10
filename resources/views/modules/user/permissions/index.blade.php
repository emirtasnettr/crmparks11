@extends('layouts.app')

@section('title', 'Yetkiler')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Kullanıcı Yönetimi</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Yetkiler</span>
@endsection

@section('content')
<div
    x-data="permissionManagementPage(@js($rolesPayload), @js($selectedRole), @js($summary), @js($saveUrl))"
    class="flex flex-col gap-6 xl:flex-row"
>
    <aside class="w-full shrink-0 xl:w-64">
        <x-ui.card title="Rol Seç">
            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Rol</label>
                <select
                    x-model="selectedRole"
                    @change="changeRole()"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                >
                    @foreach ($roles as $slug => $label)
                        <option value="{{ $slug }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-xs dark:border-slate-700 dark:bg-slate-800/50">
                    <p class="font-medium text-gray-900 dark:text-white" x-text="rolesPayload[selectedRole]?.label"></p>
                    <p class="mt-1 text-gray-500 dark:text-slate-400" x-show="isLocked" x-cloak>
                        Süper Admin yetkileri değiştirilemez.
                    </p>
                    <p class="mt-1 text-gray-500 dark:text-slate-400" x-show="dirty" x-cloak>
                        Kaydedilmemiş değişiklikler var.
                    </p>
                </div>
            </div>
        </x-ui.card>
    </aside>

    <div class="min-w-0 flex-1">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yetkiler</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Sistem yetkilerini rol bazlı yönetin.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button type="button" @click="save()" ::disabled="isLocked || saving">
                    <span x-show="!saving">Kaydet</span>
                    <span x-show="saving" x-cloak>Kaydediliyor...</span>
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" @click="selectAll()" ::disabled="isLocked">
                    Tümünü Seç
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" @click="deselectAll()" ::disabled="isLocked">
                    Tümünü Kaldır
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" @click="resetToDefault()" ::disabled="isLocked">
                    Varsayılana Döndür
                </x-ui.button>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.finance-stat-card title="Toplam Rol" :value="number_format($summary['total_roles'])" icon="courier" accent="blue" />
            <x-ui.finance-stat-card title="Toplam Yetki" :value="number_format($summary['total_permissions'])" icon="chart" accent="primary" />
            <div class="rounded-xl border border-gray-200 border-l-4 border-l-emerald-500 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Aktif Yetki</p>
                        <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white" x-text="activeCountFormatted"></p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-600/10 dark:text-emerald-400">
                        <x-ui.icon name="courier" class="h-5 w-5" />
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 border-l-4 border-l-red-500 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Pasif Yetki</p>
                        <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white" x-text="inactiveCountFormatted"></p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-600/10 dark:text-red-400">
                        <x-ui.icon name="courier" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        </div>

        <x-ui.card :padding="false" class="mb-6">
            <div class="grid grid-cols-1 gap-4 p-4 sm:p-6 md:grid-cols-2">
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Modül Ara</label>
                    <input
                        type="search"
                        x-model="moduleSearch"
                        placeholder="Modül adı"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    />
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Yetki Ara</label>
                    <input
                        type="search"
                        x-model="permissionSearch"
                        placeholder="Yetki adı veya slug"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                    />
                </div>
            </div>
        </x-ui.card>

        <div
            x-show="saved"
            x-cloak
            class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-900/20 dark:text-emerald-300"
            x-text="saveMessage || 'Yetki değişiklikleri kaydedildi.'"
        ></div>

        <div
            x-show="saveError"
            x-cloak
            class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800/50 dark:bg-red-900/20 dark:text-red-300"
            x-text="saveError"
        ></div>

        <x-ui.card :padding="false">
            <div class="max-h-[calc(100vh-20rem)] overflow-auto">
                <table class="w-full min-w-[1100px] border-collapse text-left text-sm">
                    <thead class="sticky top-0 z-30 bg-gray-50 shadow-sm dark:bg-slate-800">
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="sticky left-0 z-40 min-w-[220px] bg-gray-50 px-4 py-3 font-medium text-gray-500 dark:bg-slate-800 dark:text-slate-400 sm:px-6">
                                Modül
                            </th>
                            @foreach ($actionLabels as $key => $label)
                                <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-slate-400">
                                    {{ $label }}
                                </th>
                            @endforeach
                            <th class="px-3 py-3 text-center font-medium text-gray-500 dark:text-slate-400">
                                Tüm Yetkileri Seç
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        <template x-for="row in filteredMatrix" :key="row.key">
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                                <td class="sticky left-0 z-20 bg-white px-4 py-3 font-medium text-gray-900 dark:bg-slate-900 dark:text-white sm:px-6">
                                    <span x-text="row.label"></span>
                                </td>

                                @foreach (array_keys($actionLabels) as $actionKey)
                                    <td class="px-3 py-3 text-center">
                                        <template x-if="row.actions['{{ $actionKey }}']?.applicable">
                                            <input
                                                type="checkbox"
                                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800"
                                                :checked="row.actions['{{ $actionKey }}'].granted"
                                                @change="toggleCell(row.key, '{{ $actionKey }}', $event.target.checked)"
                                                :disabled="isLocked"
                                                :title="row.actions['{{ $actionKey }}'].primary_slug"
                                            />
                                        </template>
                                        <template x-if="!row.actions['{{ $actionKey }}']?.applicable">
                                            <span class="text-gray-300 dark:text-slate-600">—</span>
                                        </template>
                                    </td>
                                @endforeach

                                <td class="px-3 py-3 text-center">
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800"
                                        :checked="isRowFullyGranted(row)"
                                        @change="toggleRow(row.key, $event.target.checked)"
                                        :disabled="isLocked || !rowHasApplicable(row)"
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 text-xs text-gray-500 dark:border-slate-700 dark:text-slate-400 sm:px-6">
                <span x-text="filteredMatrix.length"></span> modül gösteriliyor ·
                <span x-text="activeCount"></span> aktif yetki ·
                Guard: <code class="font-mono">web</code>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
