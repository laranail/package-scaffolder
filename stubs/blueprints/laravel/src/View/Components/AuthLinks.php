<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;

/**
 * User menu / auth links using the host's configured auth route names. Each link
 * degrades gracefully when its route is absent. <x-{prefix}::auth-links />.
 */
class AuthLinks extends Component
{
    public ?string $login;

    public ?string $register;

    public ?string $logout;

    public function __construct()
    {
        $this->login = $this->routeUrl((string) config('modules.blog.ui.auth.login', 'login'));
        $this->register = $this->routeUrl((string) config('modules.blog.ui.auth.register', 'register'));
        $this->logout = $this->routeUrl((string) config('modules.blog.ui.auth.logout', 'logout'));
    }

    private function routeUrl(string $name): ?string
    {
        return $name !== '' && Route::has($name) ? route($name) : null;
    }

    public function render(): View
    {
        return view('modules/blog::components.auth-links');
    }
}
