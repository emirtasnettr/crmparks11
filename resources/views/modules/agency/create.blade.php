@extends('layouts.app')

@section('title', 'Yeni Acente')

@section('breadcrumb')
    <a href="{{ route('agencies.index') }}" class="hover:text-gray-900 dark:hover:text-white">Acenteler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Yeni Acente</span>
@endsection

@section('content')
<div
    x-data="agencyForm(@js($districtsByCity))"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni Acente</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sisteme yeni bir acente kaydı oluşturun.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('agencies.index') }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="agency-form">
                Kaydet
            </x-ui.button>
        </div>
    </div>

    @include('modules.agency.partials.form', [
        'formAction' => route('agencies.store'),
        'formMethod' => 'POST',
        'isEdit' => false,
        'cities' => $cities,
        'statuses' => $statuses,
        'paymentPeriods' => $paymentPeriods,
        'banks' => $banks,
    ])
</div>
@endsection
