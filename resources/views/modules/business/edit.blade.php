@extends('layouts.app')

@section('title', ($business['display_name'] ?? $business['brand_name']).' — Düzenle')


@section('content')
<div
    x-data="businessForm(@js($districtsByCity), @js($formValues), true, @js(\App\Modules\Business\Support\BusinessFeatures::earningsEnabled()), @js(route('businesses.geocode', absolute: false)), @js(route('businesses.neighborhoods', absolute: false)))"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">İşletmeyi Düzenle</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $business['display_name'] ?? $business['brand_name'] }} kaydını güncelleyin.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('businesses.show', $business['id']) }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="business-form" ::disabled="submitting">
                <span x-show="!submitting">Güncelle</span>
                <span x-show="submitting" x-cloak>Güncelleniyor...</span>
            </x-ui.button>
        </div>
    </div>

    @include('modules.business.partials.form', [
        'formAction' => route('businesses.update', $business['id']),
        'formMethod' => 'PUT',
        'logoUrl' => $formValues['logo_url'] ?? null,
        'formValues' => $formValues,
        'cancelUrl' => route('businesses.show', $business['id']),
        'submitLabel' => 'Güncelle',
        'isEdit' => true,
    ])
</div>
@endsection
