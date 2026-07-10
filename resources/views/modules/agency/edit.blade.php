@extends('layouts.app')

@section('title', $agency['company_name'].' — Düzenle')


@section('content')
<div
    x-data="agencyForm(@js($districtsByCity), @js($formValues), true)"
    class="max-w-5xl"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Acenteyi Düzenle</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $agency['company_name'] }} kaydını güncelleyin.
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary" href="{{ route('agencies.show', $agency['id']) }}">
                İptal
            </x-ui.button>
            <x-ui.button type="submit" form="agency-form" ::disabled="submitting">
                <span x-show="!submitting">Güncelle</span>
                <span x-show="submitting" x-cloak>Güncelleniyor...</span>
            </x-ui.button>
        </div>
    </div>

    @include('modules.agency.partials.form', [
        'formAction' => route('agencies.update', $agency['id']),
        'formMethod' => 'PUT',
        'logoUrl' => $formValues['logo_url'] ?? null,
        'formValues' => $formValues,
        'cancelUrl' => route('agencies.show', $agency['id']),
        'submitLabel' => 'Güncelle',
        'isEdit' => true,
    ])
</div>
@endsection
