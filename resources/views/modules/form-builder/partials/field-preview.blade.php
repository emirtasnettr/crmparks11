<div class="pointer-events-none">
    <template x-if="field.type === 'heading'">
        <div>
            <p class="text-base font-semibold text-gray-900 dark:text-white" x-text="field.label"></p>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400" x-show="field.placeholder" x-text="field.placeholder"></p>
        </div>
    </template>

    <template x-if="field.type === 'textarea'">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300" x-text="field.label"></label>
            <div class="min-h-[88px] rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 dark:border-slate-600 dark:bg-slate-800" x-text="field.placeholder || 'Uzun metin...'"></div>
        </div>
    </template>

    <template x-if="field.type === 'checkbox'">
        <label class="flex items-start gap-3">
            <span class="mt-0.5 h-4 w-4 rounded border border-gray-300 dark:border-slate-600"></span>
            <span class="text-sm text-gray-700 dark:text-slate-300" x-text="field.label"></span>
        </label>
    </template>

    <template x-if="field.type === 'radio'">
        <div>
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300" x-text="field.label"></label>
            <template x-for="option in field.options" :key="option">
                <label class="mb-2 flex items-center gap-2 text-sm text-gray-600 dark:text-slate-400">
                    <span class="h-4 w-4 rounded-full border border-gray-300 dark:border-slate-600"></span>
                    <span x-text="option"></span>
                </label>
            </template>
        </div>
    </template>

    <template x-if="field.type === 'select'">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300" x-text="field.label"></label>
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 dark:border-slate-600 dark:bg-slate-800">Seçiniz...</div>
        </div>
    </template>

    <template x-if="field.type === 'file'">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300" x-text="field.label"></label>
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-3 py-6 text-center text-sm text-gray-400 dark:border-slate-600 dark:bg-slate-800">Dosya yükleyin</div>
        </div>
    </template>

    <template x-if="!['heading','textarea','checkbox','radio','select','file'].includes(field.type)">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-300" x-text="field.label"></label>
            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-400 dark:border-slate-600 dark:bg-slate-800" x-text="field.placeholder || typeLabel(field.type)"></div>
        </div>
    </template>

    <p class="mt-2 text-xs text-gray-400 dark:text-slate-500" x-show="field.help_text" x-text="field.help_text"></p>
</div>
