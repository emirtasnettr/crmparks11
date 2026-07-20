@extends('layouts.app')

@section('title', 'Vardiyalarım')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Merhaba, {{ $courier['full_name'] }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Bugünkü vardiyalarını başlat / sonlandır. Saatlik anlaşmalı işletmelerde kazanç otomatik hesaplanır.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.finance-stat-card title="Bu Ay Çalışma" :value="$summary['total_hours'].' sa'" :excl-vat="false" accent="blue" />
        <x-ui.finance-stat-card title="Vardiya Sayısı" :value="(string) $summary['sessions']" :excl-vat="false" accent="violet" />
        <x-ui.finance-stat-card title="Saatlik Hakediş" :value="$summary['total_earnings_formatted']" accent="success" />
    </div>

    <x-ui.card title="Bugünkü Vardiyalar">
        @forelse ($today as $item)
            <div @class([
                'flex flex-col gap-3 border-b border-gray-100 py-4 last:border-0 last:pb-0 first:pt-0 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between',
            ])>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $item['shift_name'] }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                        {{ $item['business_name'] }} · {{ $item['start_time'] }}–{{ $item['end_time'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">
                        {{ $item['pricing_model_label'] }}
                        @if ($item['hourly_rate'] !== null)
                            · {{ number_format($item['hourly_rate'], 2, ',', '.') }} ₺/saat
                        @endif
                        @if ($item['attendance'])
                            · {{ $item['attendance']['status_label'] }}
                            @if ($item['attendance']['status'] === 'completed')
                                · {{ $item['attendance']['worked_duration_label'] }}
                                @if ($item['attendance']['earnings_formatted'] !== '—')
                                    · {{ $item['attendance']['earnings_formatted'] }}
                                @endif
                            @endif
                        @endif
                    </p>
                </div>

                <div class="flex shrink-0 gap-2">
                    @if ($item['can_start'])
                        <form method="POST" action="{{ route('courier-portal.shifts.start', $item['shift_id']) }}">
                            @csrf
                            <x-ui.button type="submit">Vardiyayı Başlat</x-ui.button>
                        </form>
                    @elseif ($item['can_end'])
                        <form method="POST" action="{{ route('courier-portal.shifts.end', $item['attendance']['id']) }}">
                            @csrf
                            <x-ui.button type="submit" variant="danger">Vardiyayı Sonlandır</x-ui.button>
                        </form>
                    @elseif ($item['attendance'] === null)
                        <span class="inline-flex items-center rounded-lg bg-sky-50 px-3 py-2 text-sm font-medium text-sky-700 dark:bg-sky-600/10 dark:text-sky-400">
                            {{ $item['start_window_opens_at'] ?? $item['start_time'] }} itibarıyla başlatılabilir
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400">
                            Geldi
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-slate-400">Bugün için atanmış vardiya bulunmuyor.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="Son Çalışmalar">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">İşletme / Vardiya</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Süre</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400 text-right">Kazanç</th>
                        <th class="pb-2 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($recent as $row)
                        <tr>
                            <td class="py-2.5 text-gray-900 dark:text-white">{{ $row['work_date_formatted'] }}</td>
                            <td class="py-2.5">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $row['business_name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $row['shift_name'] }}</p>
                            </td>
                            <td class="py-2.5 text-gray-700 dark:text-slate-300">{{ $row['worked_duration_label'] }}</td>
                            <td class="py-2.5 text-right font-medium tabular-nums text-gray-900 dark:text-white">{{ $row['earnings_formatted'] }}</td>
                            <td class="py-2.5 text-gray-600 dark:text-slate-300">{{ $row['status_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-gray-500 dark:text-slate-400">Henüz vardiya kaydı yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500 dark:text-slate-400">
            Dönem: {{ $summary['from_formatted'] }} – {{ $summary['to_formatted'] }}
        </p>
    </x-ui.card>
</div>
@endsection
