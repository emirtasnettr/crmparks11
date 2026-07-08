@props(['title'])

<div
    x-show="openModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="openModal"
        x-transition.opacity
        @click="closeModal"
        class="fixed inset-0 bg-gray-900/50"
    ></div>

    <div
        x-show="openModal"
        x-transition
        {{ $attributes->merge(['class' => 'relative w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800']) }}
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
            <button
                type="button"
                @click="closeModal"
                class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 py-4">
            {{ $slot }}
        </div>
    </div>
</div>
