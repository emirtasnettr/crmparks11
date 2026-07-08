@extends('layouts.app')

@section('title', $courier['full_name'].' — Düzenle')

@section('breadcrumb')
    <a href="{{ route('couriers.index') }}" class="hover:text-gray-900 dark:hover:text-white">Kuryeler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('couriers.show', $courier['id']) }}" class="hover:text-gray-900 dark:hover:text-white">{{ $courier['full_name'] }}</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Düzenle</span>
@endsection

@section('content')
<div
    x-data="courierForm(@js($districtsByCity), @js($formValues), true)"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kuryeyi Düzenle</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $courier['full_name'] }} kaydını güncelleyin.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('couriers.show', $courier['id']) }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="courier-form" ::disabled="submitting">
                <span x-show="!submitting">Güncelle</span>
                <span x-show="submitting" x-cloak>Güncelleniyor...</span>
            </x-ui.button>
        </div>
    </div>

    @include('modules.courier.partials.form', [
        'formAction' => route('couriers.update', $courier['id']),
        'formMethod' => 'PUT',
        'photoUrl' => $formValues['photo_url'] ?? null,
        'formValues' => $formValues,
        'cancelUrl' => route('couriers.show', $courier['id']),
        'submitLabel' => 'Güncelle',
        'isEdit' => true,
    ])
</div>
@endsection
