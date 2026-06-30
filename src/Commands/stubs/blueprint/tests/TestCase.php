<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests;

use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Some\NamespacePath\Blog\Providers\BlogServiceProvider;
use Some\NamespacePath\Blog\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return array_filter([
            class_exists(LivewireServiceProvider::class)
                ? LivewireServiceProvider::class
                : null,
            BlogServiceProvider::class,
        ]);
    }

    /**
     * Register the test-only users table. The package's own tables are loaded
     * by the service provider's runsMigrations().
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Point the package at the test user model and use the array session
        // guard so we don't need Sanctum installed for HTTP tests. The bare
        // "api"/"web" middleware avoids relying on host-defined throttlers or
        // the email-verification middleware.
        $app['config']->set('modules.blog.user_model', User::class);
        $app['config']->set('modules.blog.routes.api.middleware', ['api']);
        $app['config']->set('modules.blog.routes.api.auth_middleware', ['auth']);
        $app['config']->set('modules.blog.routes.web.middleware', ['web']);
        $app['config']->set('modules.blog.routes.web.auth_middleware', ['auth']);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Test User',
            'email' => 'user'.uniqid().'@example.test',
            'password' => 'secret',
        ], $attributes));
    }

    protected function createAdmin(array $attributes = []): User
    {
        return $this->createUser(array_merge(['is_admin' => true], $attributes));
    }
}
