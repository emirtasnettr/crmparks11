@extends('layouts.app')

@section('title', 'Hareket Geçmişi')


@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Hareket Geçmişi</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            İşletmeler üzerinde yapılan tüm işlemleri görüntüleyin.
        </p>
    </div>

    {{-- Filtreler --}}
    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('businesses.activities.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-ui.select
                    name="business_id"
                    label="İşletme"
                    :selected="$filters['business_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($businesses)->mapWithKeys(fn ($b) => [$b['id'] => $b['name']])->all())"
                />

                <x-ui.select
                    name="user_id"
                    label="Kullanıcı"
                    :selected="$filters['user_id']"
                    :options="array_merge(['all' => 'Tümü'], collect($users)->mapWithKeys(fn ($u) => [$u['id'] => $u['name']])->all())"
                />

                <x-ui.select
                    name="action"
                    label="İşlem Türü"
                    :selected="$filters['action']"
                    :options="array_merge(['all' => 'Tümü'], $actionTypes)"
                />

                <x-ui.select
                    name="date_range"
                    label="Tarih Aralığı"
                    :selected="$filters['date_range']"
                    :options="array_merge(['all' => 'Tümü'], $dateRanges)"
                />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('businesses.activities.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Tablo --}}
    <x-ui.card :padding="false" class="mt-6">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span>
                Kayıt
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kullanıcı</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP Adresi</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Açıklama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($activities as $activity)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-slate-400 sm:px-6">
                                {{ $activity['occurred_at_formatted'] }}
                            </td>
                            <td class="max-w-[200px] px-4 py-3">
                                <p class="line-clamp-2 font-medium text-gray-900 dark:text-white">
                                    {{ $activity['business_name'] }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                <x-business.activity-type-badge :action="$activity['action']">
                                    {{ $activity['action_label'] }}
                                </x-business.activity-type-badge>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                {{ $activity['user_name'] }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-slate-400">
                                {{ $activity['ip_address'] }}
                            </td>
                            <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-slate-400 sm:px-6">
                                <p class="line-clamp-2">{{ $activity['description'] }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                                Filtrelere uygun hareket kaydı bulunamadı.
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
@endsection
