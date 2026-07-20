@extends('layouts.courier-portal')

@section('title', 'Vardiyalarım')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">Merhaba, {{ $courier['full_name'] }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            Bugünkü vardiyalarını başlat / sonlandır. Saatlik anlaşmalı işletmelerde kazanç otomatik hesaplanır.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <x-ui.finance-stat-card title="Bu Ay Çalışma" :value="$summary['total_hours'].' sa'" :excl-vat="false" accent="blue" />
        <x-ui.finance-stat-card title="Vardiya Sayısı" :value="(string) $summary['sessions']" :excl-vat="false" accent="violet" />
        <x-ui.finance-stat-card title="Saatlik Hakediş" :value="$summary['total_earnings_formatted']" accent="success" />
    </div>

    <x-ui.card title="Bugünkü Vardiyalar">
        @forelse ($today as $item)
            <div class="flex flex-col gap-3 border-b border-gray-100 py-4 last:border-0 last:pb-0 first:pt-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900">
                        {{ $item['shift_name'] }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-500">
                        {{ $item['business_name'] }} · {{ $item['start_time'] }}–{{ $item['end_time'] }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
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

                <div class="flex w-full shrink-0 flex-col gap-2 sm:w-auto sm:flex-row">
                    @if ($item['can_start'])
                        <form method="POST" action="{{ route('courier-portal.shifts.start', $item['shift_id']) }}" class="w-full sm:w-auto">
                            @csrf
                            <x-ui.button type="submit" class="w-full sm:w-auto">Vardiyayı Başlat</x-ui.button>
                        </form>
                    @elseif ($item['can_end'])
                        <form method="POST" action="{{ route('courier-portal.shifts.end', $item['attendance']['id']) }}" class="w-full sm:w-auto">
                            @csrf
                            <x-ui.button type="submit" variant="danger" class="w-full sm:w-auto">Vardiyayı Sonlandır</x-ui.button>
                        </form>
                    @elseif ($item['attendance'] === null)
                        <span class="inline-flex w-full items-center justify-center rounded-lg bg-sky-50 px-3 py-2.5 text-center text-sm font-medium text-sky-700 sm:w-auto">
                            {{ $item['start_window_opens_at'] ?? $item['start_time'] }} itibarıyla başlatılabilir
                        </span>
                    @else
                        <span class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-50 px-3 py-2.5 text-sm font-medium text-emerald-700 sm:w-auto">
                            Geldi
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">Bugün için atanmış vardiya bulunmuyor.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="Son Çalışmalar">
        {{-- Mobile: card list --}}
        <div class="space-y-3 sm:hidden">
            @forelse ($recent as $row)
                <div class="rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900">{{ $row['business_name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $row['shift_name'] }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-medium tabular-nums text-gray-900">{{ $row['earnings_formatted'] }}</p>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
                        <span>{{ $row['work_date_formatted'] }}</span>
                        <span>{{ $row['worked_duration_label'] }}</span>
                        <span>{{ $row['status_label'] }}</span>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-gray-500">Henüz vardiya kaydı yok.</p>
            @endforelse
        </div>

        {{-- Desktop: table --}}
        <div class="hidden overflow-x-auto sm:block">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="pb-2 font-medium text-gray-500">Tarih</th>
                        <th class="pb-2 font-medium text-gray-500">İşletme / Vardiya</th>
                        <th class="pb-2 font-medium text-gray-500">Süre</th>
                        <th class="pb-2 text-right font-medium text-gray-500">Kazanç</th>
                        <th class="pb-2 font-medium text-gray-500">Durum</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recent as $row)
                        <tr>
                            <td class="py-2.5 text-gray-900">{{ $row['work_date_formatted'] }}</td>
                            <td class="py-2.5">
                                <p class="font-medium text-gray-900">{{ $row['business_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $row['shift_name'] }}</p>
                            </td>
                            <td class="py-2.5 text-gray-700">{{ $row['worked_duration_label'] }}</td>
                            <td class="py-2.5 text-right font-medium tabular-nums text-gray-900">{{ $row['earnings_formatted'] }}</td>
                            <td class="py-2.5 text-gray-600">{{ $row['status_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-gray-500">Henüz vardiya kaydı yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-gray-500">
            Dönem: {{ $summary['from_formatted'] }} – {{ $summary['to_formatted'] }}
        </p>
    </x-ui.card>
</div>
@endsection
