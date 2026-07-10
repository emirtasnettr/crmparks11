@extends('layouts.app')

@section('title', 'Operasyon Özeti')


@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Operasyon Özeti</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İşletme, kurye, acente ve atama sayıları.</p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.finance-stat-card title="İşletme" :value="number_format($stats['businesses'])" :subtitle="number_format($stats['active_businesses']).' aktif'" icon="building" accent="primary" />
    <x-ui.finance-stat-card title="Kurye" :value="number_format($stats['couriers'])" :subtitle="number_format($stats['active_couriers']).' aktif'" icon="courier" accent="blue" />
    <x-ui.finance-stat-card title="Acente" :value="number_format($stats['agencies'])" :subtitle="number_format($stats['active_agencies']).' aktif'" icon="agency" accent="violet" />
    <x-ui.finance-stat-card title="Aktif Atama" :value="number_format($stats['active_assignments'])" icon="assignment" accent="success" />
    <x-ui.finance-stat-card title="Bu Ay Hakediş" :value="number_format($stats['earnings_this_month'])" icon="earning" accent="warning" />
</div>
@endsection
