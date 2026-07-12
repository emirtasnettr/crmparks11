@extends('layouts.app')

@section('title', 'Vardiya Planlama')

@section('content')
@php
    $shiftsForJs = collect($shifts)->map(fn ($shift) => [
        'id' => $shift['id'],
        'name' => $shift['name'],
        'start_time' => $shift['start_time_raw'],
        'end_time' => $shift['end_time_raw'],
        'start_date' => $shift['start_date'],
        'end_date' => $shift['end_date'],
        'time_range' => $shift['time_range'],
        'date_range_label' => $shift['date_range_label'],
        'days_of_week' => $shift['days_of_week'],
        'notes' => $shift['notes'] ?? '',
        'is_active' => $shift['is_active'],
        'couriers_by_date' => $shift['couriers_by_date'],
        'color' => $shift['color'],
    ])->values()->all();

    $defaultStart = $week['week_start'];
    $defaultEnd = $week['week_end'];
@endphp

<div
    x-data="shiftPlanningPage({
        selectedBusinessId: @js($selectedBusinessId),
        shifts: @js($shiftsForJs),
        week: @js($week),
        availableCouriers: @js($availableCouriers),
        weekDayOptions: @js(collect(\App\Modules\ShiftPlanning\Data\ShiftPlanningFormData::weekDays())->map(fn ($label, $iso) => ['iso' => (int) $iso, 'label' => $label])->values()->all()),
        defaultStartDate: @js($defaultStart),
        defaultEndDate: @js($defaultEnd),
        canCreate: @js($canCreate),
        canUpdate: @js($canUpdate),
        canDelete: @js($canDelete),
        storeUrl: @js(route('shift-planning.store')),
        updateUrlTemplate: @js(url('/vardiya-planlama/__ID__')),
        assignUrlTemplate: @js(url('/vardiya-planlama/__ID__/kuryeler')),
        destroyUrlTemplate: @js(url('/vardiya-planlama/__ID__')),
    })"
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Vardiya Planlama</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Tarih aralığıyla vardiya ekleyin; takvimde her güne ayrı kurye atayın.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <template x-if="canCreate && selectedBusinessId">
                <x-ui.button type="button" @click="openCreate()">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Yeni Vardiya
                </x-ui.button>
            </template>
        </div>
    </div>

    <x-ui.card :padding="false">
        <form method="GET" action="{{ route('shift-planning.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
            <input type="hidden" name="week" value="{{ $week['week_start'] }}">
            <x-ui.select
                name="business_id"
                label="İşletme"
                :selected="$selectedBusinessId ? (string) $selectedBusinessId : ''"
                :options="collect($businesses)->mapWithKeys(fn ($b) => [(string) $b['id'] => $b['name']])->prepend('İşletme seçin', '')->all()"
                onchange="this.form.submit()"
            />
        </form>
    </x-ui.card>

    @if (! $selectedBusinessId)
        <x-ui.card class="mt-6">
            <div class="py-12 text-center">
                <p class="text-sm text-gray-500 dark:text-slate-400">
                    Haftalık takvimi görmek için önce bir işletme seçin.
                </p>
            </div>
        </x-ui.card>
    @else
        <x-ui.card :padding="false" class="mt-6 overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-6">
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedBusinessName }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">
                        {{ count($shifts) }} vardiya · {{ $activeCourierCount }} atanmış kurye
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('shift-planning.index', ['business_id' => $selectedBusinessId, 'week' => $week['prev_week']]) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        ← Önceki
                    </a>
                    <span class="min-w-[11rem] text-center text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $week['label'] }}
                    </span>
                    <a
                        href="{{ route('shift-planning.index', ['business_id' => $selectedBusinessId, 'week' => $week['next_week']]) }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        Sonraki →
                    </a>
                    @unless ($week['is_current'])
                        <a
                            href="{{ route('shift-planning.index', ['business_id' => $selectedBusinessId]) }}"
                            class="inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-slate-200"
                        >
                            Bu hafta
                        </a>
                    @endunless
                </div>
            </div>

            @if ($activeCourierCount === 0)
                <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200 sm:px-6">
                    Bu işletmeye henüz aktif kurye atanmamış. Vardiyaları oluşturabilirsiniz; kurye atamak için önce
                    <a href="{{ route('businesses.assignments.index', ['business_id' => $selectedBusinessId]) }}" class="font-medium underline">Atanan Kuryeler</a>
                    sayfasından atama yapın.
                </div>
            @endif

            <div class="overflow-x-auto">
                <div class="grid min-w-[980px] grid-cols-7 divide-x divide-gray-200 dark:divide-slate-700">
                    @foreach ($calendarDays as $day)
                        <div @class([
                            'min-h-[28rem] bg-white dark:bg-slate-900',
                            'bg-primary-50/40 dark:bg-primary-600/5' => $day['is_today'],
                        ])>
                            <div @class([
                                'border-b border-gray-200 px-3 py-3 text-center dark:border-slate-700',
                                'bg-primary-50 dark:bg-primary-600/10' => $day['is_today'],
                            ])>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">
                                    {{ $day['label_short'] }}
                                </p>
                                <p @class([
                                    'mt-1 text-lg font-semibold',
                                    'text-primary-700 dark:text-primary-300' => $day['is_today'],
                                    'text-gray-900 dark:text-white' => ! $day['is_today'],
                                ])>
                                    {{ $day['day_number'] }}
                                    <span class="text-xs font-normal text-gray-500 dark:text-slate-400">{{ $day['month_short'] }}</span>
                                </p>
                            </div>

                            <div class="space-y-2 p-2">
                                @forelse ($day['shifts'] as $shift)
                                    <button
                                        type="button"
                                        @click="openShiftActions({{ $shift['id'] }}, '{{ $day['date'] }}')"
                                        class="w-full rounded-lg border px-2.5 py-2 text-left transition hover:shadow-sm {{ $shift['color'] }}"
                                    >
                                        <p class="text-xs font-semibold leading-tight">{{ $shift['name'] }}</p>
                                        <p class="mt-0.5 text-[11px] opacity-80">{{ $shift['time_range'] }}</p>
                                        @if ($shift['courier_count'] > 0)
                                            <div class="mt-1.5 space-y-0.5">
                                                @foreach (array_slice($shift['couriers'], 0, 3) as $courier)
                                                    <p class="truncate text-[11px] font-medium opacity-90">{{ $courier['name'] }}</p>
                                                @endforeach
                                                @if ($shift['courier_count'] > 3)
                                                    <p class="text-[11px] opacity-70">+{{ $shift['courier_count'] - 3 }}</p>
                                                @endif
                                            </div>
                                        @else
                                            <p class="mt-1.5 text-[11px] italic opacity-70">Kurye yok</p>
                                        @endif
                                    </button>
                                @empty
                                    <p class="px-1 py-6 text-center text-[11px] text-gray-400 dark:text-slate-500">
                                        Vardiya yok
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (count($shifts) === 0)
                <div class="border-t border-gray-200 px-6 py-8 text-center text-sm text-gray-500 dark:border-slate-700 dark:text-slate-400">
                    Bu haftada görünen vardiya yok. Tarih aralığı seçerek yeni vardiya ekleyin.
                </div>
            @endif
        </x-ui.card>
    @endif

    @include('modules.shift-planning.partials.modal')
    @include('modules.shift-planning.partials.actions-modal')
</div>
@endsection
