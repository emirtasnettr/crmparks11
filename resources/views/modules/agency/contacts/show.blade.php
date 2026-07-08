@extends('layouts.app')

@section('title', 'Yetkili Detayı')

@section('breadcrumb')
    <a href="{{ route('agencies.index') }}" class="hover:text-gray-900 dark:hover:text-white">Acenteler</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('agencies.contacts.index') }}" class="hover:text-gray-900 dark:hover:text-white">Yetkililer</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $contact['full_name'] }}</span>
@endsection

@section('content')
<div class="max-w-5xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $contact['full_name'] }}</h1>
                <x-agency.contact-status-badge :status="$contact['status']" />
                @if ($contact['is_default'])
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-600/10 dark:text-amber-400">
                        <span>⭐</span> Varsayılan
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $contact['title'] }} — {{ $contact['agency_name'] }}
            </p>
        </div>

        <div class="flex shrink-0 gap-2">
            <x-ui.button variant="secondary">Düzenle</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Yetkili Bilgileri --}}
        <x-ui.card title="Yetkili Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contact['full_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Görevi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contact['title'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Durum</dt>
                    <dd><x-agency.contact-status-badge :status="$contact['status']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Varsayılan Yetkili</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $contact['is_default'] ? 'Evet' : 'Hayır' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Bağlı Acente --}}
        <x-ui.card title="Bağlı Acente">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma Ünvanı</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $contact['agency_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kayıt No</dt>
                    <dd class="font-mono text-xs font-medium text-gray-900 dark:text-white">{{ $contact['uuid'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- İletişim Bilgileri --}}
        <x-ui.card title="İletişim Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contact['phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">E-Posta</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $contact['email'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Notlar --}}
        <x-ui.card title="Notlar">
            @if ($contact['notes'])
                <p class="text-sm text-gray-700 dark:text-slate-300">{{ $contact['notes'] }}</p>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">Not bulunmuyor.</p>
            @endif
        </x-ui.card>
    </div>

    <div class="mt-6">
        <x-ui.button href="{{ route('agencies.contacts.index') }}" variant="secondary">
            Listeye Dön
        </x-ui.button>
    </div>
</div>
@endsection
