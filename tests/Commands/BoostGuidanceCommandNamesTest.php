<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Symfony\Component\Finder\Finder;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Finder\SplFileInfo;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

/**
 * The Laravel Boost guidelines and skill under `resources/boost/` are read by
 * AI agents and followed literally. They must name the vendor-scoped
 * `laranail::package-scaffolder.*` commands, never the deprecated bare
 * `module:*` / `make:artifact` aliases, and every scoped name they cite must
 * exist in the live Artisan registry.
 */
class BoostGuidanceCommandNamesTest extends BaseTestCase
{
    private const string SCOPED_PREFIX = 'laranail::package-scaffolder.';

    /** Measured 2026-10-05: 1 guideline + SKILL.md + 5 rule files. */
    private const int FILE_FLOOR = 7;

    /** Measured 2026-10-05: scoped command references across those files. */
    private const int REFERENCE_FLOOR = 100;

    /**
     * A bare alias used as a command: `module:` followed by a command-name
     * character (`module:make`, `module:make-*`, `module:enable`), or
     * `make:artifact`. The bare family named as `module:*` in prose is allowed.
     */
    private const string BARE_ALIAS = '/(?<![\w.-])(?:module:[a-z{]|make:artifact\b)/';

    private const string SCOPED_REFERENCE = '/laranail::package-scaffolder\.([a-z0-9][a-z0-9:-]*)([*{]?)/';

    public function test_boost_guidance_never_instructs_a_bare_command_alias(): void
    {
        $files = $this->guidanceFiles();

        $this->assertGreaterThanOrEqual(self::FILE_FLOOR, count($files));

        foreach ($files as $file) {
            foreach (preg_split('/\R/', $file->getContents()) as $index => $line) {
                $this->assertDoesNotMatchRegularExpression(
                    self::BARE_ALIAS,
                    $line,
                    sprintf(
                        '%s:%d names a deprecated bare alias; use the %s<command> name instead.',
                        $file->getRelativePathname(),
                        $index + 1,
                        self::SCOPED_PREFIX,
                    ),
                );
            }
        }
    }

    public function test_every_scoped_command_the_guidance_names_is_registered(): void
    {
        $registered = array_keys($this->app[Kernel::class]->all());
        $references = 0;

        foreach ($this->guidanceFiles() as $file) {
            preg_match_all(self::SCOPED_REFERENCE, $file->getContents(), $matches, PREG_SET_ORDER);

            foreach ($matches as [, $command, $wildcard]) {
                $references++;
                $name = self::SCOPED_PREFIX . $command;

                if ($wildcard !== '' || $command === '') {
                    $family = array_filter($registered, static fn (string $n): bool => str_starts_with($n, $name));
                    $this->assertNotEmpty($family, "{$file->getRelativePathname()} cites [{$name}{$wildcard}], which matches no registered command.");

                    continue;
                }

                $this->assertContains($name, $registered, "{$file->getRelativePathname()} cites [{$name}], which is not a registered command.");
            }
        }

        $this->assertGreaterThanOrEqual(self::REFERENCE_FLOOR, $references);
    }

    /** @return list<SplFileInfo> */
    private function guidanceFiles(): array
    {
        $finder = Finder::create()->files()->in(dirname(__DIR__, 2) . '/resources/boost');

        return array_values(iterator_to_array($finder, false));
    }
}
