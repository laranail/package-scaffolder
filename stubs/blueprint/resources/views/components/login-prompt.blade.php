{{-- Classless auth component. Restylable. <x-{prefix}::login-prompt message="…" /> --}}
@props(['message' => null])

@php($blogLoginRoute = config('modules.blog.ui.auth.login', 'login'))

<div {{ $attributes->merge(['class' => 'blog__login-prompt']) }}>
    <p>{{ $message ?? __('modules/blog::blog.login_to_comment') }}</p>

    @if (\Illuminate\Support\Facades\Route::has($blogLoginRoute))
        <a class="blog__login-link" href="{{ route($blogLoginRoute) }}">{{ __('modules/blog::blog.login') }}</a>
    @endif
</div>
