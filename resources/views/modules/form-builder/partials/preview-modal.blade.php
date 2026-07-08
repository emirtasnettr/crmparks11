<div
    x-show="previewOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div x-show="previewOpen" x-transition.opacity @click="previewOpen = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

    <div
        x-show="previewOpen"
        x-transition
        class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl border border-gray-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
    >
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white/95 px-6 py-4 backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Form Önizleme</h3>
                <p class="text-sm text-gray-500 dark:text-slate-400" x-text="meta.name"></p>
            </div>
            <button type="button" @click="previewOpen = false" class="rounded-xl p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="space-y-5 p-6">
            <template x-for="field in fields" :key="'preview-' + field.id">
                <div :class="field.width === 'half' ? 'sm:inline-block sm:w-[calc(50%-0.5rem)] sm:align-top sm:even:ml-4' : 'w-full'">
                    <template x-if="field.type === 'heading'">
                        <div class="border-b border-gray-200 pb-2 dark:border-slate-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white" x-text="field.label"></h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400" x-show="field.placeholder" x-text="field.placeholder"></p>
                        </div>
                    </template>
                    <template x-if="field.type !== 'heading'">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">
                                <span x-text="field.label"></span>
                                <span x-show="field.required" class="text-red-500">*</span>
                            </label>
                            <template x-if="field.type === 'textarea'">
                                <textarea disabled :placeholder="field.placeholder" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800" rows="4"></textarea>
                            </template>
                            <template x-if="field.type === 'select'">
                                <select disabled class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                                    <option>Seçiniz...</option>
                                    <template x-for="option in field.options" :key="option">
                                        <option x-text="option"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="field.type === 'checkbox'">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                                    <input type="checkbox" disabled class="rounded border-gray-300">
                                    <span x-text="field.label"></span>
                                </label>
                            </template>
                            <template x-if="field.type === 'radio'">
                                <div class="space-y-2">
                                    <template x-for="option in field.options" :key="option">
                                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                                            <input type="radio" disabled class="border-gray-300">
                                            <span x-text="option"></span>
                                        </label>
                                    </template>
                                </div>
                            </template>
                            <template x-if="field.type === 'file'">
                                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-400 dark:border-slate-600">Dosya seçin</div>
                            </template>
                            <template x-if="!['textarea','select','checkbox','radio','file'].includes(field.type)">
                                <input disabled :type="field.type === 'phone' ? 'tel' : field.type" :placeholder="field.placeholder" class="w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
                            </template>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-slate-400" x-show="field.help_text" x-text="field.help_text"></p>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
