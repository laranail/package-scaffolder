<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Providers\BlogServiceProvider;

/**
 * Authorization rules for posts. Registered as a policy and surfaced through
 * gates in {@see BlogServiceProvider}.
 *
 * The host User model is referenced loosely via the Authenticatable contract
 * so the package does not hard-depend on `App\Models\User`.
 */
class PostPolicy
{
    /**
     * Grant every ability to administrators before other checks run.
     *
     * @return bool|null
     */
    public function before(Authenticatable $user, string $ability)
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Post $post): bool
    {
        if ($post->isPublished()) {
            return true;
        }

        return $user !== null && $this->owns($user, $post);
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function delete(Authenticatable $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function publish(Authenticatable $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function restore(Authenticatable $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    public function forceDelete(Authenticatable $user, Post $post): bool
    {
        return $this->owns($user, $post);
    }

    private function owns(Authenticatable $user, Post $post): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $post->author_id;
    }
}
