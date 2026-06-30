<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Some\NamespacePath\Blog\Models\Tag;

/**
 * Tags are taxonomy: anyone may read them, but only administrators may manage
 * them — mirroring {@see CategoryPolicy}. This matters for the admin panels
 * [[plugins]](Nova/Filament), [[/plugins]]where a model without a policy would otherwise default to
 * fully permitted. Adjust the `isAdmin()` convention to match your app's roles.
 */
class TagPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Tag $tag): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(Authenticatable $user, Tag $tag): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(Authenticatable $user, Tag $tag): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}
