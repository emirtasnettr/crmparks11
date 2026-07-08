@extends('layouts.app')

@section('title', $business['company_name'].' — Düzenle')

@section('breadcrumb')
    <a href="{{ route('businesses.index') }}" class="hover:text-gray-900 dark:hover:text-white">İşletmeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('businesses.show', $business['id']) }}" class="hover:text-gray-900 dark:hover:text-white">{{ $business['company_name'] }}</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Düzenle</span>
@endsection

@section('content')
<div
    x-data="businessForm(@js($districtsByCity), @js($formValues), true, @js(\App\Modules\Business\Support\BusinessFeatures::earningsEnabled()))"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">İşletmeyi Düzenle</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $business['company_name'] }} kaydını güncelleyin.
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
