@extends('layouts.courier-portal')

@section('title', 'Profil')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">Profil</h1>
        <p class="mt-1 text-sm text-gray-500">
            Hesap ve iletişim bilgilerin.
        </p>
    </div>

    <x-ui.card>
        <div class="flex items-center gap-4 border-b border-gray-100 pb-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-lg font-semibold text-white">
                {{ auth()->user()->initials() }}
            </div>
            <div class="min-w-0">
                <p class="truncate text-lg font-semibold text-gray-900">{{ $courier['full_name'] }}</p>
                <p class="text-sm text-gray-500">Kurye</p>
            </div>
        </div>

        <dl class="mt-4 space-y-3 text-sm">
            <x-entity.detail-row label="Telefon" :value="$courier['phone'] ?: '—'" />
            <x-entity.detail-row label="E-Posta" :value="$courier['email'] ?: '—'" />
            <x-entity.detail-row label="Giriş E-Postası" :value="$courier['login_email'] ?: '—'" />
        </dl>
    </x-ui.card>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-ui.button type="submit" variant="danger" class="w-full">Çıkış Yap</x-ui.button>
    </form>
</div>
@endsection
