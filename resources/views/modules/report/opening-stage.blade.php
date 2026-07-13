@extends('layouts.app')

@section('title', 'Açılış Aşaması')

@push('styles')
<style>
    @keyframes opening-overdue-blink {
        0%, 100% { background-color: rgb(255 241 242); }
        50% { background-color: rgb(254 226 226); }
    }
    @keyframes opening-overdue-blink-dark {
        0%, 100% { background-color: rgb(76 5 25 / 0.25); }
        50% { background-color: rgb(136 19 55 / 0.35); }
    }
    .opening-overdue-blink {
        animation: opening-overdue-blink 2.8s ease-in-out infinite;
    }
    .dark .opening-overdue-blink {
        animation-name: opening-overdue-blink-dark;
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Açılış Aşaması</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Açılış aşamasındaki işletmeler ve geciken açılışlar.</p>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <x-ui.finance-stat-card title="Toplam" :value="number_format($summary['total'])" icon="building" accent="primary" />
    <x-ui.finance-stat-card title="Geciken" :value="number_format($summary['overdue'])" icon="report" accent="danger" />
</div>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Lokasyon</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Açılış Tarihi</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Durum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($rows as $row)
                    <tr @class([
                        'hover:bg-gray-50 dark:hover:bg-slate-800/50',
                        'opening-overdue-blink' => $row['is_opening_overdue'],
                    ])>
                        <td class="px-4 py-3 sm:px-6">
                            <a href="{{ $row['url'] }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">{{ $row['name'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['location'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">
                            {{ $row['completed_courier_count'] }} / {{ $row['planned_courier_count'] }}
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['start_date_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-rose-100 text-rose-800 dark:bg-rose-500/25 dark:text-rose-200' => $row['is_opening_overdue'],
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-200' => ! $row['is_opening_overdue'] && $row['days_until_opening'] !== null && $row['days_until_opening'] <= 1,
                                'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300' => ! $row['is_opening_overdue'] && ($row['days_until_opening'] === null || $row['days_until_opening'] > 1),
                            ])>
                                {{ $row['delay_label'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">Açılış aşamasında işletme yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
