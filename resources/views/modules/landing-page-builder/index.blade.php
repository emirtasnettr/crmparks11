@extends('layouts.app')

@section('title', 'Landing Page Builder')

@section('breadcrumb')
    <span class="font-medium text-gray-900 dark:text-white">Landing Page Builder</span>
@endsection

@section('content')
<div x-data="landingPageBuilderListPage()" @crmlog-action.window="handleDelete($event.detail)">
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Landing Page Builder</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Sabit şablonlu landing page'ler oluşturun, formlarınızı bağlayın ve yayınlayın.
        </p>
    </div>

    @can('landing_page.manage')
        <x-ui.button href="{{ route('landing-page-builder.create') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Yeni Landing Page
        </x-ui.button>
    @endcan
</div>

<x-ui.card :padding="false" class="mb-6">
    <form method="GET" action="{{ route('landing-page-builder.index') }}" class="p-4 sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ui.input name="search" label="Sayfa Ara" placeholder="Sayfa adı, başlık veya slug" :value="$filters['search']" />
            <x-ui.select
                name="status"
                label="Durum"
                :selected="$filters['status']"
                :options="['all' => 'Tümü', 'draft' => 'Taslak', 'active' => 'Yayında', 'archived' => 'Arşiv']"
            />
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button type="submit">Filtrele</x-ui.button>
            <x-ui.button href="{{ route('landing-page-builder.index') }}" variant="secondary">Temizle</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.card :padding="false">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50 dark:border-slate-700 dark:bg-slate-800/50">
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Sayfa Adı</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Başlık</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Form</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Son Güncelleme</th>
                    <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse ($pages as $page)
                    <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-slate-800/50">
                        <td class="px-4 py-3 sm:px-6">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $page['name'] }}</p>
                            @if (! empty($page['slug']))
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">/lp/{{ $page['slug'] }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $page['title'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $page['form_name'] ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <x-landing-page-builder.status-badge :status="$page['status']" />
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $page['updated_at_formatted'] }}</td>
                        <td class="px-4 py-3 sm:px-6">
                            @can('landing_page.manage')
                                <x-landing-page-builder.row-actions :page="$page" />
                                <form id="delete-form-{{ $page['id'] }}" method="POST" action="{{ route('landing-page-builder.destroy', $page['id']) }}" class="hidden">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">Henüz landing page yok</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İlk landing page'inizi oluşturarak başlayın.</p>
                                @can('landing_page.manage')
                                    <x-ui.button href="{{ route('landing-page-builder.create') }}" class="mt-4">Yeni Landing Page Oluştur</x-ui.button>
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
