@extends('layouts.app')

@section('title', $role['display_name'])

@section('breadcrumb')
    <span class="text-gray-500 dark:text-slate-400">Kullanıcı Yönetimi</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('roles.index') }}" class="hover:text-gray-900 dark:hover:text-white">Roller</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $role['display_name'] }}</span>
@endsection

@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <x-user.role-name-cell :role="$role" class="!gap-4" />
            <div class="mt-1">
                <x-user.role-status-badge :status="$role['status']" />
                @if ($role['is_system'])
                    <span class="ml-2 inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-slate-300">Sistem Rolü</span>
                @endif
                @if (! $role['is_deletable'])
                    <span class="ml-2 inline-flex rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-600/10 dark:text-amber-400">Silinemez</span>
                @endif
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($role['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @endif
            <x-ui.button href="{{ route('permissions.index', ['role' => $role['name']]) }}" variant="secondary">Yetkileri Yönet</x-ui.button>
            <x-ui.button href="{{ route('users.index', ['role' => $role['name']]) }}" variant="secondary">Kullanıcıları Gör</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Rol Bilgileri">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Görünen Ad</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $role['display_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Spatie Slug</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $role['name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Guard</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $role['guard_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Açıklama</dt>
                    <dd class="max-w-[260px] text-right text-gray-900 dark:text-white">{{ $role['description'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">İkon / Renk</dt>
                    <dd class="text-right text-gray-900 dark:text-white">
                        <span class="font-mono text-xs">{{ $role['icon'] }}</span>
                        <span class="mx-1 text-gray-400">·</span>
                        <span class="font-mono text-xs">{{ $role['color'] }}</span>
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Oluşturulma</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $role['created_at_formatted'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Son Güncelleme">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Güncelleme Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $role['updated_at_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kullanıcı Sayısı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($role['user_count']) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Yetki Sayısı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ number_format($role['permission_count']) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Silinebilir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $role['is_deletable'] ? 'Evet' : 'Hayır' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Pasife Alınabilir</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $role['can_deactivate'] ? 'Evet' : 'Hayır' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Atanmış Kullanıcılar" class="lg:col-span-2">
            @if (count($role['assigned_users']) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Kullanıcı</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">E-Posta</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                                <th class="pb-2 font-medium text-gray-500 dark:text-slate-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($role['assigned_users'] as $user)
                                <tr>
                                    <td class="py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $user['avatar_color'] }} text-xs font-bold text-white">
                                                {{ $user['avatar_initials'] }}
                                            </div>
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $user['full_name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $user['email'] }}</td>
                                    <td class="py-2.5">
                                        <x-user.status-badge :status="$user['status']" />
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('users.show', $user['id']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Profil</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($role['user_count'] > count($role['assigned_users']))
                    <p class="mt-3 text-xs text-gray-500 dark:text-slate-400">
                        ve {{ number_format($role['user_count'] - count($role['assigned_users'])) }} kullanıcı daha...
                        <a href="{{ route('users.index', ['role' => $role['name']]) }}" class="text-primary-600 hover:underline dark:text-primary-400">Tümünü gör</a>
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Bu role henüz kullanıcı atanmamış.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Atanmış Yetkiler" class="lg:col-span-2">
            <p class="mb-4 text-xs text-gray-500 dark:text-slate-400">
                Spatie Permission uyumlu {{ number_format($role['permission_count']) }} yetki · guard: {{ $role['guard_name'] }}
            </p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($role['permission_groups'] as $module => $permissions)
                    <div class="rounded-lg border border-gray-100 p-3 dark:border-slate-700">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ $module }}</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($permissions as $permission)
                                <span class="inline-flex rounded-md bg-primary-50 px-2 py-0.5 font-mono text-xs text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                                    {{ $permission }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    @include('modules.user.roles.partials.edit-form')
</div>
@endsection
