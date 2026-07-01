<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\HostComposerWriter;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class PortabilityTest extends BaseTestCase
{
    private Filesystem $fs;

    private array $cleanup = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
    }

    protected function tearDown(): void
    {
        foreach ($this->cleanup as $p) {
            $this->fs->isDirectory($p) ? $this->fs->deleteDirectory($p) : $this->fs->delete($p);
        }
        parent::tearDown();
    }

    public function test_same_artifact_resolves_to_same_namespace_in_every_container()
    {
        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $source = dirname(__DIR__, 2).'/stubs/blueprint';
        $features = array_keys($config['features']);
        $features[] = 'livewire';

        $providers = [];
        foreach (['module', 'package', 'plugin'] as $kind) {
            $target = sys_get_temp_dir().'/laranail-port-'.$kind.'-'.uniqid();
            $this->cleanup[] = $target;
            $plugin = $kind === 'plugin' ? 'none' : 'none';
            (new ArtifactGenerator($this->fs, $config))
                ->generate(new GenerationRequest($kind, $plugin, $features, 'Widget', 'Acme', 'acme'), $source, $target);
            $providers[$kind] = $this->fs->get($target.'/src/Providers/WidgetServiceProvider.php');
        }

        foreach ($providers as $kind => $content) {
            $this->assertStringContainsString('namespace Acme\\Widget\\Providers;', $content, "container [$kind] must not affect the namespace");
        }
        // folder is location-only: the generated provider is byte-identical everywhere
        $this->assertSame($providers['module'], $providers['package']);
        $this->assertSame($providers['module'], $providers['plugin']);
    }

    public function test_host_composer_wiring_is_idempotent_and_preserves_unrelated_keys()
    {
        $path = sys_get_temp_dir().'/laranail-host-'.uniqid().'.json';
        $this->cleanup[] = $path;

        // A pre-existing composer with unrelated keys + a developer's own choices.
        $this->fs->put($path, json_encode([
            'name' => 'acme/app',
            'require' => ['php' => '^8.4'],
            'minimum-stability' => 'stable',
            'config' => ['allow-plugins' => ['acme/custom-plugin' => true]],
        ], JSON_PRETTY_PRINT));

        $writer = new HostComposerWriter($this->fs);
        $writer->wire($path);
        $first = $this->fs->get($path);
        $writer->wire($path);
        $second = $this->fs->get($path);

        $this->assertSame($first, $second, 'wiring must be idempotent');

        $c = json_decode($second, true);
        // wiring applied
        $this->assertContains('./platform/packages/*/composer.json', $c['extra']['merge-plugin']['include']);
        $this->assertContains('./platform/plugins/*', array_column($c['repositories'], 'url'));
        $this->assertTrue($c['config']['allow-plugins']['wikimedia/composer-merge-plugin']);
        // unrelated/developer keys preserved
        $this->assertSame('acme/app', $c['name']);
        $this->assertSame('^8.4', $c['require']['php']);
        $this->assertSame('stable', $c['minimum-stability'], 'must not clobber the developer minimum-stability');
        $this->assertTrue($c['config']['allow-plugins']['acme/custom-plugin'], 'developer allow-plugins entry preserved');
    }
}
