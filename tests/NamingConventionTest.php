<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Package\Scaffolder\Facades\Module;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Providers\ModulesServiceProvider;

/**
 * Reads the live registries of the booted application: the container's aliases
 * and bindings, and the Artisan command map. A bare name the package owns fails
 * unless it is a declared, still-working deprecated alias.
 */
class NamingConventionTest extends BaseTestCase
{
    use AssertsRegisteredNames;

    private const string SCOPED_CONTAINER_ALIAS = 'laranail.package-scaffolder.modules';

    /** Measured 2026-10-05: 70 scoped commands, each with one deprecated bare alias. */
    private const int COMMAND_FLOOR = 70;

    public function test_the_repository_is_registered_under_the_vendor_scoped_container_alias(): void
    {
        $this->assertSame(self::SCOPED_CONTAINER_ALIAS, ModulesServiceProvider::CONTAINER_ALIAS);
        $this->assertTrue($this->app->isAlias(self::SCOPED_CONTAINER_ALIAS));
        $this->assertSame(RepositoryInterface::class, $this->app->getAlias(self::SCOPED_CONTAINER_ALIAS));
        $this->assertInstanceOf(RepositoryInterface::class, app(self::SCOPED_CONTAINER_ALIAS));
    }

    public function test_the_bare_modules_alias_still_resolves_the_same_repository(): void
    {
        $this->assertSame('modules', ModulesServiceProvider::DEPRECATED_CONTAINER_ALIAS);
        $this->assertSame(RepositoryInterface::class, $this->app->getAlias('modules'));
        $this->assertInstanceOf(RepositoryInterface::class, app('modules'));
        $this->assertSame($this->app->getAlias(self::SCOPED_CONTAINER_ALIAS), $this->app->getAlias('modules'));
    }

    public function test_the_module_facade_resolves_through_the_scoped_alias(): void
    {
        $this->assertSame(self::SCOPED_CONTAINER_ALIAS, (fn (): string => static::getFacadeAccessor())->bindTo(null, Module::class)());
        $this->assertInstanceOf(RepositoryInterface::class, Module::getFacadeRoot());
    }

    public function test_container_aliases_are_scoped_apart_from_the_deprecated_bare_alias(): void
    {
        $scoped = $this->assertContainerAliasesScoped($this->scope(), deprecated: ['modules']);

        $this->assertContains(self::SCOPED_CONTAINER_ALIAS, $scoped);
    }

    public function test_command_names_are_scoped_apart_from_the_declared_deprecated_aliases(): void
    {
        $deprecated = [];

        foreach ($this->app[Kernel::class]->all() as $command) {
            if (method_exists($command, 'deprecatedCommandAliases')) {
                array_push($deprecated, ...$command->deprecatedCommandAliases());
            }
        }

        $deprecated = array_values(array_unique($deprecated));

        $this->assertGreaterThanOrEqual(self::COMMAND_FLOOR, count($deprecated));
        $this->assertCommandNamesScoped($this->scope(), deprecated: $deprecated, atLeast: self::COMMAND_FLOOR);
    }

    private function scope(): NamingScope
    {
        return NamingScope::for('laranail/package-scaffolder', 'Simtabi\\Laranail\\Package\\Scaffolder\\');
    }
}
