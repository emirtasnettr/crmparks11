@extends('layouts.app')

@section('title', 'Atama Detayı')


@section('content')
<div class="max-w-5xl" x-data="{ openEditModal: false }">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $assignment['courier_name'] }}
                </h1>
                <x-business.assignment-status-badge :status="$assignment['work_status']" />
                <x-business.courier-type-badge :type="$assignment['courier_type']" :label="$assignment['courier_type_label']" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $assignment['business_name'] }}
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
            @can('assignment.update')
                <x-ui.button type="button" @click="openEditModal = true">Düzenle</x-ui.button>
                @if ($assignment['is_active_assignment'] ?? false)
                    <form
                        method="POST"
                        action="{{ route('businesses.assignments.terminate', $assignment['id']) }}"
                        onsubmit="return confirm('Atama sonlandırılsın mı?')"
                    >
                        @csrf
                        <x-ui.button type="submit" variant="danger">Sonlandır</x-ui.button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Kurye Bilgileri --}}
        <x-ui.card title="Kurye Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Ad Soyad</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $assignment['courier_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Telefon</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $assignment['courier_phone'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kurye Tipi</dt>
                    <dd><x-business.courier-type-badge :type="$assignment['courier_type']" :label="$assignment['courier_type_label']" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Çalışma Durumu</dt>
                    <dd><x-business.assignment-status-badge :status="$assignment['work_status']" /></dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- İşletme Bilgileri --}}
        <x-ui.card title="İşletme Bilgileri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Firma Ünvanı</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $assignment['business_name'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Marka Adı</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $assignment['business_brand'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Kayıt Durumu</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $assignment['status'] === 'active' ? 'Aktif' : 'Pasif (Arşiv)' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Atama Tarihleri --}}
        <x-ui.card title="Atama Tarihleri">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Başlangıç Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $assignment['start_date_formatted'] }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Bitiş Tarihi</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $assignment['end_date_formatted'] }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- Acente Bilgisi --}}
        <x-ui.card title="Acente Bilgisi">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-500 dark:text-slate-400">Acente</dt>
                    <dd class="text-right font-medium text-gray-900 dark:text-white">
                        {{ $assignment['agency_name'] !== '—' ? $assignment['agency_name'] : 'Esnaf Kurye — Acente yok' }}
                    </dd>
                </div>
            </dl>
        </x-ui.card>
    </div>

    @if ($assignment['notes'])
        <x-ui.card title="Notlar" class="mt-6">
            <p class="text-sm text-gray-700 dark:text-slate-300">{{ $assignment['notes'] }}</p>
        </x-ui.card>
    @endif

    <div class="mt-6">
        <x-ui.button href="{{ route('businesses.assignments.index') }}" variant="secondary">
            Listeye Dön
        </x-ui.button>
    </div>

    @can('assignment.update')
        @include('modules.business.assignments.partials.edit-modal')
    @endcan
</div>
@endsection
