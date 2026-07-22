@extends('layouts.courier-portal')

@section('title', 'Kazançlarım')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">Kazançlarım</h1>
        <p class="mt-1 text-sm text-gray-500">
            Bu ayki çalışma özeti ve son vardiya kazançların.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
        <x-ui.finance-stat-card title="Bu Ay Çalışma" :value="$summary['total_hours'].' sa'" :excl-vat="false" accent="blue" />
        <x-ui.finance-stat-card title="Vardiya Sayısı" :value="(string) $summary['sessions']" :excl-vat="false" accent="violet" />
        <x-ui.finance-stat-card title="Bu Ay Hakediş" :value="$summary['total_earnings_formatted']" :excl-vat="false" accent="success" />
    </div>

    <x-ui.card title="Son Çalışmalar">
        <div class="space-y-3 sm:hidden">
            @forelse ($recent as $row)
                <div class="rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900">{{ $row['business_name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $row['time_range'] }}</p>
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

        <div class="hidden overflow-x-auto sm:block">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="pb-2 font-medium text-gray-500">Tarih</th>
                        <th class="pb-2 font-medium text-gray-500">İşletme</th>
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
                                <p class="text-xs text-gray-500">{{ $row['time_range'] }}</p>
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
