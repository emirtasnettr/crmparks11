@extends('layouts.app')

@section('title', 'Form Başvuruları')


@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Form Başvuruları</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
        Tüm formları ve gelen başvuruları görüntüleyin. Formlar bu ekrandan düzenlenemez.
    </p>
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('form-applications.index') }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ui.input name="search" label="Form Ara" placeholder="Form adı veya açıklama" :value="$filters['search']" />
            <x-ui.select
                name="status"
                label="Form Durumu"
                :selected="$filters['status']"
                :options="['all' => 'Tümü', 'draft' => 'Taslak', 'active' => 'Yayında', 'archived' => 'Arşiv']"
            />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('form-applications.index') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Form Adı</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başvuru</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Son Güncelleme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($forms as $form)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $form['name'] }}</p>
                            @if (! empty($form['description']))
                                <p class="mt-0.5 line-clamp-1 text-xs text-gray-500 dark:text-slate-400">{{ $form['description'] }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-form-builder.status-badge :status="$form['status']" />
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-700 dark:text-slate-300">
                            {{ $form['submission_count'] ?? 0 }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $form['updated_at_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            <x-ui.button
                                href="{{ route('form-applications.submissions', $form['id']) }}"
                                variant="secondary"
                                size="sm"
                            >
                                Başvuruları Gör
                            </x-ui.button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <p class="font-medium text-gray-900 dark:text-white">Henüz form yok</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Oluşturulan formlar burada listelenir.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
