@extends('layouts.guest')

@section('title', 'Şifremi Unuttum')

@section('content')
<div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-primary-400/20 blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-indigo-400/20 blur-3xl"></div>
    </div>

    <div class="relative w-full max-w-[420px]">
        <div class="rounded-3xl border border-gray-200/80 bg-white/90 p-8 shadow-xl shadow-gray-200/50 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-none sm:p-10">
            <div class="mb-8">
                <x-app.brand-logo context="login" size="lg" />
                <p class="mt-6 text-sm font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">Şifre Sıfırlama</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Şifremi unuttum</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                    E-posta adresinizi girin, size sıfırlama bağlantısı gönderelim.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <x-ui.input
                    name="email"
                    type="email"
                    label="E-posta"
                    :value="old('email')"
                    :error="$errors->first('email')"
                    required
                    autofocus
                    autocomplete="username"
                    class="[&_input]:rounded-xl [&_input]:py-2.5"
                />

                <x-ui.button type="submit" class="w-full rounded-xl py-2.5 text-base shadow-lg shadow-primary-600/20">
                    Bağlantı Gönder
                </x-ui.button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500 dark:text-slate-400">
                <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">Giriş sayfasına dön</a>
            </p>
        </div>
    </div>
</div>
@endsection
