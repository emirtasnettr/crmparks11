@extends('layouts.app')

@section('title', ($form['name'] ?? 'Form').' Başvuruları')


@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $form['name'] }}</h1>
            <x-form-builder.status-badge :status="$form['status']" />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Bu forma gelen başvurular. Toplam <span class="font-medium text-gray-700 dark:text-slate-300">{{ $submissionCount }}</span> başvuru
        </p>
    </div>
    <x-ui.button href="{{ route('form-applications.index') }}" variant="secondary">Formlara Dön</x-ui.button>
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('form-applications.submissions', $form['id']) }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-ui.input name="search" label="Ara" placeholder="Alan değeri veya landing page" :value="$filters['search']" />
            <x-ui.select
                name="status_id"
                label="Başvuru Statüsü"
                :selected="$filters['status_id']"
                :options="$statusFilterOptions"
            />
            <x-ui.input type="date" name="date_from" label="Başlangıç Tarihi" :value="$filters['date_from']" />
            <x-ui.input type="date" name="date_to" label="Bitiş Tarihi" :value="$filters['date_to']" />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('form-applications.submissions', $form['id']) }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">#</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Statü</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Özet</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Landing Page</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gönderim</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($submissions as $submission)
                    @php
                        $summary = collect($submission['values'] ?? [])
                            ->take(2)
                            ->map(fn ($field) => ($field['label'] ?? '').': '.($field['value'] ?: '—'))
                            ->implode(' · ');
                    @endphp
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $submission['id'] }}</td>
                        <td class="px-4 py-3">
                            @if (! empty($submission['status']))
                                <x-form-builder.status-badge :status="$submission['status']" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-slate-400">
                            <span class="line-clamp-2">{{ $summary !== '' ? $summary : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                            {{ $submission['landing_page_name'] ?? $submission['landing_page_slug'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $submission['submitted_at_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <x-ui.button
                                href="{{ route('form-applications.show', [$form['id'], $submission['id']]) }}"
                                variant="secondary"
                                size="sm"
                            >
                                Görüntüle
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <p class="font-medium text-gray-900 dark:text-white">Henüz başvuru yok</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bu forma henüz başvuru gelmemiş.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
