@extends('layouts.app')

@section('title', 'Aktivite Kayıtları')


@section('content')
<div x-data="userActivityLogPage(@js($logsForModal))" @open-user-activity-detail.window="openDetail($event)">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Aktivite Kayıtları</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistemdeki tüm kullanıcı aktivitelerini görüntüleyin.
            </p>
            <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                Bu kayıtlar salt okunurdur. Denetim amaçlıdır; düzenlenemez veya silinemez.
            </p>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Toplam Aktivite" :value="number_format($summary['total'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Bugünkü Aktivite" :value="number_format($summary['today'])" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Başarılı Giriş" :value="number_format($summary['successful_logins'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Başarısız Giriş" :value="number_format($summary['failed_logins'])" icon="courier" accent="danger" />
        <x-ui.finance-stat-card title="Şifre Değişiklikleri" :value="number_format($summary['password_changes'])" icon="chart" accent="violet" />
        <x-ui.finance-stat-card title="Yetki Değişiklikleri" :value="number_format($summary['permission_changes'])" icon="chart" accent="warning" />
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('users.activity-log.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <x-ui.select name="user_id" label="Kullanıcı" :selected="$filters['user_id']"
                    :options="filter_select_options(collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())" />

                <x-ui.select name="role" label="Rol" :selected="$filters['role']"
                    :options="filter_select_options($roles)" />

                <x-ui.select name="activity_type" label="Aktivite Türü" :selected="$filters['activity_type']"
                    :options="filter_select_options($activityTypes)" />

                <x-ui.select name="module" label="Modül" :selected="$filters['module']"
                    :options="filter_select_options($modules)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />

                <x-ui.input type="text" name="ip_address" label="IP Adresi" :value="$filters['ip_address']" placeholder="185.24.10" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('users.activity-log.index') }}" variant="secondary">Temizle</x-ui.button>
                <x-ui.export-button :href="route('users.activity-log.export', request()->query())" />
                <x-ui.export-button :href="route('users.activity-log.export-pdf', request()->query())" label="PDF'e Aktar" />
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> aktivite kaydı listeleniyor
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1300px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kullanıcı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Rol</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Modül</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Aktivite</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP Adresi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tarayıcı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($logs as $log)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white sm:px-6">{{ $log['date_formatted'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600 dark:text-slate-300">{{ $log['time_formatted'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-900 dark:text-white">{{ $log['user_name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $log['role_label'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $log['module_label'] }}</td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $log['activity_type_label'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">{{ $log['ip_address'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-300">{{ $log['browser'] }}</td>
                            <td class="px-4 py-3">
                                <x-user.activity-status-badge :status="$log['status']" />
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <x-user.activity-row-actions :log="$log" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun aktivite kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>

    @include('modules.user.activity-log.partials.detail-modal')
</div>
@endsection
