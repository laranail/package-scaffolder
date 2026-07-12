<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ArtifactsConfigTest extends BaseTestCase
{
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = require dirname(__DIR__, 2).'/config/artifacts.php';
    }

    public function test_kinds_map_to_platform_containers(): void
    {
        $this->assertSame([
            'package' => 'platform/packages',
            'module' => 'platform/modules',
            'plugin' => 'platform/plugins',
        ], $this->config['kinds']);
    }

    public function test_plugin_types_include_a_true_none(): void
    {
        $this->assertContains('none', $this->config['plugin_types']);
        $this->assertContains('nova', $this->config['plugin_types']);
        $this->assertContains('filament', $this->config['plugin_types']);
    }

    public function test_core_features_are_listed_and_not_toggleable(): void
    {
        foreach (['lifecycle-events', 'search-manager', 'body-pipeline', 'macroable-dsl', 'spy-seam'] as $core) {
            $this->assertContains($core, $this->config['core']);
            $this->assertArrayNotHasKey($core, $this->config['features'], "core feature [$core] must not be toggleable");
        }
    }

    public function test_toggleable_features_each_declare_a_default(): void
    {
        $expected = ['web-ui', 'rest-api', 'caching', 'feeds', 'scheduling', 'asset-pipeline', 'notifications'];
        $this->assertSame($expected, array_keys($this->config['features']));

        foreach ($this->config['features'] as $name => $def) {
            $this->assertIsBool($def['default'], "feature [$name] needs a bool default");
            $this->assertArrayHasKey('description', $def);
        }
    }

    public function test_web_ui_has_a_livewire_sub_toggle(): void
    {
        $this->assertArrayHasKey('livewire', $this->config['features']['web-ui']['sub']);
        $this->assertTrue($this->config['features']['web-ui']['sub']['livewire']['default']);
    }

    public function test_namespace_defaults_and_suggestions_are_present(): void
    {
        $this->assertNotSame('', (string) $this->config['default_namespace']);
        $this->assertContains($this->config['default_namespace'], $this->config['namespace_suggestions']);
    }
}
