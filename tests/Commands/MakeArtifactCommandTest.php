<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class MakeArtifactCommandTest extends BaseTestCase
{
    private Filesystem $fs;

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

    private function tmp(): string
    {
        $dir = sys_get_temp_dir().'/laranail-cmd-'.uniqid();
        $this->targets[] = $dir;

        return $dir;
    }

    public function test_non_interactive_generates_a_package_with_flags()
    {
        $dir = $this->tmp();

        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--namespace' => 'Acme',
            '--features' => 'web-ui,rest-api',
            '--path' => $dir,
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        $provider = $dir.'/Demo/src/Providers/DemoServiceProvider.php';
        $this->assertFileExists($provider);
        $this->assertStringContainsString('namespace Acme\\Demo\\Providers;', $this->fs->get($provider));
        // a feature left out of --features must not be generated
        $this->assertFileDoesNotExist($dir.'/Demo/src/Repositories/CachingPostRepository.php');
    }

    public function test_default_entity_is_distinct_from_the_artifact_name()
    {
        // `make:artifact Admin` with no --entity must NOT make entity == artifact
        // (that collides the manager {Artifact} with the model {Entity}); it defaults
        // to a distinct generic entity (Item) so the artifact builds.
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Admin', '--type' => 'package', '--namespace' => 'Acme',
            '--path' => $dir, '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        $this->assertFileExists($dir.'/Admin/src/Admin.php');             // manager = artifact
        $this->assertFileExists($dir.'/Admin/src/Models/Item.php');       // model = distinct entity
        $this->assertFileDoesNotExist($dir.'/Admin/src/Models/Admin.php'); // never entity == artifact
    }

    public function test_entity_equal_to_artifact_is_rejected()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Admin', '--type' => 'package', '--entity' => 'Admin',
            '--path' => $this->tmp(), '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('must differ from the artifact name', Artisan::output());
    }

    public function test_default_flavor_emits_all_manifests_from_the_laravel_blueprint()
    {
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo', '--type' => 'package', '--namespace' => 'Acme', '--vendor' => 'acme',
            '--path' => $dir, '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        // one repo, three role manifests (laravel flavor supports all)
        $this->assertFileExists($dir.'/Demo/composer.json');
        $this->assertFileExists($dir.'/Demo/module.json');
        $this->assertFileExists($dir.'/Demo/plugin.json');
        $pj = json_decode($this->fs->get($dir.'/Demo/plugin.json'), true);
        $this->assertSame('acme/demo', $pj['id']);
        $this->assertSame('Demo', $pj['name']);
        $this->assertStringContainsString('Acme\\Demo\\Providers\\DemoServiceProvider', $pj['provider']);
    }

    public function test_unknown_flavor_is_rejected()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo', '--type' => 'package', '--flavor' => 'django',
            '--path' => $this->tmp(), '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--flavor must be one of', Artisan::output());
    }

    public function test_panel_incompatible_with_flavor_is_rejected()
    {
        // vanilla has no admin panels
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo', '--type' => 'package', '--flavor' => 'vanilla', '--plugin' => 'filament',
            '--path' => $this->tmp(), '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('not available for the [vanilla] flavor', Artisan::output());
    }

    public function test_feature_incompatible_with_flavor_is_rejected()
    {
        // web-ui is not part of the vanilla flavor's feature set
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo', '--type' => 'package', '--flavor' => 'vanilla', '--plugin' => 'none',
            '--features' => 'web-ui', '--path' => $this->tmp(), '--no-interaction' => true, '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('not available for the [vanilla] flavor', Artisan::output());
    }

    public function test_missing_required_type_fails_loudly()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--type is required', Artisan::output());
    }

    public function test_unknown_feature_errors()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--features' => 'web-ui,teleporter',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Unknown feature', Artisan::output());
    }

    public function test_artifact_name_must_be_unique_across_containers()
    {
        // pre-existing artifact in the modules container
        $this->fs->ensureDirectoryExists(base_path('platform/modules/Collision'));
        $this->targets[] = base_path('platform');

        // generating the same name into the packages container must fail
        $code = Artisan::call('make:artifact', [
            'name' => 'Collision',
            '--type' => 'package',
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('unique across all containers', Artisan::output());
    }

    public function test_selecting_livewire_pulls_in_its_required_web_ui()
    {
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--features' => 'livewire',
            '--path' => $dir,
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        // livewire selected ...
        $this->assertDirectoryExists($dir.'/Demo/src/Livewire');
        // ... and its required web-ui was pulled in (web component views present)
        $this->assertDirectoryExists($dir.'/Demo/resources/views/components');
    }

    public function test_selecting_feeds_pulls_in_its_required_web_ui()
    {
        // feeds (RSS/sitemap) are web routes + a web controller, so choosing feeds
        // must transitively pull in web-ui (else the generated feed 500s at runtime).
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--features' => 'feeds',
            '--path' => $dir,
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        // its required web-ui was pulled in (web component views present)
        $this->assertDirectoryExists($dir.'/Demo/resources/views/components');
    }

    public function test_selecting_asset_pipeline_pulls_in_its_required_web_ui()
    {
        // the Assets component is a Blade/view concern, so asset-pipeline requires web-ui.
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--features' => 'asset-pipeline',
            '--path' => $dir,
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        $this->assertDirectoryExists($dir.'/Demo/resources/views/components');
    }

    public function test_panel_defaults_to_none_and_is_zero_footprint()
    {
        $dir = $this->tmp();
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'plugin',
            '--namespace' => 'Acme',
            '--path' => $dir,
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        // panel omitted ⇒ none ⇒ zero Nova/Filament footprint
        $this->assertDirectoryDoesNotExist($dir.'/Demo/src/Filament');
        $this->assertDirectoryDoesNotExist($dir.'/Demo/src/Nova');
    }

    public function test_invalid_panel_value_is_rejected()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'plugin',
            '--plugin' => 'wordpress',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
            '--no-repo' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--plugin must be one of', Artisan::output());
    }
}
