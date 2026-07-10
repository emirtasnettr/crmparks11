@extends('layouts.app')

@section('title', 'Bildirimler')


@section('content')
<div>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Bildirimler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sistem bildirimlerinizi görüntüleyin ve yönetin.
            </p>
        </div>

        @can('notification.update')
            @if ($summary['unread'] > 0)
                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Tümünü Okundu İşaretle</x-ui.button>
                </form>
            @endif
        @endcan
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.finance-stat-card title="Toplam Bildirim" :value="number_format($summary['total'])" icon="chart" accent="primary" />
        <x-ui.finance-stat-card title="Okunmamış" :value="number_format($summary['unread'])" icon="earning" accent="warning" />
        <x-ui.finance-stat-card title="Okunmuş" :value="number_format($summary['read'])" icon="courier" accent="success" />
        <x-ui.finance-stat-card title="Bugün" :value="number_format($summary['today'])" icon="chart" accent="blue" />
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('notifications.index') }}" class="p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-ui.select name="status" label="Durum" :selected="$filters['status']"
                    :options="$statuses" />

                <x-ui.select name="type" label="Bildirim Türü" :selected="$filters['type']"
                    :options="array_merge(['all' => 'Tümü'], $types)" />

                <x-ui.select name="date_range" label="Tarih Aralığı" :selected="$filters['date_range']"
                    :options="$dateRanges" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button type="submit">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('notifications.index') }}" variant="secondary">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="border-b border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
            <p class="text-sm font-medium text-gray-900 dark:text-white">
                <span class="text-lg font-bold">{{ number_format($total) }}</span> bildirim listeleniyor
            </p>
        </div>

        <div class="divide-y divide-gray-200 dark:divide-slate-700">
            @forelse ($notifications as $notification)
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between sm:px-6 {{ $notification['is_read'] ? '' : 'bg-primary-50/40 dark:bg-primary-900/10' }}">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            @if (! $notification['is_read'])
                                <span class="inline-flex h-2 w-2 rounded-full bg-primary-500"></span>
                            @endif
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $notification['title'] }}</p>
                            <x-ui.badge variant="secondary">{{ $notification['type_label'] }}</x-ui.badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">{{ $notification['message'] }}</p>
                        <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">
                            {{ $notification['module_label'] }} · {{ $notification['created_at_formatted'] }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($notification['action_url'])
                            <x-ui.button href="{{ $notification['action_url'] }}" variant="secondary" size="sm">Görüntüle</x-ui.button>
                        @endif

                        @can('notification.update')
                            @if (! $notification['is_read'])
                                <form method="POST" action="{{ route('notifications.mark-read', $notification['id']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="secondary" size="sm">Okundu</x-ui.button>
                                </form>
                            @endif
                        @endcan

                        @can('notification.delete')
                            <form method="POST" action="{{ route('notifications.destroy', $notification['id']) }}" onsubmit="return confirm('Bu bildirim silinsin mi?')">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="danger" size="sm">Sil</x-ui.button>
                            </form>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                    Henüz bildirim bulunmuyor.
                </div>
            @endforelse
        </div>

        @if ($lastPage > 1)
            <div class="border-t border-gray-200 px-4 py-4 dark:border-slate-700 sm:px-6">
                <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
