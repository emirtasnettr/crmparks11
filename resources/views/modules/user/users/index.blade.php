@extends('layouts.app')

@section('title', 'Kullanıcılar')


@section('content')
<div
    x-data="userManagementPage(@js([
        'routes' => [
            'resetPassword' => url('/kullanici-yonetimi/kullanicilar'),
        ],
        'openCreate' => $errors->hasAny(['first_name', 'last_name', 'phone', 'email', 'password', 'roles', 'linked_business_id', 'linked_courier_id', 'linked_agency_id', 'status']),
    ]))"
    @user-row-action.window="handleRowAction($event.detail)"
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kullanıcılar</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistemdeki tüm kullanıcı hesaplarını yönetin.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <x-ui.button type="button" @click="activeModal = 'create'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Yeni Kullanıcı
            </x-ui.button>
            <x-ui.export-button :href="route('users.export', request()->query())" />
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Kullanıcı" :value="number_format($summary['total'])" icon="users" accent="blue" />
        <x-ui.finance-stat-card title="Aktif Kullanıcı" :value="number_format($summary['active'])" icon="users" accent="success" />
        <x-ui.finance-stat-card title="Pasif Kullanıcı" :value="number_format($summary['inactive'])" icon="users" accent="danger" />
        <x-ui.finance-stat-card title="Bugün Giriş Yapan" :value="number_format($summary['logged_in_today'])" icon="clock" accent="primary" />
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('users.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.input
                    name="search"
                    label="Kullanıcı Ara"
                    placeholder="Ad Soyad, E-Posta, Telefon"
                    :value="$filters['search']"
                />

                <x-ui.select
                    name="role"
                    label="Rol"
                    :selected="$filters['role']"
                    :options="array_merge(['all' => 'Tümü'], $roles)"
                />

                <x-ui.select
                    name="status"
                    label="Durum"
                    :selected="$filters['status']"
                    :options="array_merge(['all' => 'Tümü'], $statuses)"
                />

                <x-ui.select
                    name="last_login"
                    label="Son Giriş Tarihi"
                    :selected="$filters['last_login']"
                    :options="$lastLoginFilters"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('users.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false" class="mt-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                kullanıcı kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Profil Fotoğrafı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Ad Soyad</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">E-Posta</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Rol</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Bağlı Birim</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Son Giriş</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($users as $user)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $user['avatar_color'] }} text-xs font-bold text-white">
                                    {{ $user['avatar_initials'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('users.show', $user['id']) }}" class="font-medium text-gray-900 hover:text-primary-600 dark:text-white dark:hover:text-primary-400">
                                    {{ $user['full_name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $user['email'] }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $user['phone'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-user.role-badges :labels="$user['role_labels']" />
                            </td>
                            <td class="max-w-[200px] px-4 py-3 text-gray-600 dark:text-slate-400">
                                <p class="line-clamp-2">{{ $user['linked_unit'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $user['last_login_formatted'] }}
                            </td>
                            <td class="px-4 py-3">
                                <x-user.status-badge :status="$user['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-user.row-actions :user="$user" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun kullanıcı bulunamadı.
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

    @include('modules.user.users.partials.create-modal')

    <form x-ref="resetPasswordForm" method="POST" class="hidden">
        @csrf
    </form>
</div>
@endsection
