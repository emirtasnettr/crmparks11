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
                        @php
                            $rowShortage = (int) ($row['missing_count'] ?? 0);
                        @endphp
                        <tr
                            class="cursor-pointer transition-colors"
                            :class="expandedId === {{ $row['business_id'] }} ? 'bg-slate-50 dark:bg-slate-800/50' : 'hover:bg-gray-50 dark:hover:bg-slate-800/40'"
                            @click="toggleExpand({{ $row['business_id'] }})"
                        >
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-400"
                                        :class="expandedId === {{ $row['business_id'] }} ? 'border-primary-300 text-primary-600 dark:border-primary-500 dark:text-primary-400' : ''"
                                        :aria-expanded="expandedId === {{ $row['business_id'] }}"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 transition-transform duration-200"
                                            :class="expandedId === {{ $row['business_id'] }} ? 'rotate-90' : ''"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            aria-hidden="true"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                    <span class="min-w-0 font-medium text-gray-900 dark:text-white">
                                        {{ $row['business_name'] }}
                                    </span>
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
                                'text-red-600 dark:text-red-400' => $rowShortage > 0,
                                'text-gray-900 dark:text-white' => $rowShortage <= 0,
                            ])>
                                {{ number_format($rowShortage) }}
                            </td>
                        </tr>
                        <tr x-show="expandedId === {{ $row['business_id'] }}" x-cloak>
                            <td colspan="5" class="bg-slate-50 px-0 py-0 dark:bg-slate-900/50" @click.stop>
                                <div
                                    x-show="expandedId === {{ $row['business_id'] }}"
                                    x-collapse
                                    class="border-t border-slate-200 dark:border-slate-700"
                                >
                                    <div class="space-y-3 px-4 py-4 sm:px-6">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                                <span class="font-medium text-slate-900 dark:text-white">{{ $row['business_name'] }}</span>
                                                <span class="text-slate-400">·</span>
                                                Bugün {{ $workDateFormatted }}
                                            </p>
                                            <a
                                                href="{{ $row['business_url'] }}"
                                                class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                İşletme detayı →
                                            </a>
                                        </div>

                                        @if (($row['today_shifts'] ?? []) === [])
                                            <p class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">
                                                Bugün için tanımlı vardiya yok.
                                            </p>
                                        @else
                                            <div class="space-y-3">
                                                @foreach ($row['today_shifts'] as $shift)
                                                    @php
                                                        $shortage = (int) ($shift['operational_shortage'] ?? 0);
                                                        $hasStarted = (bool) ($shift['has_started'] ?? false);
                                                        $missingAssignments = (int) ($shift['missing_assignments'] ?? 0);
                                                        $shiftTone = $shortage > 0
                                                            ? 'rose'
                                                            : ($hasStarted ? 'emerald' : ($missingAssignments > 0 ? 'amber' : 'slate'));
                                                        $toneClasses = [
                                                            'rose' => [
                                                                'panel' => 'border-rose-200 bg-white dark:border-rose-500/30 dark:bg-slate-800/80',
                                                                'bar' => 'bg-rose-500',
                                                                'badge' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300',
                                                                'badge_label' => $hasStarted ? 'Eksik kadro' : 'Atama eksik',
                                                            ],
                                                            'emerald' => [
                                                                'panel' => 'border-emerald-200 bg-white dark:border-emerald-500/30 dark:bg-slate-800/80',
                                                                'bar' => 'bg-emerald-500',
                                                                'badge' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300',
                                                                'badge_label' => 'Tamam',
                                                            ],
                                                            'amber' => [
                                                                'panel' => 'border-amber-200 bg-white dark:border-amber-500/30 dark:bg-slate-800/80',
                                                                'bar' => 'bg-amber-500',
                                                                'badge' => 'bg-amber-100 text-amber-900 dark:bg-amber-500/20 dark:text-amber-300',
                                                                'badge_label' => 'Atama eksik',
                                                            ],
                                                            'slate' => [
                                                                'panel' => 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800/80',
                                                                'bar' => 'bg-slate-400',
                                                                'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                                                                'badge_label' => $hasStarted ? 'Devam ediyor' : 'Planlandı',
                                                            ],
                                                        ][$shiftTone];
                                                    @endphp

                                                    <section class="overflow-hidden rounded-xl border {{ $toneClasses['panel'] }}">
                                                        <div class="flex">
                                                            <div class="w-1 shrink-0 {{ $toneClasses['bar'] }}" aria-hidden="true"></div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-700/80">
                                                                    <div class="min-w-0">
                                                                        <p class="text-base font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white">
                                                                            {{ $shift['time'] ?: 'Vardiya' }}
                                                                        </p>
                                                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                                                            {{ $shift['summary_label'] }}
                                                                        </p>
                                                                    </div>
                                                                    <span class="inline-flex shrink-0 rounded-md px-2 py-1 text-xs font-semibold {{ $toneClasses['badge'] }}">
                                                                        {{ $toneClasses['badge_label'] }}
                                                                    </span>
                                                                </div>

                                                                <div class="px-2 py-2 sm:px-3">
                                                                    @forelse ($shift['couriers'] as $courier)
                                                                        @php
                                                                            $courierTone = match ($courier['status']) {
                                                                                'active', 'completed' => 'emerald',
                                                                                'late', 'starting_soon' => 'amber',
                                                                                'not_started' => 'rose',
                                                                                default => 'slate',
                                                                            };
                                                                            $dotClass = match ($courierTone) {
                                                                                'emerald' => 'bg-emerald-500',
                                                                                'amber' => 'bg-amber-500',
                                                                                'rose' => 'bg-rose-500',
                                                                                default => 'bg-slate-400',
                                                                            };
                                                                            $badgeClass = match ($courierTone) {
                                                                                'emerald' => 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300',
                                                                                'amber' => 'bg-amber-50 text-amber-900 dark:bg-amber-500/15 dark:text-amber-300',
                                                                                'rose' => 'bg-rose-50 text-rose-800 dark:bg-rose-500/15 dark:text-rose-300',
                                                                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                                                                            };
                                                                        @endphp
                                                                        <div class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-900/40">
                                                                            <div class="flex min-w-0 items-center gap-2.5">
                                                                                <span class="h-2 w-2 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
                                                                                <div class="min-w-0">
                                                                                    <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $courier['name'] }}</p>
                                                                                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $courier['phone'] }}</p>
                                                                                </div>
                                                                            </div>
                                                                            <span class="shrink-0 rounded-md px-2 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                                                                {{ $courier['status_label'] }}
                                                                            </span>
                                                                        </div>
                                                                    @empty
                                                                        @if ($missingAssignments === 0)
                                                                            <p class="px-2 py-3 text-sm text-slate-500 dark:text-slate-400">Kadro boş.</p>
                                                                        @endif
                                                                    @endforelse

                                                                    @if ($missingAssignments > 0)
                                                                        @for ($i = 0; $i < $missingAssignments; $i++)
                                                                            <div class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-rose-200 bg-rose-50/60 px-2 py-2 dark:border-rose-500/30 dark:bg-rose-500/10">
                                                                                <div class="flex min-w-0 items-center gap-2.5">
                                                                                    <span class="h-2 w-2 shrink-0 rounded-full bg-rose-400" aria-hidden="true"></span>
                                                                                    <p class="text-sm font-medium text-rose-800 dark:text-rose-300">Boş kadro yeri</p>
                                                                                </div>
                                                                                <span class="shrink-0 rounded-md bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-800 dark:bg-rose-500/20 dark:text-rose-300">
                                                                                    Atanmadı
                                                                                </span>
                                                                            </div>
                                                                        @endfor
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </section>
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
