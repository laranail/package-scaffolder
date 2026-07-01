<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Some\NamespacePath\Blog\Models\Category;

/**
 * Categories are taxonomy: only administrators may manage them. Adjust the
 * `isAdmin()` convention to match your application's roles.
 */
class CategoryPolicy
{
    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Category $category): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(Authenticatable $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(Authenticatable $user, Category $category): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(Authenticatable $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }
}
