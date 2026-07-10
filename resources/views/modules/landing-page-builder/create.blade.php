@extends('layouts.app')

@section('title', 'Yeni Landing Page')


@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Yeni Landing Page Oluştur</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
            Sayfaya bir isim verin; ardından görsel, içerik ve form ayarlarını düzenleyebilirsiniz.
        </p>
    </div>

    <x-ui.card>
        <form method="POST" action="{{ route('landing-page-builder.store') }}" class="space-y-5">
            @csrf

            <x-ui.input
                name="name"
                label="Sayfa Adı"
                placeholder="Örn: Kurye Başvuru Landing Page"
                :value="old('name')"
                :error="$errors->first('name')"
                required
                autofocus
            />

            <x-ui.input
                name="slug"
                label="URL Slug"
                placeholder="kurye-basvuru (isteğe bağlı)"
                :value="old('slug')"
                :error="$errors->first('slug')"
            />
            <p class="-mt-3 text-xs text-gray-500 dark:text-slate-400">Yayınlandığında /lp/slug adresinde görünür.</p>

            <x-ui.select
                name="status"
                label="Durum"
                :selected="old('status', 'draft')"
                :options="['draft' => 'Taslak', 'active' => 'Yayında', 'archived' => 'Arşiv']"
            />

            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit">Oluştur ve Düzenle</x-ui.button>
                <x-ui.button href="{{ route('landing-page-builder.index') }}" variant="secondary">İptal</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
