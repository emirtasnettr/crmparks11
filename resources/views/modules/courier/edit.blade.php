@extends('layouts.app')

@section('title', $courier['full_name'].' — Düzenle')


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
