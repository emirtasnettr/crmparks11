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
            İşletme ihtiyacı, vardiyadaki ve yaklaşan kuryeler · {{ $workDateFormatted }}
        </p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-ui.finance-stat-card title="İşletme" :value="number_format($summary['businesses'])" icon="building" accent="primary" />
        <x-ui.finance-stat-card title="Planlanmış Kurye" :value="number_format($summary['planned'])" icon="courier" accent="blue" />
        <x-ui.finance-stat-card title="Vardiyada Kurye" :value="number_format($summary['active'])" icon="clock" accent="success" />
        <x-ui.finance-stat-card title="Yaklaşan Kurye" :value="number_format($summary['roster'])" icon="report" accent="violet" />
        <x-ui.finance-stat-card title="Eksik Kurye" :value="number_format($summary['missing'])" icon="courier" accent="danger" />
    </div>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşletme Adı</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Planlanmış Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Vardiyada Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400">Yaklaşan Kurye</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-slate-400 sm:px-6">Eksik Kurye</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse ($rows as $index => $row)
                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">
                                <a href="{{ route('businesses.show', $row['business_id']) }}" class="hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ $row['business_name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-gray-900 dark:text-white">
                                {{ number_format($row['planned_courier_count']) }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                @if ($row['active_on_shift_count'] > 0)
                                    <button
                                        type="button"
                                        class="font-medium text-primary-600 hover:underline dark:text-primary-400"
                                        @click="openPeopleModal({{ $index }}, 'active')"
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
                                        @click="openPeopleModal({{ $index }}, 'roster')"
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
        peopleModalOpen: false,
        modalTitle: '',
        modalBusinessName: '',
        modalPeople: [],
        openPeopleModal(index, type) {
            const row = this.rows[index];
            if (!row) return;

            const titles = {
                active: 'Vardiyada Kurye',
                roster: 'Yaklaşan Kurye',
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
