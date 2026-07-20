@extends('layouts.app')

@section('title', 'Canlı Operasyon')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Canlı Operasyon</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Bugün vardiyası olan tüm kuryeleri durumlarına göre takip edin.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('shift-planning.index') }}">
                <x-ui.button type="button" variant="secondary">Vardiya Planlama</x-ui.button>
            </a>
        </div>
    </div>

    @if ($board['cards'] === [])
        <x-ui.card>
            <p class="py-12 text-center text-sm text-gray-500 dark:text-slate-400">
                Bugün aktif vardiya bulunmuyor.
            </p>
        </x-ui.card>
    @else
        <div class="live-ops-courier-grid">
            @foreach ($board['cards'] as $card)
                <div class="live-ops-card live-ops-card--{{ $card['bucket'] }}">
                    <span class="live-ops-badge live-ops-badge--{{ $card['bucket'] }}">
                        {{ $card['bucket_label'] }}
                    </span>

                    <div class="live-ops-card__body">
                        <p class="truncate text-sm font-semibold text-gray-900" title="{{ $card['courier_name'] }}">
                            {{ $card['courier_name'] }}
                        </p>
                        <p class="truncate text-[11px] text-gray-600" title="{{ $card['phone'] }}">
                            {{ $card['phone'] }}
                        </p>
                        <p class="truncate text-[11px] text-gray-600" title="{{ $card['business_name'] }}">
                            {{ $card['business_name'] }}
                        </p>
                        @if (! empty($card['business_location']))
                            <p class="truncate text-[11px] text-gray-500" title="{{ $card['business_location'] }}">
                                {{ $card['business_location'] }}
                            </p>
                        @endif

                        <div class="mt-3 space-y-1">
                            <p class="text-xs text-gray-600">{{ $card['time_range'] }}</p>
                        </div>

                        @if ($card['attendance'] && in_array($card['bucket'], ['active', 'late_start', 'completed'], true))
                            <p class="mt-2 text-[11px] text-gray-600">
                                @if ($card['attendance']['status'] === 'in_progress')
                                    Başladı: {{ $card['attendance']['started_at_formatted'] }}
                                @else
                                    {{ $card['attendance']['worked_duration_label'] }}
                                    @if (($card['attendance']['earnings_formatted'] ?? '—') !== '—')
                                        · {{ $card['attendance']['earnings_formatted'] }}
                                    @endif
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
