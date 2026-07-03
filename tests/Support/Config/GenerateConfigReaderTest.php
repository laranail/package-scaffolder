<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Support\Config;

use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GeneratorPath;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

final class GenerateConfigReaderTest extends BaseTestCase
{
    public function test_it_can_read_a_configuration_value_with_new_format(): void
    {
        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('database/seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_new_format_set_to_false(): void
    {
        $this->app['config']->set('modules.paths.generator.seeder', ['path' => 'Database/Seeders', 'generate' => false]);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format(): void
    {
        $this->app['config']->set('modules.paths.generator.seeder', 'Database/Seeders');

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertEquals('Database/Seeders', $seedConfig->getPath());
        $this->assertTrue($seedConfig->generate());
    }

    public function test_it_can_read_a_configuration_value_with_old_format_set_to_false(): void
    {
        $this->app['config']->set('modules.paths.generator.seeder', false);

        $seedConfig = GenerateConfigReader::read('seeder');

        $this->assertInstanceOf(GeneratorPath::class, $seedConfig);
        $this->assertFalse($seedConfig->getPath());
        $this->assertFalse($seedConfig->generate());
    }

    public function test_it_can_guess_namespace_from_path(): void
    {
        $this->app['config']->set('modules.paths.generator.provider', ['path' => 'Base/Providers', 'generate' => true]);

        $config = GenerateConfigReader::read('provider');

        $this->assertEquals('Base/Providers', $config->getPath());
        $this->assertEquals('Base\Providers', $config->getNamespace());
    }
}
