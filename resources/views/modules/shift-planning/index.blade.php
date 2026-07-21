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
        'required_headcount' => $shift['required_headcount'],
        'assigned_count' => $shift['assigned_count'],
        'staffing_label' => $shift['staffing_label'],
        'notes' => $shift['notes'] ?? '',
        'is_active' => $shift['is_active'],
        'couriers' => $shift['couriers'],
        'courier_ids' => $shift['courier_ids'],
        'color' => $shift['color'],
    ])->values()->all();
@endphp

<div
    x-data="shiftPlanningPage({
        selectedBusinessId: @js($selectedBusinessId),
        shifts: @js($shiftsForJs),
        availableCouriers: @js($availableCouriers),
        canCreate: @js($canCreate),
        canUpdate: @js($canUpdate),
        canDelete: @js($canDelete),
        defaultStartDate: @js(now()->toDateString()),
        defaultEndDate: @js(now()->toDateString()),
        storeUrl: @js(route('shift-planning.store')),
        updateUrlTemplate: @js(url('/vardiya-planlama/__ID__')),
        assignUrlTemplate: @js(url('/vardiya-planlama/__ID__/kuryeler')),
        destroyUrlTemplate: @js(url('/vardiya-planlama/__ID__')),
        eligibleCouriersUrl: @js(route('shift-planning.eligible-couriers')),
    })"
>
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Vardiya Planlama</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                İşletme vardiyalarını haftalık görünümden yönetin.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($selectedBusinessId)
                <a href="{{ route('shift-planning.attendance') }}">
                    <x-ui.button type="button" variant="secondary">Canlı Operasyon</x-ui.button>
                </a>
            @endif
            <template x-if="canCreate && selectedBusinessId">
                <x-ui.button type="button" x-on:click="openCreate()">
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
                    Vardiya yapısını görmek için önce bir işletme seçin.
                </p>
            </div>
        </x-ui.card>
    @else
        <div class="mt-6">
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Haftalık Görünüm</h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Gerekli kişi · atama eksiği · katılım (geldi / katılmadı)</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('shift-planning.index', ['business_id' => $selectedBusinessId, 'week' => $week['prev_week']]) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-slate-600">← Önceki</a>
                    <span class="min-w-[10rem] text-center text-sm font-semibold text-gray-900 dark:text-white">{{ $week['label'] }}</span>
                    <a href="{{ route('shift-planning.index', ['business_id' => $selectedBusinessId, 'week' => $week['next_week']]) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-slate-600">Sonraki →</a>
                    <a href="{{ route('shift-planning.attendance') }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-primary-700 dark:border-slate-600 dark:text-primary-300">Canlı Operasyon</a>
                </div>
            </div>

            @if (count($shifts) === 0)
                <x-ui.card>
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-slate-400">
                        Bu işletme için henüz vardiya tanımlanmamış. Yeni vardiya ekleyerek başlayın.
                    </p>
                </x-ui.card>
            @else
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-7">
                    @foreach ($calendarDays as $day)
                        <div @class([
                            'rounded-xl border p-3',
                            'border-primary-300 bg-primary-50/40' => $day['is_today'],
                            'border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800' => ! $day['is_today'],
                        ])>
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 dark:text-slate-400">{{ $day['label_short'] }}</p>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $day['day_number'] }} {{ $day['month_short'] }}</p>
                                </div>
                                <a href="{{ route('shift-planning.attendance') }}" class="text-[11px] font-medium text-primary-600 hover:underline">Takip</a>
                            </div>
                            <div class="mt-2 space-y-2">
                                @forelse ($day['shifts'] as $occurrence)
                                    <div class="rounded-lg border px-2 py-1.5 text-xs {{ $occurrence['color'] }}">
                                        <p class="font-semibold">{{ $occurrence['name'] }}</p>
                                        <p class="opacity-80">{{ $occurrence['time_range'] }}</p>
                                        @if (! empty($occurrence['attendance']))
                                            <p @class([
                                                'mt-1 font-medium',
                                                'text-sky-800' => ! empty($occurrence['attendance']['is_future']) && ($occurrence['attendance']['missing_assignments'] ?? 0) === 0,
                                                'text-amber-800' => ! empty($occurrence['attendance']['is_future']) && ($occurrence['attendance']['missing_assignments'] ?? 0) > 0,
                                                'text-rose-800' => empty($occurrence['attendance']['is_future']) && ($occurrence['attendance']['missing'] ?? 0) > 0,
                                                'text-emerald-800' => empty($occurrence['attendance']['is_future']) && ($occurrence['attendance']['missing'] ?? 0) === 0,
                                            ])>
                                                {{ $occurrence['attendance']['label'] }}
                                            </p>
                                        @endif
                                        @foreach ($occurrence['working_couriers'] as $courier)
                                            <p class="mt-0.5">
                                                {{ $courier['name'] }}
                                            </p>
                                        @endforeach
                                        @if ($occurrence['working_couriers'] === [])
                                            <p class="mt-0.5 opacity-70">Kadro boş</p>
                                        @endif
                                        @if ($canUpdate || $canDelete)
                                            <div class="mt-1.5 flex flex-wrap gap-1">
                                                @if ($canUpdate)
                                                    <button type="button" class="rounded px-1.5 py-0.5 font-medium underline-offset-2 hover:underline" x-on:click="openAssign({{ $occurrence['id'] }})">Kadro</button>
                                                    <button type="button" class="rounded px-1.5 py-0.5 font-medium underline-offset-2 hover:underline" x-on:click="openEdit({{ $occurrence['id'] }})">Düzenle</button>
                                                @endif
                                                @if ($canDelete)
                                                    <button type="button" class="rounded px-1.5 py-0.5 font-medium text-rose-700 underline-offset-2 hover:underline" x-on:click="openDeleteConfirm({{ $occurrence['id'] }})">Sil</button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-400">Vardiya yok</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @include('modules.shift-planning.partials.modal')
</div>
@endsection
