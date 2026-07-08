@props([
    'label' => null,
    'name',
    'accept' => 'image/png,image/jpeg,image/jpg,image/webp',
    'error' => null,
    'maxSizeMb' => 2,
    'hint' => null,
    'currentUrl' => null,
])

@php
    $hint = $hint ?? (str_contains($accept, 'image') || str_contains($accept, '.png') || str_contains($accept, '.jpg')
        ? 'PNG, JPG, WEBP (maks. '.$maxSizeMb.'MB)'
        : 'Dosya seçmek için tıklayın veya sürükleyin');
@endphp

<div
    x-data="fileUpload(@js([
        'accept' => $accept,
        'maxSizeMb' => $maxSizeMb,
        'currentUrl' => $currentUrl,
    ]))"
    {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}
>
    @if ($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ $label }}</label>
    @endif

    <div
        class="relative flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-8 transition-colors hover:border-primary-400 hover:bg-gray-50 dark:hover:border-primary-500 dark:hover:bg-slate-800/50 {{ $error ? 'border-red-300 dark:border-red-500' : 'border-gray-300 dark:border-slate-600' }}"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.fileInput.click()"
        :class="dragging ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/10' : ''"
        role="button"
        tabindex="0"
        @keydown.enter.prevent="$refs.fileInput.click()"
        @keydown.space.prevent="$refs.fileInput.click()"
    >
        <template x-if="preview">
            <div class="flex flex-col items-center gap-3">
                <img :src="preview" alt="Yüklenen dosya önizlemesi" class="max-h-24 max-w-full rounded-lg object-contain" />
                <p class="text-sm font-medium text-gray-700 dark:text-slate-300" x-text="fileName || 'Görsel yüklendi'"></p>
            </div>
        </template>

        <template x-if="!preview && fileName">
            <div class="flex flex-col items-center gap-2 text-center">
                <svg class="h-10 w-10 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-700 dark:text-slate-300" x-text="fileName"></p>
                <p class="text-xs text-gray-500 dark:text-slate-400">Dosya seçildi</p>
            </div>
        </template>

        <template x-if="!preview && !fileName">
            <div class="flex flex-col items-center text-center">
                <svg class="mb-3 h-10 w-10 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm font-medium text-gray-700 dark:text-slate-300">Dosya yüklemek için tıklayın</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">{{ $hint }}</p>
            </div>
        </template>

        <input
            x-ref="fileInput"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="hidden"
            @change="handleSelect($event)"
            @click.stop
        />
    </div>

    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0 flex-1">
            @if ($error)
                <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
            @endif
            <p x-show="localError" x-cloak class="text-sm text-red-600 dark:text-red-400" x-text="localError"></p>
        </div>

        <button
            type="button"
            x-show="fileName"
            x-cloak
            @click.stop="clear()"
            class="shrink-0 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200"
        >
            Kaldır
        </button>
    </div>
</div>
