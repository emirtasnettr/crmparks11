@extends('layouts.app')

@section('title', 'Vardiya Raporu')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Vardiya Raporu</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Seçilen tarih aralığındaki tüm kuryelerin vardiya giriş-çıkış, geç kalma ve katılmama durumları.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.export-button :href="route('shift-planning.report.export', array_filter([
                'from' => $dateFrom,
                'to' => $dateTo,
                'status' => $selectedStatuses ?: null,
            ]))" />
        </div>
    </div>

    <x-ui.card :padding="false">
        <form
            method="GET"
            action="{{ route('shift-planning.report') }}"
            class="grid grid-cols-1 gap-4 p-4 sm:p-5 lg:grid-cols-[11rem_11rem_minmax(0,1fr)_auto] lg:items-end lg:gap-x-8"
        >
            <x-ui.input
                type="date"
                name="from"
                label="Başlangıç"
                :value="$dateFrom"
                required
            />

            <x-ui.input
                type="date"
                name="to"
                label="Bitiş"
                :value="$dateTo"
                required
            />

            <div class="space-y-1.5 lg:border-l lg:border-gray-200 lg:pl-8 dark:lg:border-slate-700">
                <span class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</span>
                <div class="flex min-h-[42px] flex-wrap content-center items-center gap-x-6 gap-y-2">
                    @foreach ($statusOptions as $value => $label)
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-slate-200">
                            <input
                                type="checkbox"
                                name="status[]"
                                value="{{ $value }}"
                                @checked(in_array($value, $selectedStatuses, true))
                                class="h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800"
                            >
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-ui.button type="submit" size="sm">Filtrele</x-ui.button>
                <x-ui.button href="{{ route('shift-planning.report') }}" variant="secondary" size="sm">Temizle</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <p class="text-sm text-gray-500 dark:text-slate-400">{{ $report['range_label'] }}</p>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Telefon</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletme</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İl / İlçe</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Saat</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Giriş</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Çıkış</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Geç</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Süre</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($report['rows'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300 sm:px-6">{{ $row['work_date_formatted'] }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $row['courier_name'] }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['phone'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['business_name'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['business_location'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['time_range'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['started_at_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['ended_at_formatted'] }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                    'bg-rose-50 text-rose-700 dark:bg-rose-600/10 dark:text-rose-400' => $row['status'] === 'missing',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-600/10 dark:text-amber-400' => $row['status'] === 'late',
                                    'bg-violet-50 text-violet-700 dark:bg-violet-600/10 dark:text-violet-400' => $row['status'] === 'in_progress',
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-600/10 dark:text-emerald-400' => $row['status'] === 'completed',
                                    'bg-sky-50 text-sky-700 dark:bg-sky-600/10 dark:text-sky-400' => $row['status'] === 'planned',
                                ])>
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $row['late_minutes_label'] }}</td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300 sm:px-6">{{ $row['worked_duration_label'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-slate-400">
                                Seçilen tarih aralığında vardiya kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-ui.pagination :total="$total" :page="$page" :per-page="$perPage" :last-page="$lastPage" />
    </x-ui.card>
</div>
@endsection
