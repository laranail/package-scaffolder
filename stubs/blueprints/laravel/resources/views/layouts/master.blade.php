<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('modules.blog.name', 'Blog'))</title>
    @stack('head')
    <x-dynamic-component :component="$blogComponentPrefix.'::assets'" />
    @stack('styles')
</head>
<body>
    <main class="blog">
        @if (session('status'))
            <x-dynamic-component :component="$blogComponentPrefix.'::alert'" type="success">{{ session('status') }}</x-dynamic-component>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
