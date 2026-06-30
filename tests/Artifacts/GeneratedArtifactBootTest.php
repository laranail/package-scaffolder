<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Composer\Autoload\ClassLoader;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Runtime verification: a generated artifact's service provider must actually
 * boot inside a real Laravel container — not merely pass `php -l`. The only hard
 * dependency for booting is laranail/package-tools (the provider base); every
 * optional integration (livewire/sanctum/scout/filament/nova) is
 * class_exists-guarded, so a generated artifact boots against the scaffolder's
 * own installed vendor with no per-artifact `composer install`.
 *
 * Each case uses a distinct root namespace so their classes can coexist in the
 * one PHP process.
 */
class GeneratedArtifactBootTest extends BaseTestCase
{
    private Filesystem $fs;

    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(PackageServiceProvider::class)) {
            $this->markTestSkipped('laranail/package-tools is not installed (dev dependency).');
        }

        $this->fs = new Filesystem;
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    private function generateAndAutoload(string $base, string $name, string $vendor, string $plugin, array $features): string
    {
        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $target = sys_get_temp_dir().'/laranail-boot-'.uniqid();
        $this->targets[] = $target;

        (new ArtifactGenerator($this->fs, $config, dirname(__DIR__, 2).'/vendor/bin/pint'))
            ->generate(new GenerationRequest($plugin === 'none' ? 'package' : 'plugin', $plugin, $features, $name, $base, $vendor), dirname(__DIR__, 2).'/src/Commands/stubs/blueprint', $target);

        $loader = new ClassLoader;
        $loader->addPsr4($base.'\\'.$name.'\\', $target.'/src');
        $loader->register();

        return $target;
    }

    public function test_full_featured_plugin_none_artifact_boots()
    {
        $all = ['web-ui', 'livewire', 'rest-api', 'caching', 'feeds', 'scheduling', 'asset-pipeline', 'notifications'];
        $this->generateAndAutoload('BootFull', 'Widget', 'bootfull', 'none', $all);

        $provider = 'BootFull\\Widget\\Providers\\WidgetServiceProvider';
        $this->app->register($provider); // registers + boots (the app is already booted)

        // config was merged under the derived vendor.package key
        $this->assertSame('Widget', config('bootfull.widget.name'));
        // the manager singleton resolves (the facade accessor / spy seam)
        $manager = $this->app->make('BootFull\\Widget\\Widget');
        $this->assertInstanceOf('BootFull\\Widget\\Widget', $manager);
        // a core binding (the repository contract) is wired
        $this->assertInstanceOf(
            'BootFull\\Widget\\Repositories\\EloquentPostRepository',
            $this->app->make('BootFull\\Widget\\Contracts\\PostRepositoryInterface'),
        );
    }

    public function test_minimal_pruned_artifact_still_boots()
    {
        // every toggleable feature OFF — only the always-on core survives
        $this->generateAndAutoload('BootMin', 'Gadget', 'bootmin', 'none', []);

        $this->app->register('BootMin\\Gadget\\Providers\\GadgetServiceProvider');

        $this->assertSame('Gadget', config('bootmin.gadget.name'));
        $this->assertInstanceOf('BootMin\\Gadget\\Gadget', $this->app->make('BootMin\\Gadget\\Gadget'));
    }
}
