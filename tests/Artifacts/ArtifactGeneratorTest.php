<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ArtifactGeneratorTest extends BaseTestCase
{
    private Filesystem $fs;

    private string $source;

    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
        $this->source = dirname(__DIR__, 2).'/stubs/blueprint';
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    private function generate(GenerationRequest $req): string
    {
        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $target = sys_get_temp_dir().'/laranail-artifact-'.uniqid();
        $this->targets[] = $target;

        return (new ArtifactGenerator($this->fs, $config))->generate($req, $this->source, $target);
    }

    private function phpLint(string $file): bool
    {
        exec('php -l '.escapeshellarg($file).' 2>&1', $out, $code);

        return $code === 0;
    }

    public function test_full_package_with_all_features_and_plugin_none()
    {
        $all = ['web-ui', 'livewire', 'rest-api', 'caching', 'feeds', 'scheduling', 'asset-pipeline', 'notifications'];
        $t = $this->generate(new GenerationRequest('package', 'none', $all, 'Blog', 'Modules', 'modules'));

        $provider = $t.'/src/Providers/BlogServiceProvider.php';
        $this->assertFileExists($provider);
        $content = $this->fs->get($provider);
        $this->assertStringContainsString('namespace Modules\\Blog\\Providers;', $content);
        $this->assertStringNotContainsString('@artifact:', $content);
        $this->assertTrue($this->phpLint($provider), 'generated provider must be valid PHP');

        // core + enabled features present
        $this->assertFileExists($t.'/src/Repositories/CachingPostRepository.php');
        $this->assertFileExists($t.'/src/Search/SearchManager.php');
        $this->assertFileExists($t.'/src/Livewire/PostList.php');

        // plugin = none ⇒ zero Nova/Filament code footprint
        $this->assertDirectoryDoesNotExist($t.'/src/Filament');
        $this->assertDirectoryDoesNotExist($t.'/src/Nova');
        $this->assertFileDoesNotExist($t.'/src/Providers/Integrations/FilamentBlogServiceProvider.php');
        // Functional footprint: no Filament\ / Laravel\Nova namespace usage in src/.
        // (Incidental prose mentions in doc comments are scrubbed separately, task #23.)
        $refs = [];
        foreach ($this->fs->allFiles($t.'/src') as $f) {
            $c = $this->fs->get($f->getPathname());
            if (str_contains($c, 'Filament\\') || str_contains($c, 'Laravel\\Nova')) {
                $refs[] = $f->getFilename();
            }
        }
        $this->assertSame([], $refs, 'plugin=none must leave no functional Nova/Filament references in src/');

        // composer: one provider, no nova/filament deps
        $composer = json_decode($this->fs->get($t.'/composer.json'), true);
        $this->assertCount(1, $composer['extra']['laravel']['providers']);
        $deps = implode(' ', array_keys(($composer['require'] ?? []) + ($composer['require-dev'] ?? []) + ($composer['suggest'] ?? [])));
        $this->assertStringNotContainsStringIgnoringCase('filament', $deps);
        $this->assertStringNotContainsStringIgnoringCase('laravel/nova', $deps);
    }

    public function test_plugin_filament_with_caching_and_livewire_off_and_renamed()
    {
        $features = ['web-ui', 'rest-api', 'feeds', 'scheduling', 'asset-pipeline', 'notifications']; // no caching, no livewire
        $t = $this->generate(new GenerationRequest('plugin', 'filament', $features, 'Shop', 'Acme', 'acme'));

        $provider = $t.'/src/Providers/ShopServiceProvider.php';
        $this->assertFileExists($provider);
        $content = $this->fs->get($provider);
        $this->assertStringContainsString('namespace Acme\\Shop\\Providers;', $content);
        $this->assertStringNotContainsString('@artifact:', $content);
        $this->assertTrue($this->phpLint($provider));

        // caching OFF
        $this->assertFileDoesNotExist($t.'/src/Repositories/CachingPostRepository.php');
        $this->assertFileDoesNotExist($t.'/src/Listeners/FlushBlogCache.php');
        $this->assertStringNotContainsString('cache.enabled', $content);

        // web-ui ON but livewire OFF
        $this->assertFileExists($t.'/src/Http/Controllers/ShopController.php');
        $this->assertDirectoryDoesNotExist($t.'/src/Livewire');

        // plugin filament KEPT, nova removed
        $this->assertDirectoryExists($t.'/src/Filament');
        $this->assertFileExists($t.'/src/Providers/Integrations/FilamentShopServiceProvider.php');
        $this->assertDirectoryDoesNotExist($t.'/src/Nova');

        $composer = json_decode($this->fs->get($t.'/composer.json'), true);
        $providers = implode(' ', $composer['extra']['laravel']['providers']);
        $this->assertStringContainsString('Integrations\\Filament', $providers);
        $this->assertStringNotContainsString('Integrations\\Nova', $providers);
    }

    public function test_pint_pass_strips_imports_orphaned_by_a_disabled_feature()
    {
        $pint = dirname(__DIR__, 2).'/vendor/bin/pint';
        if (! is_file($pint)) {
            $this->markTestSkipped('Pint binary not available.');
        }

        $config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $target = sys_get_temp_dir().'/laranail-artifact-'.uniqid();
        $this->targets[] = $target;

        $features = ['web-ui', 'livewire', 'rest-api', 'feeds', 'scheduling', 'asset-pipeline', 'notifications']; // caching OFF
        (new ArtifactGenerator($this->fs, $config, $pint))
            ->generate(new GenerationRequest('package', 'none', $features, 'Blog', 'Modules', 'modules'), $this->source, $target);

        $content = $this->fs->get($target.'/src/Providers/BlogServiceProvider.php');
        // the caching wiring is stripped AND its now-unused import removed by Pint
        $this->assertStringNotContainsString('CachingPostRepository', $content);
        $this->assertTrue($this->phpLint($target.'/src/Providers/BlogServiceProvider.php'));
    }
}
