@props([
    'message' => 'Talebiniz alınmıştır, ilgili ekibimiz en kısa süre içerisinde sizlerle iletişime geçecektir.',
])

<div
    x-data="{
        open: true,
        close() {
            this.open = false;
        },
    }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[100] flex items-end justify-center p-0 sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="form-success-title"
    aria-describedby="form-success-message"
    @keydown.escape.window="close()"
>
    <div
        x-show="open"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/55 backdrop-blur-[2px]"
        @click="close()"
    ></div>

    <div
        x-show="open"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-4 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-2 sm:scale-95"
        class="relative z-10 w-full max-w-md rounded-t-3xl border border-gray-200/80 bg-white px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-5 shadow-2xl sm:rounded-3xl sm:px-8 sm:pb-8 sm:pt-8"
        @click.stop
    >
        <div class="mx-auto mb-5 flex h-1.5 w-10 rounded-full bg-gray-200 sm:hidden" aria-hidden="true"></div>

        <div class="flex flex-col items-center text-center">
            <div class="relative mb-5 flex h-16 w-16 items-center justify-center">
                <span class="absolute inset-0 rounded-full bg-primary-100"></span>
                <span class="relative flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-600/30">
                    <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>

            <h2 id="form-success-title" class="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">
                Talebiniz alındı
            </h2>
            <p id="form-success-message" class="mt-3 max-w-sm text-sm leading-relaxed text-gray-600 sm:text-base">
                {{ $message }}
            </p>
        </div>

        <button
            type="button"
            autofocus
            @click="close()"
            class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-primary-600 px-5 py-3.5 text-base font-semibold text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:scale-[0.99]"
        >
            Tamam
        </button>
    </div>
</div>
