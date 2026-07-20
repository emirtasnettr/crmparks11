@extends('layouts.app')

@section('title', 'Kontratlar')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kontratlar</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İşletme ticari kontratları (geçmiş kayıtlar korunur).</p>
    </div>
</div>

<form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
    <div>
        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300">İşletme</label>
        <select name="business_id" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            <option value="">Seçin</option>
            @foreach ($businesses as $business)
                <option value="{{ $business['id'] }}" @selected((int) $selectedBusinessId === (int) $business['id'])>{{ $business['name'] }}</option>
            @endforeach
        </select>
    </div>
    <x-ui.button type="submit" variant="secondary">Filtrele</x-ui.button>
</form>

@if (! $selectedBusinessId)
    <x-ui.card>
        <p class="py-8 text-center text-sm text-gray-500">Kontratları görmek için işletme seçin.</p>
    </x-ui.card>
@else
    <x-ui.card :padding="false">
        <div class="divide-y divide-gray-100 dark:divide-slate-700">
            @forelse ($contracts as $contract)
                <div class="flex items-start justify-between gap-3 px-4 py-3 sm:px-6">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $contract['start_date_formatted'] }} – {{ $contract['end_date_formatted'] }}
                            · {{ $contract['work_type_label'] }}
                            · {{ $contract['status_label'] }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-slate-400">
                            Alınan {{ $contract['business_amount_formatted'] }} · Verilen {{ $contract['courier_amount_formatted'] }} · Net {{ $contract['net_profit_formatted'] }}
                        </p>
                    </div>
                    <a href="{{ $contract['show_url'] }}" class="text-xs font-medium text-primary-600 hover:underline">Detay</a>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-sm text-gray-500">Kontrat yok.</p>
            @endforelse
        </div>
    </x-ui.card>
@endif
@endsection
