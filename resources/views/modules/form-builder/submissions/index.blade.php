@extends('layouts.app')

@section('title', 'Form Başvuruları')


@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Form Başvuruları</h1>
            <x-form-builder.status-badge :status="$form['status']" />
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            {{ $form['name'] }} formuna gelen başvuruları görüntüleyin ve Excel olarak dışa aktarın.
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Toplam <span class="font-medium text-gray-700 dark:text-slate-300">{{ $submissionCount }}</span> başvuru
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-ui.button href="{{ route('form-builder.edit', $form['id']) }}" variant="secondary">Formu Düzenle</x-ui.button>
        @if (count($submissions) > 0)
            <x-ui.button href="{{ route('form-builder.submissions.export', array_merge(['id' => $form['id']], request()->query())) }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Excel'e Aktar
            </x-ui.button>
        @endif
        <x-ui.button href="{{ route('form-builder.index') }}" variant="secondary">Geri</x-ui.button>
    </div>
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('form-builder.submissions.index', $form['id']) }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <x-ui.input name="search" label="Ara" placeholder="Alan değeri veya landing page" :value="$filters['search']" />
            <x-ui.input type="date" name="date_from" label="Başlangıç Tarihi" :value="$filters['date_from']" />
            <x-ui.input type="date" name="date_to" label="Bitiş Tarihi" :value="$filters['date_to']" />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('form-builder.submissions.index', $form['id']) }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">#</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Gönderim Tarihi</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Landing Page</th>
                    @foreach ($exportableFields as $field)
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">{{ $field['label'] }}</th>
                    @endforeach
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">IP</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($submissions as $submission)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white sm:px-6">{{ $submission['id'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $submission['submitted_at_formatted'] }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                            {{ $submission['landing_page_name'] ?? $submission['landing_page_slug'] ?? '—' }}
                        </td>
                        @foreach ($exportableFields as $field)
                            @php
                                $value = $submission['data'][$field['name']] ?? '—';
                                $fileUrl = $submission['data'][$field['name'].'_url'] ?? null;
                            @endphp
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">
                                @if ($field['type'] === 'file' && $fileUrl)
                                    <a href="{{ $fileUrl }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">{{ $value }}</a>
                                @else
                                    {{ $value ?: '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-gray-500 dark:text-slate-500">{{ $submission['ip_address'] ?? '—' }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <x-ui.button
                                href="{{ route('form-builder.submissions.show', [$form['id'], $submission['id']]) }}"
                                variant="secondary"
                                size="sm"
                            >
                                Görüntüle
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + count($exportableFields) }}" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">Henüz başvuru yok</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Bu forma bağlı landing page üzerinden gelen başvurular burada listelenir.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
