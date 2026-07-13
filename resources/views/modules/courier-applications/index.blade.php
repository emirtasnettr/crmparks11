@extends('layouts.app')

@section('title', 'Kurye Başvuruları')


@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kurye Başvuruları</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Formlar üzerinden gelen başvuruları inceleyin, statü güncelleyin ve not bırakın.
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Toplam <span class="font-medium text-gray-700 dark:text-slate-300">{{ $submissionCount }}</span> başvuru
        </p>
    </div>
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('courier-applications.index') }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-ui.input name="search" label="Ara" placeholder="Ad, form veya landing page" :value="$filters['search']" />
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
            <x-ui.button href="{{ route('courier-applications.index') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">#</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Statü</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Form</th>
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
                        <td class="px-4 py-3 text-gray-700 dark:text-slate-300">{{ $submission['form_name'] ?? '—' }}</td>
                        <td class="max-w-xs px-4 py-3 text-gray-600 dark:text-slate-400">
                            <span class="line-clamp-2">{{ $summary !== '' ? $summary : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                            {{ $submission['landing_page_name'] ?? $submission['landing_page_slug'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $submission['submitted_at_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <x-ui.button
                                href="{{ route('courier-applications.show', $submission['id']) }}"
                                variant="secondary"
                                size="sm"
                            >
                                Görüntüle
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">Henüz başvuru yok</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Landing page formlarından gelen başvurular burada listelenir.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
