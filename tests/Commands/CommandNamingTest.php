<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class CommandNamingTest extends BaseTestCase
{
    /**
     * Resolving every command forces each one to be instantiated, which would
     * surface any `::`-named command that is not bypassing Symfony's name
     * validator (i.e. missing the SupportsNamespacedNames trait).
     */
    public function test_all_commands_instantiate_and_use_the_namespaced_name()
    {
        $commands = $this->app[Kernel::class]->all();

        $this->assertArrayHasKey('laranail::package-scaffolder.make', $commands);
        $this->assertArrayHasKey('laranail::package-scaffolder.list', $commands);
        $this->assertArrayHasKey('laranail::package-scaffolder.migrate', $commands);
    }

    public function test_legacy_module_names_are_kept_as_aliases()
    {
        $commands = $this->app[Kernel::class]->all();

        $this->assertContains('module:make', $commands['laranail::package-scaffolder.make']->getAliases());
        $this->assertContains('module:disable', $commands['laranail::package-scaffolder.disable']->getAliases());
        $this->assertContains('module:migrate', $commands['laranail::package-scaffolder.migrate']->getAliases());
    }
}
