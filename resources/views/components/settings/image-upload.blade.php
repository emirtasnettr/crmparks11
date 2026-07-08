@props([
    'name',
    'label',
    'currentUrl' => null,
    'accept' => 'image/png,image/svg+xml,image/webp,image/jpeg',
])

<div
    x-data="settingsImageUpload(@js($currentUrl))"
    class="space-y-2"
>
    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">{{ $label }}</label>

    <div
        class="relative flex min-h-[140px] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 p-4 transition-colors hover:border-primary-400 dark:border-slate-600 dark:bg-slate-800/50 dark:hover:border-primary-500"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)"
        :class="dragging ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/10' : ''"
        @click="$refs.fileInput.click()"
    >
        <template x-if="preview">
            <img :src="preview" alt="Önizleme" class="max-h-24 max-w-full object-contain" />
        </template>
        <template x-if="!preview">
            <svg class="mb-2 h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm font-medium text-gray-700 dark:text-slate-300">Sürükle bırak veya tıkla</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">PNG, SVG, WEBP</p>
        </template>

        <input
            x-ref="fileInput"
            type="file"
            name="{{ $name }}"
            accept="{{ $accept }}"
            class="hidden"
            @change="handleFile($event.target.files[0])"
        />
    </div>
</div>
