<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Doctor;

use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorResult;
use Some\NamespacePath\Blog\Models\Post;
use Throwable;

/**
 * Health check surfaced by `php artisan laranail::package-tools.doctor`.
 * Verifies the package is wired up correctly in the host application.
 */
class BlogDoctorCheck implements DoctorCheck
{
    public function name(): string
    {
        return 'blog:environment';
    }

    public function description(): string
    {
        return 'Verifies the Blog config is loaded, migrations are run and the user model resolves.';
    }

    public function run(): DoctorResult
    {
        if (config('modules.blog.name') === null) {
            return DoctorResult::fail('Blog config is not loaded. Run `php artisan blog:install` or publish the config.');
        }

        $userModel = config('modules.blog.user_model');
        if (! is_string($userModel) || ! class_exists($userModel)) {
            return DoctorResult::fail("Configured blog.user_model [{$userModel}] does not exist.");
        }

        try {
            if (! Schema::hasTable((new Post)->getTable())) {
                return DoctorResult::warn('Blog tables are missing. Run `php artisan migrate`.');
            }
        } catch (Throwable $e) {
            return DoctorResult::skip('Could not inspect the database: '.$e->getMessage());
        }

        return DoctorResult::pass('Blog is configured and migrated.');
    }
}
