@extends('layouts.guest')

@section('title', 'Giriş')

@section('content')
@php
    $systemName = $branding['system_name'] ?? 'CRMLog';
    $welcome = $branding['welcome_text'] ?: 'Tekrar hoş geldiniz';
    $loginTitle = $branding['login_title'] ?: 'Hesabınıza giriş yapın';
    $description = $branding['login_description']
        ?: 'Kurye operasyonlarınızı tek panelden yönetin. İşletme, kurye, acente ve finans süreçlerini bir arada takip edin.';
@endphp

<div class="relative min-h-screen overflow-hidden bg-[#f3faf5]">
    <div class="relative flex min-h-screen">
        {{-- Sol panel --}}
        <aside class="relative hidden w-[54%] overflow-hidden lg:flex xl:w-[56%]">
            @if ($branding['login_background_url'])
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    style="background-image: url('{{ $branding['login_background_url'] }}');"
                ></div>
                <div class="absolute inset-0 bg-[rgba(7,21,13,0.72)]"></div>
            @else
                <div class="login-gradient-shift absolute inset-0"></div>
                <div class="login-mesh absolute inset-0"></div>
                <div class="login-grid absolute inset-0"></div>

                <div class="login-orb absolute -left-28 top-10 h-[26rem] w-[26rem] rounded-full bg-[#16B24B]/30 blur-3xl"></div>
                <div class="login-orb-alt absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-[#3dd46a]/20 blur-3xl"></div>
                <div class="login-float absolute right-[18%] top-[22%] h-28 w-28 rounded-full bg-white/10 blur-xl"></div>

                {{-- Dönen halkalar --}}
                <div class="pointer-events-none absolute left-1/2 top-1/2 h-[28rem] w-[28rem] -translate-x-1/2 -translate-y-1/2">
                    <svg class="login-spin absolute inset-0 h-full w-full text-white/15" viewBox="0 0 400 400" fill="none">
                        <circle class="login-path" cx="200" cy="200" r="150" stroke="currentColor" stroke-width="1.25"/>
                        <circle cx="200" cy="50" r="4" fill="#16B24B" fill-opacity="0.9"/>
                    </svg>
                    <svg class="login-spin-rev absolute inset-8 h-[calc(100%-4rem)] w-[calc(100%-4rem)] text-[#16B24B]/25" viewBox="0 0 400 400" fill="none">
                        <circle class="login-path" cx="200" cy="200" r="150" stroke="currentColor" stroke-width="1"/>
                        <circle cx="350" cy="200" r="3.5" fill="white" fill-opacity="0.8"/>
                    </svg>
                </div>

                {{-- Parçacıklar --}}
                <div class="pointer-events-none absolute inset-0">
                    <span class="login-dot absolute left-[18%] top-[30%] h-1.5 w-1.5 rounded-full bg-white/70"></span>
                    <span class="login-dot absolute left-[72%] top-[24%] h-2 w-2 rounded-full bg-[#16B24B]"></span>
                    <span class="login-dot absolute left-[64%] top-[58%] h-1.5 w-1.5 rounded-full bg-white/60"></span>
                    <span class="login-dot absolute left-[28%] top-[68%] h-2 w-2 rounded-full bg-[#3dd46a]/80"></span>
                    <span class="login-dot absolute left-[48%] top-[18%] h-1 w-1 rounded-full bg-white/80"></span>
                    <span class="login-dot absolute left-[82%] top-[72%] h-1.5 w-1.5 rounded-full bg-white/50"></span>
                </div>
            @endif

            <div class="relative z-10 flex w-full flex-col justify-between p-12 xl:p-16">
                <div class="login-fade-up">
                    <x-app.brand-logo
                        context="login"
                        size="xl"
                        surface="dark"
                        class="{{ $branding['has_login_logo'] ? '' : 'rounded-2xl bg-white/10 p-3 ring-1 ring-white/20 backdrop-blur-md' }}"
                    />
                </div>

                <div class="login-fade-up-2 max-w-xl">
                    @unless ($branding['has_login_logo'])
                        <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur-md">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#16B24B] opacity-60"></span>
                                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-[#16B24B]"></span>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-white/85">Operasyon paneli</span>
                        </div>

                        <h1 class="font-display text-5xl font-semibold leading-[1.05] tracking-tight text-white xl:text-6xl">
                            {{ $systemName }}
                        </h1>
                    @endunless

                    <p class="mt-6 max-w-md text-lg leading-relaxed text-white/75">
                        {{ $description }}
                    </p>

                    @if ($branding['login_image_url'])
                        <img
                            src="{{ $branding['login_image_url'] }}"
                            alt="{{ $systemName }}"
                            class="login-float mt-10 max-h-52 w-auto rounded-2xl object-contain shadow-2xl ring-1 ring-white/20"
                        />
                    @endif
                </div>

                <p class="login-fade-up-4 text-sm text-white/45">
                    &copy; {{ date('Y') }} {{ $systemName }}
                </p>
            </div>
        </aside>

        {{-- Sağ panel --}}
        <main class="relative flex flex-1 flex-col">
            <div class="pointer-events-none absolute inset-0 lg:hidden">
                <div class="login-gradient-shift absolute inset-0 opacity-95"></div>
                <div class="login-mesh absolute inset-0"></div>
            </div>
            <div class="pointer-events-none absolute inset-0 hidden overflow-hidden lg:block">
                <div class="login-orb absolute -right-24 -top-16 h-80 w-80 rounded-full bg-[#16B24B]/15 blur-3xl"></div>
                <div class="login-orb-alt absolute -bottom-20 left-10 h-72 w-72 rounded-full bg-[#16B24B]/10 blur-3xl"></div>
            </div>

            <div class="relative flex flex-1 items-center justify-center px-5 py-12 sm:px-8">
                <div class="w-full max-w-[430px]">
                    <div class="login-fade-up mb-8 lg:hidden">
                        <x-app.brand-logo context="login" size="lg" surface="dark" class="{{ $branding['has_login_logo'] ? '' : 'rounded-xl bg-white/10 p-2.5 ring-1 ring-white/20' }}" />
                        @unless ($branding['has_login_logo'])
                            <h2 class="font-display mt-5 text-3xl font-semibold tracking-tight text-white">
                                {{ $systemName }}
                            </h2>
                        @endunless
                    </div>

                    <div class="login-fade-up-1 login-panel relative rounded-[1.85rem] border border-white/80 bg-white/95 p-8 shadow-[0_28px_80px_-28px_rgba(10,40,22,0.35)] backdrop-blur-xl sm:p-10">
                        <div class="login-ring pointer-events-none absolute -right-3 -top-3 h-14 w-14 rounded-full bg-[#16B24B]/10"></div>

                        <div class="login-fade-up-2 mb-8">
                            <p class="mb-2 text-sm font-semibold text-[#16B24B]">Giriş</p>
                            <h2 class="font-display text-3xl font-semibold tracking-tight text-slate-900">
                                {{ $welcome }}
                            </h2>
                            <p class="mt-2 text-[15px] leading-relaxed text-slate-500">
                                {{ $loginTitle }}
                            </p>
                        </div>

                        @if (session('success'))
                            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <div class="login-fade-up-3">
                                <x-ui.input
                                    name="email"
                                    type="email"
                                    label="E-posta"
                                    :value="old('email')"
                                    :error="$errors->first('email')"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    class="[&_input]:rounded-xl [&_input]:border-slate-200 [&_input]:bg-[#f7fbf8] [&_input]:py-3 [&_input]:transition [&_input]:focus:border-[#16B24B] [&_input]:focus:bg-white [&_input]:focus:ring-[#16B24B]/20"
                                />
                            </div>

                            <div class="login-fade-up-4">
                                <x-ui.input
                                    name="password"
                                    type="password"
                                    label="Şifre"
                                    :error="$errors->first('password')"
                                    required
                                    autocomplete="current-password"
                                    class="[&_input]:rounded-xl [&_input]:border-slate-200 [&_input]:bg-[#f7fbf8] [&_input]:py-3 [&_input]:transition [&_input]:focus:border-[#16B24B] [&_input]:focus:bg-white [&_input]:focus:ring-[#16B24B]/20"
                                />
                            </div>

                            <div class="login-fade-up-5 flex items-center justify-between gap-4 pt-1">
                                <label class="flex cursor-pointer items-center gap-2.5">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        class="h-4 w-4 rounded border-slate-300 text-[#16B24B] focus:ring-[#16B24B]"
                                    >
                                    <span class="text-sm text-slate-600">Beni hatırla</span>
                                </label>

                                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#16B24B] transition hover:text-[#0f8f3a]">
                                    Şifremi unuttum
                                </a>
                            </div>

                            <div class="login-fade-up-5 pt-1">
                                <button
                                    type="submit"
                                    class="login-btn group inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3.5 text-base font-semibold text-white shadow-lg shadow-[#16B24B]/30 focus:outline-none focus:ring-2 focus:ring-[#16B24B] focus:ring-offset-2"
                                >
                                    Giriş Yap
                                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <p class="login-fade-up-5 mt-6 text-center text-xs text-white/70 lg:text-slate-500">
                        &copy; {{ date('Y') }} {{ $systemName }}
                    </p>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
