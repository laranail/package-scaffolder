<nav {{ $attributes->merge(['class' => 'blog__auth-links']) }}>
    @auth
        <span class="blog__auth-user">{{ auth()->user()->name ?? '' }}</span>
        @if ($logout)
            <form method="POST" action="{{ $logout }}" class="blog__auth-logout">
                @csrf
                <button type="submit">{{ __('modules/blog::blog.logout') }}</button>
            </form>
        @endif
    @else
        @if ($login)
            <a class="blog__auth-login" href="{{ $login }}">{{ __('modules/blog::blog.login') }}</a>
        @endif
        @if ($register)
            <a class="blog__auth-register" href="{{ $register }}">{{ __('modules/blog::blog.register') }}</a>
        @endif
    @endauth
</nav>
