@extends('layouts.app')

@section('title', 'Canlı Operasyon')

@section('content')
<div
    x-data="radarPage()"
    @keydown.escape.window="expandedId = null"
>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Canlı Operasyon</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Bugünkü vardiyalar · gerekli / atanan / eksik kadro · {{ $workDateFormatted }}
        </p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="İşletme" :value="number_format($summary['businesses'])" icon="building" accent="primary" />
        <x-ui.finance-stat-card title="Vardiya" :value="number_format($summary['shifts'])" icon="calendar" accent="blue" />
        <x-ui.finance-stat-card title="Gerekli Kişi" :value="number_format($summary['required'])" icon="courier" accent="violet" />
        <x-ui.finance-stat-card title="Atanan Kurye" :value="number_format($summary['assigned'])" icon="report" accent="success" />
        <x-ui.finance-stat-card title="Eksik Kurye" :value="number_format($summary['missing'])" icon="courier" accent="danger" />
    </div>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme Adı</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Vardiya</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Gerekli</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Atanan</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400 sm:px-6">Eksik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($rows as $row)
                        <tr
                            class="transition-colors"
                            :class="expandedId === {{ $row['business_id'] }} ? 'bg-slate-50/80 dark:bg-slate-800/40' : 'hover:bg-gray-50 dark:hover:bg-slate-800/50'"
                        >
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex items-center gap-2.5">
                                    <button
                                        type="button"
                                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 transition hover:border-primary-300 hover:text-primary-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400 dark:hover:border-primary-500 dark:hover:text-primary-400"
                                        @click="toggleExpand({{ $row['business_id'] }})"
                                        :aria-expanded="expandedId === {{ $row['business_id'] }}"
                                        :title="expandedId === {{ $row['business_id'] }} ? 'Kapat' : 'Bugünkü vardiyaları aç'"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 transition-transform duration-200"
                                            :class="expandedId === {{ $row['business_id'] }} ? 'rotate-45' : ''"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="min-w-0 text-left font-medium text-gray-900 transition hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                        @click="toggleExpand({{ $row['business_id'] }})"
                                    >
                                        {{ $row['business_name'] }}
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($row['shift_count']) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($row['required_count']) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($row['assigned_count']) }}
                            </td>
                            <td @class([
                                'px-4 py-3 text-right tabular-nums font-medium sm:px-6',
                                'text-red-600 dark:text-red-400' => $row['missing_count'] > 0,
                                'text-gray-900 dark:text-white' => $row['missing_count'] <= 0,
                            ])>
                                {{ number_format($row['missing_count']) }}
                            </td>
                        </tr>
                        <tr x-show="expandedId === {{ $row['business_id'] }}" x-cloak>
                            <td colspan="5" class="bg-slate-50/90 px-4 py-0 sm:px-6 dark:bg-slate-900/40">
                                <div
                                    x-show="expandedId === {{ $row['business_id'] }}"
                                    x-collapse
                                    class="border-t border-slate-200/80 dark:border-slate-700/80"
                                >
                                    <div class="py-4">
                                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                Bugünkü vardiyalar · anlık durum
                                            </p>
                                            <a
                                                href="{{ $row['business_url'] }}"
                                                class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                İşletme detayı →
                                            </a>
                                        </div>

                                        @if (($row['today_shifts'] ?? []) === [])
                                            <p class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">
                                                Bugün için tanımlı vardiya yok.
                                            </p>
                                        @else
                                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                                @foreach ($row['today_shifts'] as $shift)
                                                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800/70">
                                                        <div class="flex items-start justify-between gap-2 border-b border-slate-100 px-3 py-2.5 dark:border-slate-700">
                                                            <div class="min-w-0">
                                                                <p class="truncate font-semibold text-slate-900 dark:text-white">{{ $shift['name'] }}</p>
                                                                @if ($shift['time'])
                                                                    <p class="text-[11px] tabular-nums text-slate-500 dark:text-slate-400">{{ $shift['time'] }}</p>
                                                                @endif
                                                            </div>
                                                            <p @class([
                                                                'shrink-0 text-right text-[11px] font-medium',
                                                                'text-rose-700 dark:text-rose-400' => ($shift['operational_shortage'] ?? 0) > 0,
                                                                'text-emerald-700 dark:text-emerald-400' => ($shift['operational_shortage'] ?? 0) === 0,
                                                            ])>
                                                                {{ $shift['summary_label'] }}
                                                            </p>
                                                        </div>

                                                        <div class="space-y-1.5 px-3 py-2.5">
                                                            @foreach ($shift['couriers'] as $courier)
                                                                <div class="flex items-center justify-between gap-2 rounded-md border border-slate-100 bg-slate-50 px-2 py-1.5 dark:border-slate-700 dark:bg-slate-900/40">
                                                                    <div class="min-w-0">
                                                                        <p class="truncate text-xs font-medium text-slate-800 dark:text-slate-100">{{ $courier['name'] }}</p>
                                                                        <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $courier['phone'] }}</p>
                                                                    </div>
                                                                    <span @class([
                                                                        'shrink-0 rounded-md px-1.5 py-0.5 text-[10px] font-semibold',
                                                                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => in_array($courier['status'], ['active', 'completed'], true),
                                                                        'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' => in_array($courier['status'], ['late', 'starting_soon'], true),
                                                                        'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300' => $courier['status'] === 'not_started',
                                                                        'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' => $courier['status'] === 'upcoming',
                                                                    ])>
                                                                        {{ $courier['status_label'] }}
                                                                    </span>
                                                                </div>
                                                            @endforeach

                                                            @if (($shift['missing_assignments'] ?? 0) > 0)
                                                                @for ($i = 0; $i < $shift['missing_assignments']; $i++)
                                                                    <div class="flex items-center justify-between gap-2 rounded-md border border-dashed border-rose-200 bg-rose-50/70 px-2 py-1.5 dark:border-rose-500/30 dark:bg-rose-500/10">
                                                                        <p class="text-xs font-medium text-rose-800 dark:text-rose-300">Eksik kadro</p>
                                                                        <span class="shrink-0 rounded-md bg-rose-100 px-1.5 py-0.5 text-[10px] font-semibold text-rose-800 dark:bg-rose-500/20 dark:text-rose-300">
                                                                            Atanmadı
                                                                        </span>
                                                                    </div>
                                                                @endfor
                                                            @endif

                                                            @if ($shift['couriers'] === [] && ($shift['missing_assignments'] ?? 0) === 0)
                                                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Kadro boş.</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-slate-400 sm:px-6">
                                Bugün için canlı operasyon verisi bulunmuyor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

@push('scripts')
<script>
function radarPage() {
    return {
        expandedId: null,
        toggleExpand(businessId) {
            this.expandedId = this.expandedId === businessId ? null : businessId;
        },
    };
}
</script>
@endpush
@endsection
