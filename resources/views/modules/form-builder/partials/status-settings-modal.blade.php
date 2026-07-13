@props(['statuses' => []])

@php
    $colorOptions = [
        'primary' => 'Mavi',
        'success' => 'Yeşil',
        'danger' => 'Kırmızı',
        'warning' => 'Turuncu',
        'muted' => 'Gri',
    ];
@endphp

<div
    x-show="openStatusModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="openStatusModal"
        x-transition.opacity
        @click="closeStatusModal"
        class="fixed inset-0 bg-gray-900/50"
    ></div>

    <div
        x-show="openStatusModal"
        x-transition
        class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
        @click.stop
    >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Statü Ayarları</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">Başvuru durumlarını yönetin. Başvurusu olan statü silinemez.</p>
            </div>
            <button
                type="button"
                @click="closeStatusModal"
                class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-700"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 space-y-4 overflow-y-auto px-6 py-4">
            @foreach ($statuses as $status)
                <div class="rounded-xl border border-gray-200 p-4 dark:border-slate-700">
                    <form method="POST" action="{{ route('form-builder.statuses.update', $status['id']) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_140px_auto]">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Statü adı</label>
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $status['name']) }}"
                                    required
                                    maxlength="80"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-slate-400">Renk</label>
                                <select
                                    name="color"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    @foreach ($colorOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($status['color'] ?? 'muted') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <x-ui.button type="submit" variant="secondary" size="sm" class="w-full sm:w-auto">Kaydet</x-ui.button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
                            <x-form-builder.status-badge :status="$status" />
                            <span>{{ $status['submissions_count'] }} başvuru</span>
                            @if ($status['is_default'])
                                <span class="rounded-full bg-primary-50 px-2 py-0.5 font-medium text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">Varsayılan</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @unless ($status['is_default'])
                                <form method="POST" action="{{ route('form-builder.statuses.set-default', $status['id']) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Varsayılan yap</button>
                                </form>
                            @endunless
                            @if ($status['can_delete'])
                                <form
                                    method="POST"
                                    action="{{ route('form-builder.statuses.destroy', $status['id']) }}"
                                    onsubmit="return confirm('Bu statü silinsin mi?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">Sil</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 dark:text-slate-500" title="Başvurusu olan statü silinemez">Silinemez</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-slate-600">
                <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Yeni statü ekle</h4>
                <form method="POST" action="{{ route('form-builder.statuses.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_140px_auto]">
                    @csrf
                    <input
                        type="text"
                        name="name"
                        placeholder="Örn: Beklemede"
                        required
                        maxlength="80"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                    <select
                        name="color"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                        @foreach ($colorOptions as $value => $label)
                            <option value="{{ $value }}" @selected($value === 'muted')>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" size="sm">Ekle</x-ui.button>
                </form>
            </div>
        </div>
    </div>
</div>
