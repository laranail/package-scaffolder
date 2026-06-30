<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Some\NamespacePath\Blog\Models\Post;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks access to posts that are not yet public unless the viewer is allowed
 * to preview them (e.g. the author). Apply to public-facing read routes.
 */
class EnsurePostIsPublished
{
    public function handle(Request $request, Closure $next): Response
    {
        $post = $request->route('post');

        if ($post instanceof Post && ! $post->isPublished()) {
            abort_unless(
                $request->user()?->can('view', $post) ?? false,
                Response::HTTP_NOT_FOUND,
            );
        }

        return $next($request);
    }
}
