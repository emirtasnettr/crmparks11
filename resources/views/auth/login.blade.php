@extends('layouts.guest')

@section('title', 'Giriş')

@section('content')
<div class="relative min-h-screen overflow-hidden bg-slate-50 dark:bg-slate-950">
  {{-- Arka plan dekorasyonu --}}
  <div class="pointer-events-none absolute inset-0 lg:hidden">
    <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-primary-400/20 blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-indigo-400/20 blur-3xl"></div>
  </div>

  <div class="relative flex min-h-screen">
    {{-- Sol panel: marka alanı --}}
    <div class="relative hidden overflow-hidden lg:flex lg:w-[48%] xl:w-[52%]">
      @if ($branding['login_background_url'])
        <div
          class="absolute inset-0 bg-cover bg-center"
          style="background-image: url('{{ $branding['login_background_url'] }}');"
        ></div>
        <div class="absolute inset-0 bg-slate-950/70"></div>
      @else
        <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-900"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,0.18),transparent_40%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_80%,rgba(99,102,241,0.35),transparent_45%)]"></div>
      @endif

      <div class="absolute -left-16 top-24 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
      <div class="absolute -right-10 bottom-16 h-48 w-48 rounded-full bg-indigo-300/20 blur-2xl"></div>

      <div class="relative z-10 flex w-full flex-col justify-between p-12 xl:p-16">
        <div>
          <x-app.brand-logo context="login" size="xl" surface="dark" class="{{ $branding['has_login_logo'] ? '' : 'rounded-2xl bg-white/10 p-3 backdrop-blur-sm' }}" />

          @unless ($branding['has_login_logo'])
            <h1 class="mt-10 max-w-lg text-4xl font-bold tracking-tight text-white xl:text-5xl">
              {{ $branding['system_name'] }}
            </h1>
            <p class="mt-5 max-w-md text-lg leading-relaxed text-white/80">
              {{ $branding['login_description'] ?: 'Kurye operasyonlarınızı tek panelden yönetin. İşletme, kurye, acente ve finans süreçlerini bir arada takip edin.' }}
            </p>
          @endunless

          @if ($branding['login_image_url'])
            <img
              src="{{ $branding['login_image_url'] }}"
              alt="{{ $branding['system_name'] }}"
              class="mt-10 max-h-56 w-auto rounded-2xl object-contain shadow-2xl ring-1 ring-white/20"
            />
          @endif

          <div class="mt-12 grid max-w-lg gap-4 sm:grid-cols-3">
            @foreach ([
              ['icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-16 0H3m2 0h10M9 7h1m-1 4h1m4-4h1m-1 4h1', 'label' => 'İşletme Yönetimi'],
              ['icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'label' => 'Kurye Operasyonu'],
              ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Finans & Hakediş'],
            ] as $feature)
              <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 text-white">
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $feature['icon'] }}"/>
                  </svg>
                </div>
                <p class="text-sm font-medium text-white">{{ $feature['label'] }}</p>
              </div>
            @endforeach
          </div>
        </div>

        <p class="text-sm text-white/60">&copy; {{ date('Y') }} {{ $branding['system_name'] }}</p>
      </div>
    </div>

    {{-- Sağ panel: giriş formu --}}
    <div class="relative flex flex-1 flex-col">
      <div class="flex items-center justify-end p-4 sm:p-6">
        <button
          type="button"
          @click="toggle()"
          class="rounded-xl border border-gray-200 bg-white/80 p-2.5 text-gray-500 shadow-sm backdrop-blur transition hover:bg-white dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-400 dark:hover:bg-slate-800"
          title="Tema değiştir"
        >
          <svg x-show="theme !== 'dark'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
          </svg>
          <svg x-show="theme === 'dark'" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
        </button>
      </div>

      <div class="flex flex-1 items-center justify-center px-4 pb-10 sm:px-8">
        <div class="w-full max-w-[420px]">
          <div class="rounded-3xl border border-gray-200/80 bg-white/90 p-8 shadow-xl shadow-gray-200/50 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-none sm:p-10">
            <div class="mb-8 lg:hidden">
              <x-app.brand-logo context="login" size="lg" />
              @unless ($branding['has_login_logo'])
                <h2 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $branding['system_name'] }}</h2>
              @endunless
            </div>

            <div class="mb-8">
              <p class="text-sm font-semibold uppercase tracking-wider text-primary-600 dark:text-primary-400">Giriş</p>
              <h2 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $branding['welcome_text'] }}
              </h2>
              <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-slate-400">
                {{ $branding['login_title'] }}
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

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

              <x-ui.input
                name="password"
                type="password"
                label="Şifre"
                :error="$errors->first('password')"
                required
                autocomplete="current-password"
                class="[&_input]:rounded-xl [&_input]:py-2.5"
              />

              <div class="flex items-center justify-between gap-4">
                <label class="flex cursor-pointer items-center gap-2.5">
                  <input
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-slate-600 dark:bg-slate-800"
                  >
                  <span class="text-sm text-gray-600 dark:text-slate-400">Beni hatırla</span>
                </label>

                <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                  Şifremi unuttum
                </a>
              </div>

              <x-ui.button type="submit" class="w-full rounded-xl py-2.5 text-base shadow-lg shadow-primary-600/20">
                Giriş Yap
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
              </x-ui.button>
            </form>

            <div class="mt-8 rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/50">
              <div class="mb-3 flex items-center gap-2">
                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-900/40 dark:text-primary-400">
                  <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </span>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">Demo Hesaplar</p>
              </div>
              <div class="space-y-2">
                @foreach ([
                  ['email' => 'admin@crmlog.com', 'role' => 'Süper Admin'],
                  ['email' => 'mudur@crmlog.com', 'role' => 'Genel Müdür'],
                  ['email' => 'operasyon@crmlog.com', 'role' => 'Operasyon'],
                ] as $demo)
                  <div class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2.5 dark:bg-slate-900/60">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $demo['email'] }}</p>
                      <p class="text-xs text-gray-500 dark:text-slate-400">{{ $demo['role'] }} · password</p>
                    </div>
                    <button
                      type="button"
                      onclick="navigator.clipboard.writeText('{{ $demo['email'] }}')"
                      class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium text-primary-600 transition hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-900/20"
                    >
                      Kopyala
                    </button>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <p class="mt-6 text-center text-xs text-gray-400 dark:text-slate-500 lg:hidden">
            &copy; {{ date('Y') }} {{ $branding['system_name'] }}
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
