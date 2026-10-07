<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use LogicException;
use Symfony\Component\Finder\Finder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\WarnsOnDeprecatedAlias;
use Simtabi\Laranail\Package\Scaffolder\Commands\Concerns\WarnsOnDeprecatedAlias as LegacyWarnsOnDeprecatedAlias;

/**
 * The bare `module:*` and `make:artifact` names are deprecated aliases of the
 * vendor-scoped `laranail::package-scaffolder.*` commands: still registered,
 * still runnable, but they warn, and nothing in the package calls them.
 *
 * The warning comes from laranail/console's shared WarnsOnDeprecatedAlias, driven
 * by each command's `$deprecatedCommandAliases`. This package's own trait of the
 * same name is a deprecated delegate kept for commands outside the package.
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
            $this->assertNotContains(
                LegacyWarnsOnDeprecatedAlias::class,
                class_uses_recursive($command),
                "[{$name}] still uses the deprecated local WarnsOnDeprecatedAlias.",
            );
            $this->assertSame(
                $command->getAliases(),
                $command->deprecatedCommandAliases(),
                "[{$name}] registers an alias that is not declared deprecated, so it would run silently.",
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

    /**
     * On a terminal-like output (separate error stream) a bare alias adds the
     * one warning line and nothing else: same exit code, and with that line
     * removed the output is byte-identical to the scoped name's.
     *
     * The stream is pinned to the installed laranail/console. Laravel wraps
     * the terminal output in an OutputStyle before initialize() runs; v0.1.5
     * of the shared trait did not see through it, so the line landed on
     * stdout. The release carrying laranail/console#77 unwraps it and the
     * line goes to stderr, leaving stdout byte-identical to the scoped
     * name's. Which one is installed is read from the class that fix added,
     * so this asserts the right stream on either side of the upgrade and
     * fails if the fixed release regresses.
     */
    public function test_on_a_console_output_a_bare_alias_adds_only_the_warning_line(): void
    {
        [$aliasExit, $aliasOut, $aliasErr] = $this->runOnConsoleOutput('module:list');
        [$scopedExit, $scopedOut, $scopedErr] = $this->runOnConsoleOutput(self::SCOPED_PREFIX . 'list');

        $warning = 'Deprecated: [module:list] is a deprecated alias and will be removed in the next minor after 0.1. '
            . 'Use [laranail::package-scaffolder.list] instead.' . PHP_EOL;

        $this->assertSame(0, $scopedExit);
        $this->assertSame($scopedExit, $aliasExit);
        $this->assertSame(1, substr_count($aliasOut . $aliasErr, $warning), 'The warning must print exactly once.');
        $this->assertStringNotContainsString('deprecated alias', $scopedOut . $scopedErr);

        if ($this->consoleRoutesWarningToStderr()) {
            $this->assertSame($scopedOut, $aliasOut, 'stdout must be unchanged by a bare alias.');
            $this->assertSame($warning . $scopedErr, $aliasErr, 'The warning must be the first line of stderr.');

            return;
        }

        $this->assertSame($warning . $scopedOut, $aliasOut, 'Before the console fix the warning leads stdout.');
        $this->assertSame($scopedErr, $aliasErr);
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

    public function test_the_deprecated_local_trait_still_warns_on_a_plain_alias(): void
    {
        $command = new class extends IlluminateCommand
        {
            use LegacyWarnsOnDeprecatedAlias;

            protected $signature = 'acme:legacy-run';

            protected $aliases = ['legacy:run'];

            public function handle(): int
            {
                return self::SUCCESS;
            }
        };

        $this->assertSame(['legacy:run'], $command->getAliases());
        $this->assertSame(['legacy:run'], $command->deprecatedCommandAliases());

        $this->app[Kernel::class]->registerCommand($command);

        $this->assertSame(0, Artisan::call('legacy:run'));
        $this->assertStringContainsString(
            '[legacy:run] is a deprecated alias and will be removed in the next minor after 0.1. Use [acme:legacy-run] instead.',
            Artisan::output(),
        );

        $this->assertSame(0, Artisan::call('acme:legacy-run'));
        $this->assertStringNotContainsString('deprecated alias', Artisan::output());
    }

    /**
     * Source scan, because the defect is a string in a line no test may
     * execute. Biased towards a false "used": any quoted bare name counts,
     * except the `$deprecatedCommandAliases` declarations that keep the aliases registered.
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
                    if (preg_match('/\$(aliases|deprecatedCommandAliases)\s*=/', $line)) {
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
     * Whether the installed laranail/console sees through Laravel's OutputStyle
     * when it routes the warning to stderr (laranail/console#77). Named as a
     * string so static analysis does not require the class on older releases.
     */
    private function consoleRoutesWarningToStderr(): bool
    {
        return class_exists('Simtabi\\Laranail\\Console\\Tools\\Support\\ErrorOutput');
    }

    /**
     * @return array<string, Command>
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

    /**
     * Runs a command through the console kernel on an output that, like a real
     * terminal's, carries a separate error stream.
     *
     * @return array{int, string, string}
     */
    private function runOnConsoleOutput(string $command): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        $this->assertIsResource($stdout);
        $this->assertIsResource($stderr);

        $output = new class($stdout, $stderr) extends StreamOutput implements ConsoleOutputInterface
        {
            private OutputInterface $error;

            /**
             * @param resource $stdout
             * @param resource $stderr
             */
            public function __construct($stdout, $stderr)
            {
                parent::__construct($stdout, self::VERBOSITY_NORMAL, false);
                $this->error = new StreamOutput($stderr, self::VERBOSITY_NORMAL, false);
            }

            public function getErrorOutput(): OutputInterface
            {
                return $this->error;
            }

            public function setErrorOutput(OutputInterface $error): void
            {
                $this->error = $error;
            }

            public function section(): ConsoleSectionOutput
            {
                throw new LogicException('Sections are not used by these commands.');
            }
        };

        $exit = $this->app[Kernel::class]->handle(new ArrayInput(['command' => $command]), $output);

        rewind($stdout);
        rewind($stderr);

        return [$exit, (string) stream_get_contents($stdout), (string) stream_get_contents($stderr)];
    }
}
