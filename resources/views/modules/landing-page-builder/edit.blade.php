@extends('layouts.app')

@section('title', 'Landing Page Düzenle')

@section('breadcrumb')
    <a href="{{ route('landing-page-builder.index') }}" class="hover:text-gray-900 dark:hover:text-white">Landing Page Builder</a>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-medium text-gray-900 dark:text-white">{{ $page['name'] }}</span>
@endsection

@section('content')
<div
    x-data="landingPageBuilderPage(@js($page), @js($forms))"
    class="flex min-h-[calc(100vh-8rem)] flex-col"
>
    <form method="POST" action="{{ route('landing-page-builder.update', $page['id']) }}" enctype="multipart/form-data" @submit="syncContent">
        @csrf
        @method('PUT')

        <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Landing Page Düzenleyici</h1>
                    <x-landing-page-builder.status-badge :status="$page['status']" />
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                    Sabit şablon: görsel → başlık → metin → form. Sağda canlı önizleme.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($page['status'] === 'active' && $page['public_url'])
                    <x-ui.button href="{{ $page['public_url'] }}" variant="secondary" target="_blank">Yayında Görüntüle</x-ui.button>
                @endif
                <x-ui.button href="{{ route('landing-page-builder.index') }}" variant="secondary">Geri</x-ui.button>
                <x-ui.button type="submit">Kaydet</x-ui.button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="space-y-6">
                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Genel Bilgiler</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Sayfa Adı</label>
                            <input type="text" name="name" x-model="meta.name" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">URL Slug</label>
                            <div class="flex items-center gap-2">
                                <span class="shrink-0 text-sm text-gray-500 dark:text-slate-400">/lp/</span>
                                <input type="text" name="slug" x-model="meta.slug" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Durum</label>
                            <select name="status" x-model="meta.status" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <option value="draft">Taslak</option>
                                <option value="active">Yayında</option>
                                <option value="archived">Arşiv</option>
                            </select>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">İçerik</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Üst Görsel</label>
                            <div class="overflow-hidden rounded-2xl border border-dashed border-gray-300 bg-gray-50 dark:border-slate-600 dark:bg-slate-900/40">
                                <template x-if="heroPreview">
                                    <div class="relative w-full overflow-hidden {{ $heroSpec['aspectClass'] }}">
                                        <img :src="heroPreview" alt="Hero görsel" class="h-full w-full object-cover">
                                    </div>
                                </template>
                                <template x-if="!heroPreview">
                                    <div class="flex w-full items-center justify-center text-sm text-gray-500 dark:text-slate-400 {{ $heroSpec['aspectClass'] }}">
                                        Görsel seçilmedi
                                    </div>
                                </template>
                            </div>
                            <input type="file" name="hero_image" accept="image/png,image/jpeg,image/webp" @change="onHeroChange" class="mt-3 w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 dark:text-slate-300 dark:file:bg-primary-900/20 dark:file:text-primary-300">
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">
                                PNG, JPG veya WEBP. Önerilen görsel boyutu: <span class="font-medium text-gray-700 dark:text-slate-300">{{ $heroSpec['recommended'] }}</span> (minimum {{ $heroSpec['minimum'] }}).
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Başlık</label>
                            <input type="text" name="title" x-model="meta.title" placeholder="Sayfa başlığı" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Metin Alanı</label>
                            <input type="hidden" name="content" x-ref="contentInput" value="">
                            <div class="landing-page-editor overflow-hidden rounded-xl border border-gray-300 bg-white dark:border-slate-600 dark:bg-slate-900">
                                <div x-ref="contentEditor" class="min-h-[220px] text-sm text-gray-900 dark:text-white"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">Kalın, liste, bağlantı ve başlık gibi HTML biçimlendirmeleri desteklenir.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Form</label>
                            <select name="form_id" x-model="meta.form_id" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <option value="">Form seçiniz...</option>
                                <template x-for="form in forms" :key="form.id">
                                    <option :value="form.id" x-text="form.name + (form.status !== 'active' ? ' (Taslak)' : '')"></option>
                                </template>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400">Form Builder'da oluşturduğunuz form sayfanın altında gösterilir.</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">SEO Ayarları</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Meta Title</label>
                            <input type="text" name="meta_title" x-model="meta.meta_title" maxlength="70" placeholder="Tarayıcı sekmesinde görünen başlık" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400" x-text="(meta.meta_title?.length || 0) + ' / 70'"></p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Meta Description</label>
                            <textarea name="meta_description" x-model="meta.meta_description" maxlength="160" rows="3" placeholder="Arama motorlarında görünen açıklama" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400" x-text="(meta.meta_description?.length || 0) + ' / 160'"></p>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="xl:sticky xl:top-24 xl:self-start">
                <x-ui.card :padding="false">
                    <div class="border-b border-gray-200 px-4 py-3 dark:border-slate-700">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Canlı Önizleme</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Sabit landing page şablonu</p>
                    </div>
                    <div class="overflow-hidden rounded-b-2xl bg-white">
                        <div class="space-y-4 p-5">
                            <template x-if="heroPreview">
                                <div class="relative w-full overflow-hidden rounded-2xl bg-slate-100 {{ $heroSpec['aspectClass'] }}">
                                    <img :src="heroPreview" alt="" class="h-full w-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                                </div>
                            </template>
                            <template x-if="!heroPreview">
                                <div class="w-full rounded-2xl bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 {{ $heroSpec['aspectClass'] }}"></div>
                            </template>

                            <h3 class="text-xl font-bold text-gray-900" x-text="meta.title || 'Başlık alanı'"></h3>
                            <div class="landing-page-content" x-html="contentPreview"></div>

                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <template x-if="selectedFormFields.length > 0">
                                    <div class="space-y-3">
                                        <template x-for="field in selectedFormFields" :key="field.id">
                                            <div>
                                                <template x-if="field.type === 'heading'">
                                                    <p class="border-b border-gray-200 pb-2 text-sm font-semibold text-gray-900" x-text="field.label"></p>
                                                </template>
                                                <template x-if="field.type !== 'heading'">
                                                    <p class="mb-1 text-xs font-medium text-gray-700">
                                                        <span x-text="field.label"></span>
                                                        <span x-show="field.required" class="text-red-500">*</span>
                                                    </p>
                                                    <div class="h-9 rounded-lg border border-gray-300 bg-white"></div>
                                                </template>
                                            </div>
                                        </template>
                                        <div class="h-9 w-28 rounded-lg bg-primary-600"></div>
                                    </div>
                                </template>
                                <template x-if="selectedFormFields.length === 0">
                                    <p class="text-center text-sm text-gray-500">Form seçildiğinde alanlar burada görünür.</p>
                                </template>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </form>
</div>
@endsection
