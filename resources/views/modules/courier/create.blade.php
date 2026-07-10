@extends('layouts.app')

@section('title', 'Yeni Kurye')


@section('content')
<div
    x-data="courierForm(@js($districtsByCity))"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni Kurye</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sisteme yeni bir kurye kaydı oluşturun.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('couriers.index') }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="courier-form">
                Kaydet
            </x-ui.button>
        </div>
    </div>

    @include('modules.courier.partials.form', [
        'formAction' => route('couriers.store'),
        'formMethod' => 'POST',
        'isEdit' => false,
        'cities' => $cities,
        'courierTypes' => $courierTypes,
        'agencies' => $agencies,
        'vehicleTypes' => $vehicleTypes,
        'statuses' => $statuses,
        'banks' => $banks,
    ])
</div>
@endsection
