@extends('layouts.app')

@section('title', 'İşletme Pipeline')


@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">İşletme Pipeline</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İşletmelerin durum bazlı dağılımı ve satış hunisi.</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($distribution as $item)
        <x-ui.finance-stat-card
            :title="$item['label']"
            :value="number_format($item['count'])"
            :subtitle="'%'.number_format($item['percentage'], $item['percentage'] == floor($item['percentage']) ? 0 : 1, ',', '.')"
            icon="building"
            accent="primary"
        />
    @endforeach
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('reports.business-pipeline') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-3 sm:p-6">
        <x-ui.select name="status" label="Durum" :selected="(string) $filters['status']" :options="$statusOptions" />
        <div class="flex items-end gap-2 sm:col-span-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('reports.business-pipeline') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Lokasyon</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Kayıt Tarihi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <a href="{{ $row['url'] }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ $row['name'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['location'] }}</td>
                        <td class="px-4 py-3"><x-business.status-badge :status="$row['status']" /></td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300 sm:px-6">{{ $row['created_at_formatted'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
