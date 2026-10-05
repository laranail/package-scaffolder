<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Concerns;

use ReflectionProperty;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\WarnsOnDeprecatedAlias as SharedWarnsOnDeprecatedAlias;

/**
 * Prints a one-line deprecation warning when a command is invoked by one of
 * its aliases rather than by its vendor-scoped name.
 *
 * @deprecated since 0.1, removable in the next minor after 0.1. Use
 *             {@see SharedWarnsOnDeprecatedAlias} from laranail/console and list
 *             the old names in the command's `$deprecatedCommandAliases`. This
 *             package's own commands no longer use this trait.
 *
 * Kept as a delegate so a command outside this package that still uses it keeps
 * warning exactly as before: every plain alias it registers (Laravel's
 * `$aliases`, or `setAliases()`) is treated as deprecated, in addition to any
 * `$deprecatedCommandAliases` it declares. The warning text and the detection
 * come from the shared trait.
 */
trait WarnsOnDeprecatedAlias
{
    use SharedWarnsOnDeprecatedAlias {
        SharedWarnsOnDeprecatedAlias::deprecatedCommandAliases as sharedDeprecatedCommandAliases;
    }

    /**
     * The shared list plus every plain alias, which is what this trait warned on.
     *
     * The plain aliases are read from Symfony's own storage rather than through
     * `getAliases()`, which the shared trait builds from this method -- calling it
     * here would recurse.
     *
     * @return list<string>
     */
    public function deprecatedCommandAliases(): array
    {
        $name = $this->getName();

        /** @var array<mixed> $registered */
        $registered = (new ReflectionProperty(SymfonyCommand::class, 'aliases'))->getValue($this);

        return array_values(array_unique([
            ...$this->sharedDeprecatedCommandAliases(),
            ...array_filter(
                $registered,
                static fn (mixed $alias): bool => is_string($alias) && $alias !== '' && $alias !== $name,
            ),
        ]));
    }
}
