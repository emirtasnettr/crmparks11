<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['meta_title'] ?: $page['title'] ?: $page['name'] }}</title>
    @if (! empty($page['meta_description']))
        <meta name="description" content="{{ $page['meta_description'] }}">
    @endif
    @if (! empty($page['hero_image_url']))
        <meta property="og:image" content="{{ url($page['hero_image_url']) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white antialiased text-gray-900">
    @yield('content')
</body>
</html>
