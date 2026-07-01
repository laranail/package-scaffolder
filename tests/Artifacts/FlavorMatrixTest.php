<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

/**
 * The framework-flavor matrix: each flavor generates from its own blueprint and
 * carries exactly the manifests its registry entry declares (vanilla = composer
 * only; laravel/lumen = composer + module + plugin), with no framework leakage.
 */
class FlavorMatrixTest extends BaseTestCase
{
    private Filesystem $fs;

    /** @var list<string> */
    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    /** @param  list<string>  $features */
    private function generate(string $flavor, string $name, array $features = []): string
    {
        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $blueprint = (string) $config['flavors'][$flavor]['blueprint'];
        $target = sys_get_temp_dir().'/laranail-flavor-'.$flavor.'-'.uniqid();
        $this->targets[] = $target;

        (new ArtifactGenerator($this->fs, $config, dirname(__DIR__, 2).'/vendor/bin/pint'))
            ->generate(
                new GenerationRequest('package', 'none', $features, $name, 'Acme', 'acme', false, 'Item', $flavor),
                dirname(__DIR__, 2).'/stubs/blueprints/'.$blueprint,
                $target,
            );

        return $target;
    }

    public function test_vanilla_is_composer_only_and_illuminate_free()
    {
        $t = $this->generate('vanilla', 'Widget');

        $this->assertFileExists($t.'/composer.json');
        $this->assertFileDoesNotExist($t.'/module.json');
        $this->assertFileDoesNotExist($t.'/plugin.json');
        $this->assertFileExists($t.'/src/Widget.php');

        $leaks = [];
        foreach ($this->fs->allFiles($t) as $f) {
            if ($f->getExtension() === 'php' && str_contains($this->fs->get($f->getPathname()), 'Illuminate')) {
                $leaks[] = $f->getRelativePathname();
            }
        }
        $this->assertSame([], $leaks, 'vanilla must be Illuminate-free: '.implode(', ', $leaks));
    }

    public function test_lumen_carries_all_manifests_and_a_provider()
    {
        $t = $this->generate('lumen', 'Gadget');

        $this->assertFileExists($t.'/composer.json');
        $this->assertFileExists($t.'/module.json');
        $this->assertFileExists($t.'/plugin.json');
        $this->assertFileExists($t.'/src/Providers/GadgetServiceProvider.php');
        $this->assertIsArray(json_decode($this->fs->get($t.'/plugin.json'), true));
    }

    public function test_laravel_carries_all_manifests()
    {
        $t = $this->generate('laravel', 'Shop');

        $this->assertFileExists($t.'/composer.json');
        $this->assertFileExists($t.'/module.json');
        $this->assertFileExists($t.'/plugin.json');
        $pj = json_decode($this->fs->get($t.'/plugin.json'), true);
        $this->assertSame('acme/shop', $pj['id']);
    }
}
