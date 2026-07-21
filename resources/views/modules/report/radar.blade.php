@extends('layouts.app')

@section('title', 'Radar')


@section('content')
<div
    x-data="radarPage(@js($rows))"
    @keydown.escape.window="closePeopleModal()"
>
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Radar</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            İşletme ihtiyacı, vardiyadaki ve atanan kuryeler · {{ $workDateFormatted }}
        </p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="İşletme" :value="number_format($summary['businesses'])" icon="building" accent="primary" />
        <x-ui.finance-stat-card title="Planlanmış Kurye" :value="number_format($summary['planned'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Vardiyada Kurye" :value="number_format($summary['active'])" icon="clock" accent="success" />
        <x-ui.finance-stat-card title="Atanan Kurye" :value="number_format($summary['roster'])" icon="report" accent="violet" />
        <x-ui.finance-stat-card title="Eksik Kurye" :value="number_format($summary['missing'])" icon="courier" accent="danger" />
    </div>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme Adı</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Planlanmış Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Vardiyada Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Atanan Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400 sm:px-6">Eksik Kurye</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($rows as $index => $row)
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
                                        :title="expandedId === {{ $row['business_id'] }} ? 'Kapat' : 'Vardiyaları aç'"
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
                                {{ number_format($row['planned_courier_count']) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                @if ($row['active_on_shift_count'] > 0)
                                    <button
                                        type="button"
                                        class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                                        @click.stop="openPeopleModal({{ $index }}, 'active')"
                                    >
                                        {{ number_format($row['active_on_shift_count']) }}
                                    </button>
                                @else
                                    <span class="text-gray-900 dark:text-white">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                @if ($row['roster_planned_count'] > 0)
                                    <button
                                        type="button"
                                        class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                                        @click.stop="openPeopleModal({{ $index }}, 'roster')"
                                    >
                                        {{ number_format($row['roster_planned_count']) }}
                                    </button>
                                @else
                                    <span class="text-gray-900 dark:text-white">0</span>
                                @endif
                            </td>
                            <td @class([
                                'px-4 py-3 text-right tabular-nums font-medium sm:px-6',
                                'text-red-600 dark:text-red-400' => $row['missing_courier_count'] > 0,
                                'text-gray-900 dark:text-white' => $row['missing_courier_count'] <= 0,
                            ])>
                                {{ number_format($row['missing_courier_count']) }}
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
                                                7 günlük vardiya planı
                                            </p>
                                            <a
                                                href="{{ $row['business_url'] }}"
                                                class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400"
                                            >
                                                İşletme detayı →
                                            </a>
                                        </div>

                                        @if (($row['week_schedule'] ?? []) === [])
                                            <p class="rounded-lg border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-400">
                                                Önümüzdeki 7 günde tanımlı vardiya yok.
                                            </p>
                                        @else
                                            <div class="space-y-3">
                                                @foreach ($row['week_schedule'] as $day)
                                                    <div @class([
                                                        'overflow-hidden rounded-xl border bg-white dark:bg-slate-800/70',
                                                        'border-primary-200 dark:border-primary-500/30' => $day['is_today'],
                                                        'border-slate-200 dark:border-slate-700' => ! $day['is_today'],
                                                    ])>
                                                        <div @class([
                                                            'flex items-center justify-between gap-3 border-b px-4 py-2.5',
                                                            'border-primary-100 bg-primary-50/70 dark:border-primary-500/20 dark:bg-primary-500/10' => $day['is_today'],
                                                            'border-slate-100 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-800' => ! $day['is_today'],
                                                        ])>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                                                    {{ $day['label'] }}
                                                                </span>
                                                                @if ($day['is_today'])
                                                                    <span class="rounded-full bg-primary-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                                                        Bugün
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <span class="text-xs tabular-nums text-slate-500 dark:text-slate-400">
                                                                {{ $day['shift_count'] }} vardiya
                                                            </span>
                                                        </div>

                                                        <div class="divide-y divide-slate-100 dark:divide-slate-700/80">
                                                            @foreach ($day['shifts'] as $shift)
                                                                <div class="px-4 py-3">
                                                                    <div class="mb-2 flex flex-wrap items-baseline justify-between gap-2">
                                                                        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                                                            <span class="font-medium text-slate-900 dark:text-white">{{ $shift['name'] }}</span>
                                                                            @if ($shift['time'])
                                                                                <span class="text-xs tabular-nums text-slate-500 dark:text-slate-400">{{ $shift['time'] }}</span>
                                                                            @endif
                                                                        </div>
                                                                        <span class="text-xs text-slate-500 dark:text-slate-400">
                                                                            {{ $shift['courier_count'] }} kurye
                                                                        </span>
                                                                    </div>

                                                                    @if ($shift['couriers'] === [])
                                                                        <p class="text-xs text-amber-700 dark:text-amber-400">Henüz kurye atanmamış.</p>
                                                                    @else
                                                                        <div class="flex flex-wrap gap-1.5">
                                                                            @foreach ($shift['couriers'] as $courier)
                                                                                <span
                                                                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs text-slate-700 dark:border-slate-600 dark:bg-slate-900/50 dark:text-slate-200"
                                                                                    title="{{ $courier['phone'] }}"
                                                                                >
                                                                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                                                                                        {{ mb_strtoupper(mb_substr($courier['name'], 0, 1)) }}
                                                                                    </span>
                                                                                    {{ $courier['name'] }}
                                                                                </span>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
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
                                Henüz radar verisi bulunmuyor.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div
        x-show="peopleModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
        role="dialog"
        aria-modal="true"
    >
        <div
            x-show="peopleModalOpen"
            x-transition.opacity
            @click="closePeopleModal()"
            class="fixed inset-0 bg-gray-900/50"
        ></div>

        <div
            x-show="peopleModalOpen"
            x-transition
            class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
        >
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="modalTitle"></h3>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400" x-text="modalBusinessName"></p>
                </div>
                <button
                    type="button"
                    @click="closePeopleModal()"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4">
                <template x-if="modalPeople.length === 0">
                    <p class="py-6 text-center text-sm text-gray-500 dark:text-slate-400">
                        Listelenecek kurye bulunamadı.
                    </p>
                </template>

                <ul x-show="modalPeople.length > 0" class="divide-y divide-gray-100 dark:divide-slate-700">
                    <template x-for="person in modalPeople" :key="person.id">
                        <li class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white" x-text="person.name"></p>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400" x-text="person.phone"></p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p
                                    class="text-sm font-medium tabular-nums text-gray-900 dark:text-white"
                                    x-text="person.shift_time || ''"
                                    x-show="person.shift_time"
                                ></p>
                                <p
                                    class="mt-0.5 text-xs text-gray-400 dark:text-slate-500"
                                    x-text="person.shift_name || ''"
                                    x-show="person.shift_name"
                                ></p>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-slate-700">
                <x-ui.button type="button" variant="secondary" @click="closePeopleModal()">Kapat</x-ui.button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function radarPage(rows) {
    return {
        rows,
        expandedId: null,
        peopleModalOpen: false,
        modalTitle: '',
        modalBusinessName: '',
        modalPeople: [],
        toggleExpand(businessId) {
            this.expandedId = this.expandedId === businessId ? null : businessId;
        },
        openPeopleModal(index, type) {
            const row = this.rows[index];
            if (!row) return;

            const titles = {
                active: 'Vardiyada Kurye',
                roster: 'Atanan Kurye',
            };
            const peopleKey = {
                active: 'active_couriers',
                roster: 'roster_couriers',
            }[type];

            this.modalTitle = titles[type] || 'Kuryeler';
            this.modalBusinessName = row.business_name || '';
            this.modalPeople = row[peopleKey] || [];
            this.peopleModalOpen = true;
        },
        closePeopleModal() {
            this.peopleModalOpen = false;
            this.modalPeople = [];
        },
    };
}
</script>
@endpush
@endsection
