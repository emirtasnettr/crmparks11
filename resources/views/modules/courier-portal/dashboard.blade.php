@extends('layouts.courier-portal')

@section('title', 'Vardiya')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <x-ui.card title="Vardiya">
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

    <x-ui.card title="Gelecek Vardiyalar">
        @forelse ($upcoming as $item)
            <div class="flex flex-col gap-1 border-b border-gray-100 py-4 last:border-0 last:pb-0 first:pt-0">
                <p class="text-xs font-medium uppercase tracking-wide text-primary-600">
                    {{ $item['work_date_formatted'] }}
                </p>
                <p class="font-semibold text-gray-900">
                    {{ $item['shift_name'] }}
                </p>
                <p class="text-sm text-gray-500">
                    {{ $item['business_name'] }} · {{ $item['start_time'] }}–{{ $item['end_time'] }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $item['pricing_model_label'] }}
                    @if ($item['hourly_rate'] !== null)
                        · {{ number_format($item['hourly_rate'], 2, ',', '.') }} ₺/saat
                    @endif
                </p>
            </div>
        @empty
            <p class="text-sm text-gray-500">Yaklaşan vardiya bulunmuyor.</p>
        @endforelse
    </x-ui.card>
</div>
@endsection
