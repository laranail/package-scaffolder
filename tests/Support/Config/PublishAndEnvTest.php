<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Support\Config;

use ReflectionProperty;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class PublishAndEnvTest extends BaseTestCase
{
    /** @var list<string> */
    private array $touched = [];

    protected function tearDown(): void
    {
        foreach ($this->touched as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }

        parent::tearDown();
    }

    public function test_both_config_files_have_a_vendor_scoped_publish_tag(): void
    {
        $groups = ServiceProvider::publishableGroups();

        $this->assertContains('laranail::package-scaffolder-config', $groups);
        $this->assertContains('laranail::package-scaffolder-artifacts', $groups);

        $paths = ServiceProvider::pathsToPublish(null, 'laranail::package-scaffolder-artifacts');

        $this->assertCount(1, $paths);
        $this->assertSame('artifacts.php', basename((string) array_key_first($paths)));
        $this->assertFileExists((string) array_key_first($paths));
        $this->assertSame(config_path('laranail/package-scaffolder/artifacts.php'), array_values($paths)[0]);
    }

    public function test_the_prefixed_env_name_wins_over_the_deprecated_one(): void
    {
        $this->setEnv('ARTIFACT_DEFAULT_FLAVOR', 'lumen');
        $this->setEnv('LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_FLAVOR', 'vanilla');
        $this->setEnv('MODULE_VENDOR', 'old-vendor');
        $this->setEnv('LARANAIL_PACKAGE_SCAFFOLDER_MODULE_VENDOR', 'new-vendor');

        $this->assertSame('vanilla', $this->load('artifacts.php')['default_flavor']);
        $this->assertSame('new-vendor', $this->load('config.php')['composer']['vendor']);
    }

    public function test_the_deprecated_env_name_still_works_as_a_fallback(): void
    {
        $this->setEnv('ARTIFACT_DEFAULT_NAMESPACE', 'Legacy');
        $this->setEnv('ARTIFACT_DEFAULT_ENTITY', 'Thing');
        $this->setEnv('MODULE_AUTHOR_NAME', 'Old Author');
        $this->setEnv('MODULE_AUTHOR_EMAIL', 'old@example.test');

        $artifacts = $this->load('artifacts.php');
        $modules = $this->load('config.php');

        $this->assertSame('Legacy', $artifacts['default_namespace']);
        $this->assertSame('Thing', $artifacts['default_entity']);
        $this->assertSame('Old Author', $modules['composer']['author']['name']);
        $this->assertSame('old@example.test', $modules['composer']['author']['email']);
    }

    public function test_defaults_apply_when_neither_name_is_set(): void
    {
        $this->assertSame('laravel', $this->load('artifacts.php')['default_flavor']);
        $this->assertSame('simtabi', $this->load('config.php')['composer']['vendor']);
    }

    private function setEnv(string $name, string $value): void
    {
        $this->touched[] = $name;
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $file): array
    {
        // Env caches its repository; rebuild it so the variables set above are seen.
        Env::enablePutenv();
        $repository = new ReflectionProperty(Env::class, 'repository');
        $repository->setValue(null, null);

        return require dirname(__DIR__, 3) . '/config/' . $file;
    }
}
