@extends('layouts.app')

@section('title', 'Form Düzenle')


@section('content')
<div
    x-data="formBuilderPage(@js($form), @js($fieldTypes))"
    class="flex min-h-[calc(100vh-8rem)] flex-col"
>
    <form method="POST" action="{{ route('form-builder.update', $form['id']) }}" @submit="syncFields">
        @csrf
        @method('PUT')
        <input type="hidden" name="fields_json" x-ref="fieldsJson" value="">

        <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Form Düzenleyici</h1>
                    <x-form-builder.status-badge :status="$form['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Sol panelden alan ekleyin, ortada sıralayın, sağda özelliklerini düzenleyin.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="button" variant="secondary" @click="previewOpen = true">Önizle</x-ui.button>
                <x-ui.button href="{{ route('form-builder.index') }}" variant="secondary">Geri</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800 md:grid-cols-3">
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Form Adı</label>
                <input type="text" name="name" x-model="meta.name" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                <select name="status" x-model="meta.status" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    <option value="draft">Taslak</option>
                    <option value="active">Yayında</option>
                    <option value="archived">Arşiv</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">Açıklama</label>
                <input type="text" name="description" x-model="meta.description" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 xl:grid-cols-12">
            {{-- Alan paleti --}}
            <div class="xl:col-span-3">
                <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Alan Paleti</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Eklemek için tıklayın</p>
                    </div>
                    <div class="max-h-[calc(100vh-18rem)] space-y-4 overflow-y-auto p-4">
                        @foreach (['basic' => 'Temel Alanlar', 'choice' => 'Seçim Alanları', 'advanced' => 'Gelişmiş', 'layout' => 'Yerleşim'] as $category => $categoryLabel)
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $categoryLabel }}</p>
                                <div class="space-y-1.5">
                                    @foreach ($fieldTypes as $type => $config)
                                        @if ($config['category'] === $category)
                                            <button
                                                type="button"
                                                @click="addField('{{ $type }}')"
                                                class="flex w-full items-center gap-3 rounded-xl border border-gray-200 px-3 py-2.5 text-left text-sm transition hover:border-primary-300 hover:bg-primary-50 dark:border-slate-600 dark:hover:border-primary-500 dark:hover:bg-primary-900/10"
                                            >
                                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-300">
                                                    <x-form-builder.field-type-icon :type="$type" class="h-4 w-4" />
                                                </span>
                                                <span class="font-medium text-gray-800 dark:text-slate-200">{{ $config['label'] }}</span>
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Tuval --}}
            <div class="xl:col-span-5">
                <div class="flex min-h-[520px] flex-col rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-slate-700">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Form İçeriği</h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400" x-text="fields.length + ' alan'"></p>
                        </div>
                    </div>

                    <div class="flex-1 space-y-3 overflow-y-auto p-4">
                        <template x-if="fields.length === 0">
                            <div class="flex h-full min-h-[360px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/80 p-8 text-center dark:border-slate-600 dark:bg-slate-900/40">
                                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </div>
                                <p class="font-medium text-gray-900 dark:text-white">Form boş</p>
                                <p class="mt-1 max-w-xs text-sm text-gray-500 dark:text-slate-400">Soldan bir alan seçerek formunuzu oluşturmaya başlayın.</p>
                            </div>
                        </template>

                        <template x-for="(field, index) in fields" :key="field.id">
                            <div
                                @click="selectField(field.id)"
                                :class="selectedId === field.id ? 'border-primary-500 ring-2 ring-primary-500/20' : 'border-gray-200 dark:border-slate-600'"
                                class="group cursor-pointer rounded-2xl border bg-gray-50/50 p-4 transition dark:bg-slate-900/40"
                            >
                                <div class="mb-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-md bg-white px-2 py-0.5 text-xs font-medium text-gray-500 shadow-sm dark:bg-slate-800 dark:text-slate-400" x-text="typeLabel(field.type)"></span>
                                            <span class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="field.label"></span>
                                            <span x-show="field.required" class="text-xs text-red-500">*</span>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-slate-400" x-text="field.name"></p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1 opacity-100 xl:opacity-0 xl:group-hover:opacity-100">
                                        <button type="button" @click.stop="moveField(index, -1)" :disabled="index === 0" class="rounded-lg p-1.5 text-gray-500 hover:bg-white disabled:opacity-30 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button" @click.stop="moveField(index, 1)" :disabled="index === fields.length - 1" class="rounded-lg p-1.5 text-gray-500 hover:bg-white disabled:opacity-30 dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                        <button type="button" @click.stop="duplicateField(index)" class="rounded-lg p-1.5 text-gray-500 hover:bg-white dark:hover:bg-slate-800">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" @click.stop="removeField(index)" class="rounded-lg p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                @include('modules.form-builder.partials.field-preview')
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Özellikler --}}
            <div class="xl:col-span-4">
                <div class="sticky top-24 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-slate-700">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Alan Özellikleri</h3>
                    </div>

                    <div class="p-4" x-show="selectedField">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Etiket</label>
                                <input type="text" x-model="selectedField.label" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Alan Adı</label>
                                <input type="text" x-model="selectedField.name" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            </div>
                            <div x-show="selectedField.type !== 'heading'">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Placeholder</label>
                                <input type="text" x-model="selectedField.placeholder" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Yardım Metni</label>
                                <input type="text" x-model="selectedField.help_text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            </div>
                            <div x-show="!['heading', 'checkbox'].includes(selectedField.type)">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="selectedField.required" class="rounded border-gray-300 text-primary-600 dark:border-slate-600">
                                    <span class="text-sm text-gray-700 dark:text-slate-300">Zorunlu alan</span>
                                </label>
                            </div>
                            <div x-show="['text','email','phone','number','date','select'].includes(selectedField.type)">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Genişlik</label>
                                <select x-model="selectedField.width" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                    <option value="full">Tam Genişlik</option>
                                    <option value="half">Yarım Genişlik</option>
                                </select>
                            </div>
                            <div x-show="['select', 'radio'].includes(selectedField.type)">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Seçenekler</label>
                                <template x-for="(option, optionIndex) in selectedField.options" :key="optionIndex">
                                    <div class="mb-2 flex gap-2">
                                        <input type="text" x-model="selectedField.options[optionIndex]" class="flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                        <button type="button" @click="selectedField.options.splice(optionIndex, 1)" class="rounded-lg px-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">×</button>
                                    </div>
                                </template>
                                <button type="button" @click="selectedField.options.push('Yeni seçenek')" class="text-sm font-medium text-primary-600 dark:text-primary-400">+ Seçenek Ekle</button>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 text-center" x-show="!selectedField">
                        <p class="text-sm text-gray-500 dark:text-slate-400">Düzenlemek için bir alan seçin.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @include('modules.form-builder.partials.preview-modal')
</div>
@endsection
