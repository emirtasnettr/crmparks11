@props(['name', 'label' => null])

<button
    type="button"
    @click="activeTab = '{{ $name }}'"
    :class="activeTab === '{{ $name }}'
        ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
        : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-slate-400 dark:hover:text-white'"
    {{ $attributes->merge(['class' => '-mb-px inline-flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors']) }}
>
    {{ $label ?? $slot }}
</button>
