@props(['currentSlug' => null])

<footer class="py-8">
    <nav class="mx-auto flex max-w-3xl flex-wrap items-center justify-center gap-x-5 gap-y-2 px-4 text-center text-xs text-gray-400 sm:text-sm">
        @foreach (\App\Modules\Policy\Data\PolicyDefinitions::all() as $policy)
            <a
                href="{{ route('policy.show', $policy['slug']) }}"
                @class([
                    'hover:text-gray-600',
                    'text-gray-600' => $currentSlug === $policy['slug'],
                ])
            >
                {{ $policy['label'] }}
            </a>
        @endforeach
    </nav>
</footer>
