<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Support\Config;

use ReflectionProperty;
use Illuminate\Support\Env;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Every prefixed variable, its deprecated fallback, and where it lands.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function renamedEnvKeys(): array
    {
        return [
            'artifact namespace' => ['LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_NAMESPACE', 'ARTIFACT_DEFAULT_NAMESPACE', 'artifacts.php', 'default_namespace'],
            'artifact entity'    => ['LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_ENTITY', 'ARTIFACT_DEFAULT_ENTITY', 'artifacts.php', 'default_entity'],
            'artifact flavor'    => ['LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_FLAVOR', 'ARTIFACT_DEFAULT_FLAVOR', 'artifacts.php', 'default_flavor'],
            'module vendor'      => ['LARANAIL_PACKAGE_SCAFFOLDER_MODULE_VENDOR', 'MODULE_VENDOR', 'config.php', 'composer.vendor'],
            'module author name' => ['LARANAIL_PACKAGE_SCAFFOLDER_MODULE_AUTHOR_NAME', 'MODULE_AUTHOR_NAME', 'config.php', 'composer.author.name'],
            'module author mail' => ['LARANAIL_PACKAGE_SCAFFOLDER_MODULE_AUTHOR_EMAIL', 'MODULE_AUTHOR_EMAIL', 'config.php', 'composer.author.email'],
        ];
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

    #[DataProvider('renamedEnvKeys')]
    public function test_each_deprecated_env_name_is_read_when_the_prefixed_one_is_unset(string $prefixed, string $legacy, string $file, string $key): void
    {
        $this->setEnv($legacy, 'from-legacy');

        $this->assertSame('from-legacy', data_get($this->load($file), $key), "[{$legacy}] is no longer read as a fallback for [{$key}].");
    }

    #[DataProvider('renamedEnvKeys')]
    public function test_each_prefixed_env_name_wins_over_its_deprecated_fallback(string $prefixed, string $legacy, string $file, string $key): void
    {
        $this->setEnv($legacy, 'from-legacy');
        $this->setEnv($prefixed, 'from-prefixed');

        $this->assertSame('from-prefixed', data_get($this->load($file), $key), "[{$prefixed}] does not take precedence for [{$key}].");
    }

    /**
     * Source scan of every env() call in config/, so a variable added later
     * cannot ship unprefixed, and a fallback cannot exist without a row in
     * renamedEnvKeys() exercising it.
     */
    public function test_every_env_variable_in_config_is_prefixed_or_a_tested_fallback(): void
    {
        // Set by Laravel Vapor, not owned by this package.
        $foreign = ['VAPOR_MAINTENANCE_MODE'];

        $pairs = [];
        foreach (self::renamedEnvKeys() as [$prefixed, $legacy]) {
            $pairs[$prefixed] = $legacy;
        }

        $inspected = 0;
        $offenders = [];

        foreach (glob(dirname(__DIR__, 3) . '/config/*.php') ?: [] as $path) {
            $source = (string) file_get_contents($path);

            preg_match_all("/env\\(\\s*'([A-Z0-9_]+)'(?:\\s*,\\s*env\\(\\s*'([A-Z0-9_]+)')?/", $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $inspected++;
                $name = $match[1];
                $fallback = $match[2] ?? '';

                if (in_array($name, $foreign, true)) {
                    continue;
                }

                if (! str_starts_with($name, 'LARANAIL_PACKAGE_SCAFFOLDER_')) {
                    $offenders[] = basename($path) . ": [{$name}] is neither vendor-prefixed nor a nested fallback";

                    continue;
                }

                if ($fallback !== '' && ($pairs[$name] ?? null) !== $fallback) {
                    $offenders[] = basename($path) . ": fallback [{$fallback}] for [{$name}] has no renamedEnvKeys() row";
                }
            }
        }

        // Measured 2026-10-06: 6 prefixed reads in config/ plus VAPOR_MAINTENANCE_MODE.
        $this->assertGreaterThanOrEqual(7, $inspected, 'The config/ scan matched too few env() calls to be meaningful.');
        $this->assertSame([], $offenders, implode("\n", $offenders));
    }

    /**
     * Laravel keys a publishable file by its source path, so one source under
     * two of this package's tags would publish wherever the last tag pointed it.
     */
    public function test_no_two_publish_tags_share_a_source_path(): void
    {
        $owner = [];
        $collisions = [];
        $groups = array_values(array_filter(
            ServiceProvider::publishableGroups(),
            static fn (string $group): bool => str_starts_with($group, 'laranail::package-scaffolder-'),
        ));

        foreach ($groups as $group) {
            foreach (array_keys(ServiceProvider::pathsToPublish(null, $group)) as $source) {
                $source = realpath($source) ?: $source;

                if (isset($owner[$source])) {
                    $collisions[] = "{$source} is published by [{$owner[$source]}] and [{$group}]";
                }

                $owner[$source] = $group;
            }
        }

        // Measured 2026-10-06: config, artifacts, stubs, vite.
        $this->assertGreaterThanOrEqual(4, count($groups), 'Too few package publish tags found to be meaningful.');
        $this->assertSame([], $collisions, implode("\n", $collisions));
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
