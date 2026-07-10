@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="font-medium text-gray-900 dark:text-white">Dashboard</span>
@endsection

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Dashboard</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
        Hoş geldiniz, {{ auth()->user()->name }}. İşte operasyon özetiniz.
    </p>
</div>

<div class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-5">
    <x-ui.stat-card title="Toplam İşletme" :value="number_format($stats['total_businesses'])" icon="building" color="primary" />
    <x-ui.stat-card title="Toplam Kurye" :value="number_format($stats['total_couriers'])" icon="courier" color="primary" />
    <x-ui.stat-card title="Toplam Acente" :value="number_format($stats['total_agencies'])" icon="agency" color="primary" />
    <x-ui.stat-card title="Aktif Kurye" :value="number_format($stats['active_couriers'])" icon="courier" color="success" />
    <x-ui.stat-card title="Pasif Kurye" :value="number_format($stats['inactive_couriers'])" icon="courier" color="secondary" class="col-span-2 md:col-span-1" />
</div>

@if ($canFinance && $finance)
    <div class="mb-2 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Finans Özeti</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400">{{ $finance['period_label'] }} dönemi</p>
        </div>
        <a href="{{ route('finance.dashboard.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
            Finans Dashboard
        </a>
    </div>

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.finance-stat-card title="Bu Ay Gelir" :value="$finance['revenue_formatted']" icon="earning" accent="success" />
        <x-ui.finance-stat-card title="Bu Ay Gider" :value="$finance['expense_formatted']" icon="chart" accent="danger" />
        <x-ui.finance-stat-card title="Net Kâr" :value="$finance['net_profit_formatted']" icon="chart" accent="primary" />
        <x-ui.finance-stat-card
            title="Bekleyen Tahsilat"
            :value="$finance['pending_collection_formatted']"
            :subtitle="$finance['pending_collection_count'].' kayıt'"
            icon="earning"
            accent="warning"
        />
        <x-ui.finance-stat-card
            title="Bekleyen Ödeme"
            :value="$finance['pending_payment_formatted']"
            :subtitle="$finance['pending_payment_count'].' kayıt'"
            icon="courier"
            accent="violet"
        />
        <x-ui.finance-stat-card
            title="Onay Bekleyen Hakediş"
            :value="number_format($finance['pending_earning_count'])"
            icon="chart"
            accent="blue"
        />
    </div>
@endif

@if (($canFinance && (count($pendingCollections) > 0 || count($pendingPayments) > 0)) || ($canEarnings && count($pendingEarnings) > 0))
    <div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        @if ($canFinance)
            <x-ui.card title="Bekleyen Tahsilatlar">
                <x-slot:actions>
                    <a href="{{ route('finance.collections.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Tümü</a>
                </x-slot:actions>

                @forelse ($pendingCollections as $item)
                    <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 border-b border-gray-100 py-3 last:border-0 last:pb-0 first:pt-0 hover:opacity-80 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $item['reference'] }} · {{ $item['due_date_formatted'] }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['amount_formatted'] }}</p>
                            <p @class(['text-xs', 'text-red-600 dark:text-red-400' => $item['is_overdue'], 'text-amber-600 dark:text-amber-400' => ! $item['is_overdue']])>
                                {{ $item['delay_label'] }}
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-slate-400">Bekleyen tahsilat yok.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Bekleyen Ödemeler">
                <x-slot:actions>
                    <a href="{{ route('finance.payments.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Tümü</a>
                </x-slot:actions>

                @forelse ($pendingPayments as $item)
                    <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 border-b border-gray-100 py-3 last:border-0 last:pb-0 first:pt-0 hover:opacity-80 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['recipient'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $item['reference'] }} · {{ $item['scheduled_date_formatted'] }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item['amount_formatted'] }}</p>
                            <p @class(['text-xs', 'text-red-600 dark:text-red-400' => $item['is_overdue'], 'text-amber-600 dark:text-amber-400' => ! $item['is_overdue']])>
                                {{ $item['delay_label'] }}
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-slate-400">Bekleyen ödeme yok.</p>
                @endforelse
            </x-ui.card>
        @endif

        @if ($canEarnings)
            <x-ui.card title="Onay Bekleyen Hakedişler">
                <x-slot:actions>
                    <a href="{{ route('businesses.earnings.index', ['status' => 'pending']) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Tümü</a>
                </x-slot:actions>

                @forelse ($pendingEarnings as $item)
                    <a href="{{ $item['url'] }}" class="flex items-start justify-between gap-3 border-b border-gray-100 py-3 last:border-0 last:pb-0 first:pt-0 hover:opacity-80 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $item['business'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-slate-400">{{ $item['courier'] }} · {{ $item['period'] }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-gray-900 dark:text-white">{{ $item['revenue_formatted'] }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-500 dark:text-slate-400">Onay bekleyen hakediş yok.</p>
                @endforelse
            </x-ui.card>
        @endif
    </div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <x-ui.card title="Son Eklenen İşletmeler">
        <x-slot:actions>
            <a href="{{ route('businesses.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                Tümünü Gör
            </a>
        </x-slot:actions>

        <ul class="divide-y divide-gray-100 dark:divide-slate-700">
            @foreach ($latestBusinesses as $business)
                <li>
                    <a href="{{ $business['url'] }}" class="flex items-center gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:opacity-80">
                        <x-ui.entity-avatar
                            :url="$business['logo_url']"
                            :initials="$business['logo']"
                            :color="$business['logo_color']"
                            :alt="$business['company_name'].' logosu'"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $business['company_name'] }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-slate-400">
                                {{ $business['location'] }} · {{ $business['pricing_model_label'] }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <x-business.status-badge :status="$business['status']" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ $business['created_at_formatted'] }}</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    <x-ui.card title="Son Eklenen Kuryeler">
        <x-slot:actions>
            <a href="{{ route('couriers.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                Tümünü Gör
            </a>
        </x-slot:actions>

        <ul class="divide-y divide-gray-100 dark:divide-slate-700">
            @foreach ($latestCouriers as $courier)
                <li>
                    <a href="{{ $courier['url'] }}" class="flex items-center gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:opacity-80">
                        @if ($courier['photo_url'])
                            <img src="{{ $courier['photo_url'] }}" alt="" class="h-10 w-10 shrink-0 rounded-full border border-gray-200 object-cover dark:border-slate-700" />
                        @else
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white {{ $courier['avatar_color'] }}">
                                {{ $courier['avatar_initials'] }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $courier['full_name'] }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-slate-400">
                                {{ $courier['type_label'] }} · {{ $courier['vehicle_type_label'] }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <x-courier.status-badge :status="$courier['status']" />
                            <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ $courier['created_at_formatted'] }}</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-ui.card>

    <x-ui.card title="Kurye Tür Dağılımı">
        @php
            $independent = collect($courierTypeDistribution['items'])->firstWhere('key', 'independent');
            $agency = collect($courierTypeDistribution['items'])->firstWhere('key', 'agency');
            $independentPct = $independent['percentage'] ?? 0;
            $agencyPct = $agency['percentage'] ?? 0;
        @endphp

        <div class="flex flex-col items-center">
            <div
                class="relative mb-6 flex h-36 w-36 items-center justify-center rounded-full"
                style="background: conic-gradient(
                    rgb(59 130 246) 0% {{ $independentPct }}%,
                    rgb(139 92 246) {{ $independentPct }}% 100%
                );"
            >
                <div class="flex h-28 w-28 flex-col items-center justify-center rounded-full border border-gray-100 bg-white shadow-inner dark:border-slate-600 dark:bg-slate-800">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($courierTypeDistribution['total']) }}</span>
                    <span class="text-xs text-gray-500 dark:text-slate-400">Toplam</span>
                </div>
            </div>

            <div class="w-full space-y-4">
                @foreach ($courierTypeDistribution['items'] as $item)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2">
                                <span @class([
                                    'h-2.5 w-2.5 rounded-full',
                                    'bg-blue-500' => $item['key'] === 'independent',
                                    'bg-violet-500' => $item['key'] === 'agency',
                                ])></span>
                                <span class="font-medium text-gray-700 dark:text-slate-300">{{ $item['label'] }}</span>
                            </div>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['count']) }}
                                <span class="font-normal text-gray-500 dark:text-slate-400">(%{{ number_format($item['percentage'], $item['percentage'] == floor($item['percentage']) ? 0 : 1, ',', '.') }})</span>
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                            <div
                                @class([
                                    'h-full rounded-full transition-all',
                                    'bg-blue-500' => $item['key'] === 'independent',
                                    'bg-violet-500' => $item['key'] === 'agency',
                                ])
                                style="width: {{ $item['percentage'] }}%"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="mt-5 text-center text-xs text-gray-500 dark:text-slate-400">
                Esnaf ve acente kurye oranları sistemdeki tüm kayıtlara göre hesaplanır.
            </p>
        </div>
    </x-ui.card>
</div>
@endsection
