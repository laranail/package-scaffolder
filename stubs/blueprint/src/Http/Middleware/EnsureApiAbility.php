<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opt-in Sanctum token-ability gate. Given an ability key (e.g. "write"), it
 * requires the authenticated token to carry the configured ability
 * (config('modules.blog.api.abilities.{key}')). When that config is null — the default —
 * the gate is a no-op, so plain-token / session setups are unaffected.
 */
class EnsureApiAbility
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        // Abilities live under routes.api.abilities (NOT a top-level blog.api).
        $ability = config("modules.blog.routes.api.abilities.{$key}");

        if (! is_string($ability) || $ability === '') {
            return $next($request); // gate disabled (the default)
        }

        $user = $request->user();

        if ($user === null || ! method_exists($user, 'tokenCan') || ! method_exists($user, 'currentAccessToken')) {
            return $next($request); // not a token-capable user — nothing to gate
        }

        // Only enforce when the request is actually token-authenticated. A user
        // authenticated by other means (a session / web guard) carries no access
        // token, so tokenCan() would return false and 403 a legitimate request.
        // Sanctum session auth sets a TransientToken whose can() is always true,
        // so same-domain SPA sessions still pass.
        if ($user->currentAccessToken() !== null && ! $user->tokenCan($ability)) {
            abort(Response::HTTP_FORBIDDEN, 'Missing required token ability ['.$ability.'].');
        }

        return $next($request);
    }
}
