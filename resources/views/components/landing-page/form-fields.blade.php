@props([
    'fields' => [],
    'action' => '#',
    'hasFileField' => false,
])

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-5"
    @if ($hasFileField) enctype="multipart/form-data" @endif
>
    @csrf

    @if (session('form_success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('form_success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Lütfen formu kontrol edin:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach ($fields as $field)
        @if (($field['type'] ?? '') === 'heading')
            <div class="border-b border-gray-200 pb-3 pt-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ $field['label'] }}</h3>
                @if (! empty($field['placeholder']))
                    <p class="mt-1 text-sm text-gray-500">{{ $field['placeholder'] }}</p>
                @endif
            </div>
            @continue
        @endif

        <div @class(['w-full', ($field['width'] ?? 'full') === 'half' ? 'sm:inline-block sm:w-[calc(50%-0.625rem)] sm:align-top sm:odd:mr-5' : ''])>
            @if (($field['type'] ?? '') === 'checkbox')
                <label class="flex items-start gap-3">
                    <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked(old($field['name'])) class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500" @required($field['required'] ?? false)>
                    <span class="text-sm text-gray-700">
                        {{ $field['label'] }}
                        @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                    </span>
                </label>
            @elseif (($field['type'] ?? '') === 'radio')
                <fieldset>
                    <legend class="mb-2 block text-sm font-medium text-gray-700">
                        {{ $field['label'] }}
                        @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                    </legend>
                    <div class="space-y-2">
                        @foreach ($field['options'] ?? [] as $option)
                            @php
                                $optionValue = is_array($option) ? (string) ($option['value'] ?? $option['label'] ?? '') : (string) $option;
                                $optionLabel = is_array($option) ? (string) ($option['label'] ?? $option['value'] ?? '') : (string) $option;
                            @endphp
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" name="{{ $field['name'] }}" value="{{ $optionValue }}" @checked(old($field['name']) === $optionValue) class="border-gray-300 text-primary-600 focus:ring-primary-500">
                                {{ $optionLabel }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @elseif (($field['type'] ?? '') === 'textarea')
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                </label>
                <textarea
                    name="{{ $field['name'] }}"
                    rows="4"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    @required($field['required'] ?? false)
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                >{{ old($field['name']) }}</textarea>
            @elseif (($field['type'] ?? '') === 'select')
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                </label>
                <select
                    name="{{ $field['name'] }}"
                    @required($field['required'] ?? false)
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                >
                    <option value="">Seçiniz...</option>
                    @foreach ($field['options'] ?? [] as $option)
                        @php
                            $optionValue = is_array($option) ? (string) ($option['value'] ?? $option['label'] ?? '') : (string) $option;
                            $optionLabel = is_array($option) ? (string) ($option['label'] ?? $option['value'] ?? '') : (string) $option;
                        @endphp
                        <option value="{{ $optionValue }}" @selected(old($field['name']) === $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
            @elseif (($field['type'] ?? '') === 'file')
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                </label>
                <input
                    type="file"
                    name="{{ $field['name'] }}"
                    @required($field['required'] ?? false)
                    class="w-full rounded-xl border border-dashed border-gray-300 px-3 py-8 text-sm text-gray-500"
                >
            @else
                <label class="mb-1.5 block text-sm font-medium text-gray-700">
                    {{ $field['label'] }}
                    @if ($field['required'] ?? false)<span class="text-red-500">*</span>@endif
                </label>
                <input
                    type="{{ $field['type'] === 'phone' ? 'tel' : ($field['type'] ?? 'text') }}"
                    name="{{ $field['name'] }}"
                    value="{{ old($field['name']) }}"
                    placeholder="{{ $field['placeholder'] ?? '' }}"
                    @required($field['required'] ?? false)
                    class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                >
            @endif

            @if (! empty($field['help_text']))
                <p class="mt-1.5 text-xs text-gray-500">{{ $field['help_text'] }}</p>
            @endif
        </div>
    @endforeach

    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 sm:w-auto">
        Gönder
    </button>
</form>
