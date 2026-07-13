@extends('layouts.app')

@section('title', 'Dashboard')


@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Dashboard</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
        Hoş geldiniz, {{ auth()->user()->name }}.
        @if ($isSalesDashboard ?? false)
            İşte satış özetiniz.
        @else
            İşte operasyon özetiniz.
        @endif
    </p>
</div>

@if (\App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses())
<section class="mb-8">
    <style>
        @keyframes opening-soon-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.25; }
        }
        .opening-soon-blink {
            animation: opening-soon-blink 1.1s ease-in-out infinite;
        }
        @keyframes opening-overdue-blink {
            0%, 100% { background-color: rgb(255 241 242); }
            50% { background-color: rgb(254 202 202); }
        }
        @keyframes opening-overdue-blink-dark {
            0%, 100% { background-color: rgb(244 63 94 / 0.12); }
            50% { background-color: rgb(244 63 94 / 0.32); }
        }
        .opening-overdue-blink {
            animation: opening-overdue-blink 2.8s ease-in-out infinite;
        }
        .dark .opening-overdue-blink {
            animation-name: opening-overdue-blink-dark;
        }
        @keyframes opening-overdue-badge-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.45; }
        }
        .opening-overdue-badge-blink {
            animation: opening-overdue-badge-blink 2.8s ease-in-out infinite;
        }
    </style>
    <x-ui.card :padding="false">
        <div class="flex flex-col gap-1 border-b border-gray-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-[19px] font-semibold text-gray-900 dark:text-white">Açılış Aşamasındakiler</h2>
            </div>
            <a
                href="{{ route('businesses.index', ['status' => 'opening_stage']) }}"
                class="text-[17px] font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >
                Tümünü Gör
            </a>
        </div>

        @if (count($openingStageBusinesses))
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-[17px]">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50/80 dark:border-slate-700/80 dark:bg-slate-800/40">
                            <th class="px-5 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Marka Adı</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İl/İlçe</th>
                            @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                                <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">İşletmeden Alınan Tutar</th>
                            @endif
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Kuryeye Verilecek Tutar</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Planlanan Kurye</th>
                            <th class="px-4 py-3 font-medium text-gray-500 dark:text-slate-400">Tamamlanan Kurye</th>
                            <th class="px-5 py-3 font-medium text-gray-500 dark:text-slate-400 sm:px-6">Açılış Tarihi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/80">
                        @foreach ($openingStageBusinesses as $business)
                            @php
                                $couriersComplete = $business['completed_courier_count'] >= $business['planned_courier_count'];
                                $isOverdue = ! empty($business['is_opening_overdue']);
                            @endphp
                            <tr
                                @class([
                                    'transition-colors',
                                    'opening-overdue-blink' => $isOverdue,
                                    'bg-emerald-50/90 hover:bg-emerald-50 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/15' => ! $isOverdue && $couriersComplete,
                                    'bg-rose-50/90 hover:bg-rose-50 dark:bg-rose-500/10 dark:hover:bg-rose-500/15' => ! $isOverdue && ! $couriersComplete,
                                ])
                            >
                                <td class="px-5 py-3.5 sm:px-6">
                                    <a href="{{ $business['url'] }}" class="group inline-flex items-center gap-2">
                                        <span @class([
                                            'font-medium group-hover:text-primary-600 dark:group-hover:text-primary-400',
                                            'text-rose-800 dark:text-rose-200' => $isOverdue,
                                            'text-gray-900 dark:text-white' => ! $isOverdue,
                                        ])>
                                            {{ $business['brand_name'] }}
                                        </span>
                                        @if ($isOverdue)
                                            <span class="opening-overdue-badge-blink rounded-md bg-rose-100 px-1.5 py-0.5 text-[14px] font-medium text-rose-800 dark:bg-rose-500/25 dark:text-rose-200">
                                                {{ abs((int) $business['days_until_opening']) }} gün gecikti
                                            </span>
                                        @elseif ($business['is_opening_soon'])
                                            <span class="opening-soon-blink rounded-md bg-amber-100 px-1.5 py-0.5 text-[14px] font-medium text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">
                                                1 gün kaldı
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-4 py-3.5 text-gray-700 dark:text-slate-300">
                                    {{ $business['location'] }}
                                </td>
                                @if (\App\Modules\Business\Support\BusinessPricingVisibility::canViewCustomerAndNetPricing())
                                    <td class="px-4 py-3.5 tabular-nums text-gray-700 dark:text-slate-300">
                                        {{ $business['customer_amount_formatted'] }}
                                    </td>
                                @endif
                                <td class="px-4 py-3.5 tabular-nums text-gray-700 dark:text-slate-300">
                                    {{ $business['courier_amount_formatted'] }}
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-gray-700 dark:text-slate-300">
                                    {{ number_format($business['planned_courier_count']) }}
                                </td>
                                <td
                                    @class([
                                        'px-4 py-3.5 tabular-nums font-medium',
                                        'text-emerald-800 dark:text-emerald-200' => $couriersComplete,
                                        'text-rose-800 dark:text-rose-200' => ! $couriersComplete,
                                    ])
                                >
                                    {{ number_format($business['completed_courier_count']) }}
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    <span @class([
                                        'tabular-nums font-medium',
                                        'text-rose-800 dark:text-rose-200' => $isOverdue,
                                        'text-amber-800 dark:text-amber-200' => ! $isOverdue && $business['is_opening_soon'],
                                        'text-gray-900 dark:text-white' => ! $isOverdue && ! $business['is_opening_soon'],
                                    ])>
                                        {{ $business['start_date_formatted'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-5 py-10 text-center sm:px-6">
                <p class="text-[17px] text-gray-500 dark:text-slate-400">Açılış aşamasında işletme bulunmuyor.</p>
            </div>
        @endif
    </x-ui.card>
</section>
@endif

@if ($isSalesDashboard ?? false)
    <div class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
        <x-ui.stat-card title="Toplam İşletme" :value="number_format($salesStats['total_businesses'])" icon="building" color="primary" />
        <x-ui.stat-card title="Aktif İşletme" :value="number_format($salesStats['active_businesses'])" icon="building" color="success" />
        <x-ui.stat-card title="Sözleşme Aşamasında" :value="number_format($salesStats['contract_stage_businesses'])" icon="contract" color="warning" />
        <x-ui.stat-card title="Bu Ay Yeni" :value="number_format($salesStats['businesses_added_this_month'])" icon="chart" color="primary" />
    </div>

    <div class="mb-8 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <x-ui.card title="İşletme Pipeline">
            <div class="space-y-4">
                @forelse ($businessStatusDistribution['items'] as $item)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-slate-300">{{ $item['label'] }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ number_format($item['count']) }}
                                <span class="font-normal text-gray-500 dark:text-slate-400">(%{{ number_format($item['percentage'], $item['percentage'] == floor($item['percentage']) ? 0 : 1, ',', '.') }})</span>
                            </span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-slate-400">Henüz işletme kaydı yok.</p>
                @endforelse
            </div>
        </x-ui.card>

        <x-ui.card title="Son Eklenen İşletmeler">
            <x-slot:actions>
                <a href="{{ route('businesses.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    Tümünü Gör
                </a>
            </x-slot:actions>

            <ul class="divide-y divide-gray-100 dark:divide-slate-700">
                @forelse ($latestBusinesses as $business)
                    <li>
                        <a href="{{ $business['url'] }}" class="flex items-center gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:opacity-80">
                            <x-ui.entity-avatar
                                :url="$business['logo_url']"
                                :initials="$business['logo']"
                                :color="$business['logo_color']"
                                :alt="($business['display_name'] ?? $business['brand_name']).' logosu'"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-white">{{ $business['display_name'] ?? $business['brand_name'] }}</p>
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
                @empty
                    <li class="py-4 text-sm text-gray-500 dark:text-slate-400">Henüz işletme yok.</li>
                @endforelse
            </ul>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card title="Yakında Bitecek Sözleşmeler">
                <x-slot:actions>
                    <a href="{{ route('businesses.contracts.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                        Tümünü Gör
                    </a>
                </x-slot:actions>

                <ul class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($expiringContracts as $contract)
                        <li>
                            <a href="{{ $contract['url'] }}" class="flex items-start justify-between gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:opacity-80">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900 dark:text-white">{{ $contract['business_name'] }}</p>
                                    <p class="truncate text-xs text-gray-500 dark:text-slate-400">{{ $contract['title'] }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $contract['end_date_formatted'] }}</p>
                                    <p @class([
                                        'mt-0.5 text-xs',
                                        'text-rose-600 dark:text-rose-400' => $contract['is_overdue'],
                                        'text-amber-600 dark:text-amber-400' => ! $contract['is_overdue'],
                                    ])>
                                        {{ $contract['delay_label'] }}
                                    </p>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-gray-500 dark:text-slate-400">Yakın tarihte bitecek sözleşme yok.</li>
                    @endforelse
                </ul>
            </x-ui.card>

            @can('form_application.view')
                <x-ui.card title="Son Form Başvuruları">
                    <x-slot:actions>
                        <a href="{{ route('form-applications.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                            Tümünü Gör
                        </a>
                    </x-slot:actions>

                    <ul class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($latestFormSubmissions as $submission)
                            <li>
                                <a href="{{ $submission['url'] }}" class="flex items-start justify-between gap-3 py-3 transition-colors first:pt-0 last:pb-0 hover:opacity-80">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-white">{{ $submission['form_name'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">{{ $submission['submitted_at_formatted'] }}</p>
                                    </div>
                                    <div class="shrink-0">
                                        @if (! empty($submission['status']))
                                            <x-form-builder.status-badge :status="$submission['status']" />
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="py-4 text-sm text-gray-500 dark:text-slate-400">Henüz başvuru yok.</li>
                        @endforelse
                    </ul>
                </x-ui.card>
            @endcan
        </div>
    </div>
@else
<div class="mb-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
    @if (\App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses())
        <x-ui.stat-card title="Toplam İşletme" :value="number_format($stats['total_businesses'])" icon="building" color="primary" />
    @endif
    @can('courier.view')
        <x-ui.stat-card title="Toplam Kurye" :value="number_format($stats['total_couriers'])" icon="courier" color="primary" />
    @endcan
    @can('agency.view')
        <x-ui.stat-card title="Toplam Acente" :value="number_format($stats['total_agencies'])" icon="agency" color="primary" />
    @endcan
    @can('courier.view')
        <x-ui.stat-card title="Aktif Kurye" :value="number_format($stats['active_couriers'])" icon="courier" color="success" />
    @endcan
</div>

<div @class([
    'grid grid-cols-1 gap-6',
    'xl:grid-cols-3' => auth()->user()?->can('courier.view') && \App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses(),
    'xl:grid-cols-2' => auth()->user()?->can('courier.view') && ! \App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses(),
    'xl:grid-cols-1' => ! auth()->user()?->can('courier.view'),
])>
    @if (\App\Modules\Business\Support\BusinessCardVisibility::canBrowseBusinesses())
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
                            :alt="($business['display_name'] ?? $business['brand_name']).' logosu'"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-white">{{ $business['display_name'] ?? $business['brand_name'] }}</p>
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
    @endif

    @can('courier.view')
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
    @endcan
</div>
@endif
@endsection
