@extends('layouts.app')

@section('title', 'Form Builder')

@section('breadcrumb')
    <span class="font-medium text-gray-900 dark:text-white">Form Builder</span>
@endsection

@section('content')
<div x-data="formBuilderListPage()" @crmlog-action.window="handleDelete($event.detail)">
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Form Builder</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Dinamik formlar oluşturun, düzenleyin ve yönetin.
        </p>
    </div>

    @can('form_builder.manage')
        <x-ui.button href="{{ route('form-builder.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Form
        </x-ui.button>
    @endcan
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('form-builder.index') }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ui.input name="search" label="Form Ara" placeholder="Form adı veya açıklama" :value="$filters['search']" />
            <x-ui.select
                name="status"
                label="Durum"
                :selected="$filters['status']"
                :options="['all' => 'Tümü', 'draft' => 'Taslak', 'active' => 'Yayında', 'archived' => 'Arşiv']"
            />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('form-builder.index') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Form Adı</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Alan Sayısı</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başvuru</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
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
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $form['field_count'] }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('form-builder.submissions.index', $form['id']) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                {{ $form['submission_count'] ?? 0 }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <x-form-builder.status-badge :status="$form['status']" />
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $form['updated_at_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            @can('form_builder.manage')
                                <x-form-builder.row-actions :form="$form" />
                                <form id="delete-form-{{ $form['id'] }}" method="POST" action="{{ route('form-builder.destroy', $form['id']) }}" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @else
                                <span class="text-sm text-gray-400 dark:text-slate-500">—</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5h16M4 12h10M4 19h16"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">Henüz form yok</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İlk formunuzu oluşturarak başlayın.</p>
                                @can('form_builder.manage')
                                    <x-ui.button href="{{ route('form-builder.create') }}" class="mt-4">Yeni Form Oluştur</x-ui.button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
</div>
@endsection
