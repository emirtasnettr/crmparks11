@extends('layouts.landing')

@section('content')
<article class="flex min-h-screen flex-col">
    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 pb-6 pt-5 sm:px-6 sm:pt-6">
        <div class="flex-1">
        @if ($page['hero_image_url'])
            <x-landing-page.hero
                :src="$page['hero_image_url']"
                :alt="$page['title'] ?: $page['name']"
                class="mb-8"
            />
        @else
            <x-landing-page.hero class="mb-8" />
        @endif

        @if ($page['title'])
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl lg:text-5xl">
                {{ $page['title'] }}
            </h1>
        @endif

        @if ($page['content'])
            <div class="landing-page-content mt-6 max-w-none">
                {!! $page['content'] !!}
            </div>
        @endif

        @if (! empty($page['form_fields']))
            <div class="mt-10 rounded-3xl border border-gray-200 bg-white p-6 shadow-xl shadow-gray-200/60 sm:p-8">
                <x-landing-page.form-fields
                    :fields="$page['form_fields']"
                    :action="route('landing.submit', $page['slug'])"
                    :has-file-field="$hasFileField ?? false"
                />
            </div>
        @endif
        </div>

        <x-landing-page.footer />
    </div>
</article>
@endsection
