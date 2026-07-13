@extends('layouts.app')

@section('title', 'Zimmetler')

@section('content')
<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Zimmetler</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Kuryelere verilen ekipman zimmet kayıtları.
            </p>
        </div>
        <x-ui.button variant="secondary" href="{{ route('stock.products.index') }}">Ürünlere Dön</x-ui.button>
    </div>

    <x-ui.card :padding="false" class="mb-6">
        <form method="GET" action="{{ route('stock.assignments.index') }}" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
            <x-ui.input name="search" label="Ara" :value="$filters['search']" placeholder="Ürün veya kurye" />
            <x-ui.select
                name="status"
                label="Durum"
                :selected="$filters['status']"
                :options="collect($statuses)->prepend('Tümü', 'all')->all()"
            />
            <x-ui.select
                name="courier_id"
                label="Kurye"
                :selected="$filters['courier_id']"
                :options="collect($couriers)->mapWithKeys(fn ($c) => [(string) $c['id'] => $c['label']])->prepend('Tümü', 'all')->all()"
            />
            <div class="flex items-end">
                <x-ui.button type="submit" variant="secondary">Filtrele</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card :padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Ürün</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kurye</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Adet</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tarih</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Durum</th>
                        <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşlem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($assignments as $assignment)
                        <tr>
                            <td class="px-4 py-3 sm:px-6">
                                <a href="{{ route('stock.products.show', $assignment['product_id']) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                                    {{ $assignment['product_name'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ number_format($assignment['quantity']) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $assignment['assigned_at_formatted'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $assignment['status_label'] }}</td>
                            <td class="px-4 py-3">
                                @if ($assignment['is_assigned'])
                                    @can('stock.update')
                                        <form method="POST" action="{{ route('stock.assignments.return', $assignment['id']) }}" onsubmit="return confirm('Zimmet iade alınsın mı?')">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-400">İade Al</button>
                                        </form>
                                    @endcan
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-slate-400 sm:px-6">
                                Zimmet kaydı bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
