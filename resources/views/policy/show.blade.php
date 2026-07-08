@extends('layouts.landing')

@section('content')
<article class="flex min-h-screen flex-col">
    <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col px-4 pb-6 pt-5 sm:px-6 sm:pt-6">
        <div class="flex-1">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
            {{ $page['title'] }}
        </h1>

        @if ($page['content'])
            <div class="landing-page-content mt-6 max-w-none">
                {!! $page['content'] !!}
            </div>
        @else
            <p class="mt-6 text-sm text-gray-500">Bu sayfa henüz düzenlenmedi.</p>
        @endif
        </div>

        <x-landing-page.footer :current-slug="$page['slug']" />
    </div>
</article>
@endsection
