<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Simtabi\Laranail\Package\Scaffolder\Commands\Concerns\WarnsOnDeprecatedAlias;

/**
 * The bare `module:*` and `make:artifact` names are deprecated aliases of the
 * vendor-scoped `laranail::package-scaffolder.*` commands: still registered,
 * still runnable, but they warn, and nothing in the package calls them.
 */
class DeprecatedAliasTest extends BaseTestCase
{
    private const string SCOPED_PREFIX = 'laranail::package-scaffolder.';

    /** Measured 2026-10-04: 70 commands, 69 `module:*` aliases + `make:artifact`. */
    private const int COMMAND_FLOOR = 70;

    public function test_every_package_command_warns_when_invoked_by_a_bare_alias(): void
    {
        $commands = $this->packageCommands();

        $this->assertGreaterThanOrEqual(self::COMMAND_FLOOR, count($commands));

        foreach ($commands as $name => $command) {
            $this->assertContains(
                WarnsOnDeprecatedAlias::class,
                class_uses_recursive($command),
                "[{$name}] does not use WarnsOnDeprecatedAlias, so its bare alias would run silently.",
            );
        }
    }

    public function test_every_bare_alias_maps_to_its_scoped_name(): void
    {
        $aliases = 0;

        foreach ($this->packageCommands() as $name => $command) {
            foreach ($command->getAliases() as $alias) {
                $aliases++;
                $expected = $alias === 'make:artifact'
                    ? self::SCOPED_PREFIX . 'new'
                    : self::SCOPED_PREFIX . substr($alias, strlen('module:'));

                $this->assertSame($expected, $name, "Alias [{$alias}] does not map to [{$expected}].");
            }
        }

        $this->assertGreaterThanOrEqual(self::COMMAND_FLOOR, $aliases);
    }

    public function test_a_bare_alias_still_runs_and_prints_a_deprecation_naming_the_scoped_command(): void
    {
        $exit = Artisan::call('module:list');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('[module:list] is a deprecated alias', $output);
        $this->assertStringContainsString('Use [laranail::package-scaffolder.list] instead.', $output);
    }

    public function test_the_scoped_name_runs_without_a_deprecation(): void
    {
        $exit = Artisan::call('laranail::package-scaffolder.list');

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('deprecated alias', Artisan::output());
    }

    public function test_make_artifact_alias_warns_before_any_validation(): void
    {
        // No name and --no-interaction: the command fails validation, but the
        // warning is printed in initialize(), before the command body runs.
        Artisan::call('make:artifact', ['--no-interaction' => true]);
        $output = Artisan::output();

        $this->assertStringContainsString('[make:artifact] is a deprecated alias', $output);
        $this->assertStringContainsString('Use [laranail::package-scaffolder.new] instead.', $output);
    }

    /**
     * Source scan, because the defect is a string in a line no test may
     * execute. Biased towards a false "used": any quoted bare name counts,
     * except the `$aliases` declarations that keep the aliases registered.
     */
    public function test_package_source_and_tests_never_call_a_bare_name(): void
    {
        $root = dirname(__DIR__, 2);
        $offenders = [];
        $inspected = ['src' => 0, 'tests' => 0];

        foreach (['src', 'tests'] as $dir) {
            $finder = Finder::create()->files()->name('*.php')->in($root . '/' . $dir)->exclude(['__snapshots__', 'stubs']);

            foreach ($finder as $file) {
                if (in_array($file->getFilename(), ['DeprecatedAliasTest.php', 'CommandNamingTest.php'], true)) {
                    continue;
                }

                $inspected[$dir]++;

                foreach (preg_split('/\R/', $file->getContents()) as $i => $line) {
                    if (preg_match('/\$aliases\s*=/', $line)) {
                        continue;
                    }

                    if (preg_match('/[\'"](module:[a-z0-9:-]+|make:artifact)\b/', $line)) {
                        $offenders[] = $dir . '/' . $file->getRelativePathname() . ':' . ($i + 1);
                    }
                }
            }
        }

        $this->assertGreaterThanOrEqual(70, $inspected['src'], 'The src/ scan matched too few files to be meaningful.');
        $this->assertGreaterThanOrEqual(50, $inspected['tests'], 'The tests/ scan matched too few files to be meaningful.');
        $this->assertSame([], $offenders, "Bare command names are still called:\n" . implode("\n", $offenders));
    }

    /**
     * @return array<string, \Symfony\Component\Console\Command\Command>
     */
    private function packageCommands(): array
    {
        $commands = [];

        foreach ($this->app[Kernel::class]->all() as $command) {
            if (str_starts_with((string) $command->getName(), self::SCOPED_PREFIX)) {
                $commands[(string) $command->getName()] = $command;
            }
        }

        return $commands;
    }
}
