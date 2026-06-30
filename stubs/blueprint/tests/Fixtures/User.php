<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Minimal host-application User stand-in for the test suite. The package only
 * ever references the user model through config('modules.blog.user_model'), so this
 * fixture is enough to exercise authorship and authorization.
 *
 * @property bool $is_admin
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    public function hasRole(string $role): bool
    {
        return $role === 'admin' && (bool) $this->is_admin;
    }

    /** @var array<int, string> */
    public array $tokenAbilities = ['*'];

    /** Models Sanctum: false = no access token (session/web guard) → tokenCan() is false. */
    public bool $hasToken = true;

    public function currentAccessToken(): ?object
    {
        return $this->hasToken ? (object) ['abilities' => $this->tokenAbilities] : null;
    }

    public function tokenCan(string $ability): bool
    {
        if (! $this->hasToken) {
            return false; // no token → carries no abilities (matches Sanctum)
        }

        return in_array('*', $this->tokenAbilities, true) || in_array($ability, $this->tokenAbilities, true);
    }
}
