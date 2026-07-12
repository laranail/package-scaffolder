<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Facades;

use Simtabi\Laranail\Package\Scaffolder\Facades\Module;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ModuleFacadeTest extends BaseTestCase
{
    public function test_it_resolves_the_module_facade(): void
    {
        $modules = Module::all();

        $this->assertTrue(is_array($modules));
    }

    public function test_it_creates_macros_via_facade(): void
    {
        Module::macro('testMacro', fn (): true => true);

        $this->assertTrue(Module::hasMacro('testMacro'));
    }

    public function test_it_calls_macros_via_facade(): void
    {
        Module::macro('testMacro', fn (): string => 'a value');

        $this->assertEquals('a value', Module::testMacro());
    }
}
