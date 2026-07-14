@extends('layouts.app')

@section('title', 'Yeni İşletme')


@section('content')
<div
    x-data="businessForm(@js($districtsByCity), @js(['first_invoice_date' => \App\Modules\Business\Data\BusinessFormData::defaultFirstInvoiceDate()]), false, @js(\App\Modules\Business\Support\BusinessFeatures::earningsEnabled()))"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni İşletme</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                Sisteme yeni bir işletme ekleyin.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('businesses.index') }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="business-form">
                Kaydet
            </x-ui.button>
        </div>
    </div>

    @include('modules.business.partials.form', [
        'formAction' => route('businesses.store'),
        'formMethod' => 'POST',
        'isEdit' => false,
        'cities' => $cities,
        'pricingModels' => $pricingModels,
        'pricingFieldLabels' => $pricingFieldLabels,
        'earningPeriods' => $earningPeriods,
        'statuses' => $statuses,
    ])
</div>
@endsection
