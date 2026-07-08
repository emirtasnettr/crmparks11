@extends('layouts.app')

@section('title', 'Sistem Ayarları')

@section('breadcrumb')
    <span class="font-medium text-gray-900 dark:text-white">Sistem Ayarları</span>
@endsection

@section('content')
<div x-data="systemSettingsPage()">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sistem Ayarları</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $categoryMeta['description'] }}</p>
        </div>

        @unless ($isReadOnly)
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="submit" :form="$isPolicySection ? 'policies-form' : 'settings-form'">Kaydet</x-ui.button>
                @unless ($isPolicySection)
                    <form method="POST" action="{{ route('settings.reset', $section) }}" onsubmit="return confirm('Bu kategorideki ayarlar varsayılana döndürülsün mü?')">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">Varsayılana Döndür</x-ui.button>
                    </form>
                @endunless
            </div>
        @endunless
    </div>

    @if (session('error'))
        <x-ui.alert type="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="flex flex-col gap-6 xl:flex-row">
        @include('modules.settings.partials.sidebar')

        <div class="min-w-0 flex-1">
            @if ($isPolicySection)
                <form
                    id="policies-form"
                    method="POST"
                    action="{{ route('policy-settings.update') }}"
                    x-data="policySettingsPage(@js($policies))"
                    @submit="syncAll"
                >
                    @csrf
                    @method('PUT')
                    @include('modules.settings.sections.policies', ['policies' => $policies])
                </form>
            @elseif ($isReadOnly)
                @include('modules.settings.sections.content')
            @else
                <form
                    id="settings-form"
                    method="POST"
                    action="{{ route('settings.update', $section) }}"
                    enctype="multipart/form-data"
                    @input="dirty = true"
                    @change="dirty = true"
                    @submit="markSaving()"
                >
                    @csrf
                    @method('PUT')
                    @include('modules.settings.sections.content')
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
