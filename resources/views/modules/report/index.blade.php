@extends('layouts.app')

@section('title', 'Raporlar')


@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Raporlar</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
        @if (auth()->user()?->hasRole('sales_manager'))
            Satış raporlarını görüntüleyin.
        @else
            Operasyon ve finans raporlarını görüntüleyin.
        @endif
    </p>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($reports as $report)
        <a href="{{ route($report['route']) }}" class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-primary-600">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-600/10 dark:text-primary-400">
                <x-ui.icon :name="$report['icon']" class="h-5 w-5" />
            </div>
            <h2 class="text-base font-semibold text-gray-900 group-hover:text-primary-700 dark:text-white dark:group-hover:text-primary-300">
                {{ $report['title'] }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $report['description'] }}</p>
        </a>
    @empty
        <x-ui.card class="md:col-span-2 xl:col-span-3">
            <p class="text-sm text-gray-500 dark:text-slate-400">Görüntülenebilir rapor bulunamadı.</p>
        </x-ui.card>
    @endforelse
</div>
@endsection
