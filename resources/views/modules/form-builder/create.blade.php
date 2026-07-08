@extends('layouts.app')

@section('title', 'Yeni Form')

@section('breadcrumb')
    <a href="{{ route('form-builder.index') }}" class="hover:text-gray-900 dark:hover:text-white">Form Builder</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">Yeni Form</span>
@endsection

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni Form Oluştur</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Forma bir isim verin; ardından alanları sürükleyip düzenleyebilirsiniz.
        </p>
    </div>

    <x-ui.card>
        <form method="POST" action="{{ route('form-builder.store') }}" class="space-y-5">
            @csrf

            <x-ui.input
                name="name"
                label="Form Adı"
                placeholder="Örn: Kurye Başvuru Formu"
                :value="old('name')"
                :error="$errors->first('name')"
                required
                autofocus
            />

            <x-ui.textarea name="description" label="Açıklama" rows="3" placeholder="Formun kullanım amacı (isteğe bağlı)">{{ old('description') }}</x-ui.textarea>

            <x-ui.select
                name="status"
                label="Durum"
                :selected="old('status', 'draft')"
                :options="['draft' => 'Taslak', 'active' => 'Yayında', 'archived' => 'Arşiv']"
            />

            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit">Oluştur ve Düzenle</x-ui.button>
                <x-ui.button href="{{ route('form-builder.index') }}" variant="secondary">İptal</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
