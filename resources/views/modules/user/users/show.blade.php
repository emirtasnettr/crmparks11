@extends('layouts.app')

@section('title', $user['full_name'])


@section('content')
<div class="max-w-6xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full {{ $user['avatar_color'] }} text-lg font-bold text-white">
                {{ $user['avatar_initials'] }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $user['full_name'] }}</h1>
                    <x-user.status-badge :status="$user['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $user['email'] }} · {{ $user['phone'] }}</p>
                <div class="mt-2">
                    <x-user.role-badges :labels="$user['role_labels']" />
                </div>
            </div>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            @if ($user['can_update'] ?? false)
                <x-ui.button href="#edit">Düzenle</x-ui.button>
            @elseif (($user['is_courier_account'] ?? false) && ($user['courier_profile_url'] ?? null))
                <x-ui.button href="{{ $user['courier_profile_url'] }}" variant="secondary">Kurye Kartına Git</x-ui.button>
            @endif
            <x-ui.button href="{{ route('permissions.index') }}" variant="secondary">Rolleri Yönet</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card title="Genel Bilgiler">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $user['full_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">E-Posta</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $user['email'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $user['phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kullanıcı Tipi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $user['user_type_label'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Bağlı Birim</dt>
                    <dd class="max-w-[240px] text-right font-medium text-gray-900 dark:text-white">{{ $user['linked_unit'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kayıt Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $user['created_at_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">2FA Durumu</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        @if ($user['two_factor_enabled'])
                            Aktif ({{ $user['two_factor_method'] === 'app' ? 'Authenticator' : 'SMS' }})
                        @else
                            <span class="text-gray-400 dark:text-slate-500">Kapalı</span>
                        @endif
                    </dd>
                </div>
                @if ($user['deleted_at'])
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-slate-400">Soft Delete</dt>
                        <dd class="font-medium text-red-600 dark:text-red-400">{{ \Carbon\Carbon::parse($user['deleted_at'])->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card title="Rol Bilgileri">
            <div class="space-y-4">
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Atanmış Roller</p>
                    <x-user.role-badges :labels="$user['role_labels']" />
                </div>
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Spatie Role Slug</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach ($user['roles'] as $role)
                            <code class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700 dark:bg-slate-700 dark:text-slate-300">{{ $role }}</code>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Son Girişler">
            @if (count($user['recent_logins']) > 0)
                <div class="space-y-3">
                    @foreach ($user['recent_logins'] as $login)
                        <div class="flex items-start justify-between gap-4 rounded-lg border border-gray-100 px-3 py-2 dark:border-slate-700">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $login['logged_in_at'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $login['device'] }}</p>
                            </div>
                            <div class="text-right text-xs text-gray-500 dark:text-slate-400">
                                <p>{{ $login['ip'] }}</p>
                                <p>{{ $login['location'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Henüz giriş kaydı bulunmuyor.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Yetkiler">
            <p class="mb-3 text-xs text-gray-500 dark:text-slate-400">Spatie Permission uyumlu izin listesi (role bazlı türetilmiş)</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach ($user['permissions'] as $permission)
                    <span class="inline-flex rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                        {{ $permission }}
                    </span>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Oturum Geçmişi" class="lg:col-span-2">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Cihaz</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">IP</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Son Aktivite</th>
                            <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @foreach ($user['sessions'] as $session)
                            <tr>
                                <td class="py-2.5 text-gray-900 dark:text-white">{{ $session['device'] }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $session['ip'] }}</td>
                                <td class="py-2.5 text-gray-600 dark:text-slate-400">{{ $session['last_active'] }}</td>
                                <td class="py-2.5">
                                    @if ($session['current'])
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400">Aktif Oturum</span>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-slate-500">Sonlandırılmış</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card title="İşlem Geçmişi" class="lg:col-span-2">
            <div class="space-y-3">
                @foreach ($user['activity_log'] as $activity)
                    <div class="flex flex-col gap-1 border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['action'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $activity['description'] }}</p>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-slate-400">
                            <span>{{ $activity['performed_at'] }}</span>
                            <span class="mx-1">·</span>
                            <span>{{ $activity['ip'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    </div>

    @include('modules.user.users.partials.edit-form')
</div>
@endsection
