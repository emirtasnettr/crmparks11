@extends('layouts.app')

@section('title', 'Roller')

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Kullanıcı Yönetimi</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Roller</span>
@endsection

@section('content')
<div x-data="roleManagementPage()" x-on:role-row-action.window="handleRowAction($event.detail)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Roller</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kullanıcı rollerini yönetin.
            </p>
        </div>

        <x-ui.button type="button" @click="activeModal = 'create'">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Rol
        </x-ui.button>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Rol" :value="number_format($summary['total_roles'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Rol" :value="number_format($summary['active_roles'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Toplam Kullanıcı" :value="number_format($summary['total_users'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Toplam Yetki" :value="number_format($summary['total_permissions'])" icon="chart" accent="primary" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('roles.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.input
                    name="search"
                    label="Rol Ara"
                    placeholder="Rol adı veya açıklama"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('roles.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                rol kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Rol Adı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açıklama</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kullanıcı Sayısı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Yetki Sayısı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Oluşturulma Tarihi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($roles as $role)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 sm:px-6">
                                <x-user.role-name-cell :role="$role" />
                            </td>
                            <td class="max-w-[280px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                <p class="line-clamp-2">{{ $role['description'] }}</p>
                                @if ($role['is_system'])
                                    <span class="mt-1 inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-gray-500 dark:bg-slate-700 dark:text-slate-400">Sistem Rolü</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('users.index', ['role' => $role['name']]) }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                    {{ number_format($role['user_count']) }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ number_format($role['permission_count']) }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $role['created_at_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-user.role-status-badge :status="$role['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-user.role-row-actions :role="$role" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun rol bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination
            :total="$total"
            :page="$page"
            :per-page="$perPage"
            :last-page="$lastPage"
        />
    </x-ui.card>

    @include('modules.user.roles.partials.create-modal')
</div>
@endsection
