@extends('layouts.app')

@section('title', 'Başvuru #'.$submission['id'])


@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Başvuru #{{ $submission['id'] }}</h1>
                @if (! empty($submission['status']))
                    <x-form-builder.status-badge :status="$submission['status']" />
                @endif
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                {{ $form['name'] }} · {{ $submission['submitted_at_formatted'] }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button href="{{ route('form-applications.submissions', $form['id']) }}" variant="secondary">Başvurulara Dön</x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Başvuru Bilgileri</h2>
                <dl class="space-y-4">
                    @foreach ($submission['values'] as $field)
                        <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0 dark:border-slate-700">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">{{ $field['label'] }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                @if (($field['type'] ?? '') === 'file' && ! empty($field['url']))
                                    <a href="{{ $field['url'] }}" target="_blank" class="text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $field['value'] ?: 'Dosyayı aç' }}
                                    </a>
                                @else
                                    {{ $field['value'] !== '' ? $field['value'] : '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Notlar</h2>

                <form method="POST" action="{{ route('form-applications.notes.store', [$form['id'], $submission['id']]) }}" class="mb-6 space-y-3">
                    @csrf
                    <div>
                        <label for="note-body" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-300">Yeni not</label>
                        <textarea
                            id="note-body"
                            name="body"
                            rows="3"
                            required
                            maxlength="5000"
                            placeholder="Bu başvuru hakkında not bırakın..."
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-ui.button type="submit">Notu Kaydet</x-ui.button>
                </form>

                <div class="space-y-3">
                    @forelse ($notes as $note)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/40">
                            <div class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $note['user_name'] ?? 'Sistem' }}</p>
                                <p class="text-xs text-gray-500 dark:text-slate-400">{{ $note['created_at_formatted'] }}</p>
                            </div>
                            <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-slate-300">{{ $note['body'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-slate-400">Henüz not yok.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Özet</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="mb-1.5 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Başvuru Statüsü</dt>
                        <dd>
                            <form method="POST" action="{{ route('form-applications.status.update', [$form['id'], $submission['id']]) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <select
                                    name="form_submission_status_id"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status['id'] }}" @selected((int) ($submission['form_submission_status_id'] ?? 0) === (int) $status['id'])>
                                            {{ $status['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Form</dt>
                        <dd class="mt-1 text-gray-900 dark:text-white">{{ $form['name'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Gönderim</dt>
                        <dd class="mt-1 text-gray-900 dark:text-white">{{ $submission['submitted_at_formatted'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Landing Page</dt>
                        <dd class="mt-1 text-gray-900 dark:text-white">{{ $submission['landing_page_name'] ?? $submission['landing_page_slug'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">IP Adresi</dt>
                        <dd class="mt-1 text-gray-900 dark:text-white">{{ $submission['ip_address'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">Not sayısı</dt>
                        <dd class="mt-1 text-gray-900 dark:text-white">{{ count($notes) }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>
    </div>
</div>
@endsection
