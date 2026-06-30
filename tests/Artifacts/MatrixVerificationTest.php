<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Artifacts;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\DataProvider;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

/**
 * Self-verification sweep across the type × plugin × feature matrix: every
 * combination must generate a structurally valid artifact — no leftover markers,
 * a syntactically valid provider, both laranail deps present, and the plugin
 * dimension honored (incl. plugin=none zero footprint, prose scrub included).
 */
class MatrixVerificationTest extends BaseTestCase
{
    private Filesystem $fs;

    private array $targets = [];

    private array $config;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
        $this->config = require dirname(__DIR__, 2).'/config/artifacts.php';
        $this->source = dirname(__DIR__, 2).'/src/Commands/stubs/blueprint';
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    public static function matrix(): array
    {
        $all = ['web-ui', 'livewire', 'rest-api', 'caching', 'feeds', 'scheduling', 'asset-pipeline', 'notifications'];

        $matrix = [];
        // full 3 shapes × {nova, filament, none}, all features
        foreach (['package', 'module', 'plugin'] as $kind) {
            foreach (['nova', 'filament', 'none'] as $panel) {
                $matrix["{$kind} · {$panel} · all"] = [$kind, $panel, $all];
            }
        }
        // representative feature-pruning combos
        $matrix['package · none · minimal'] = ['package', 'none', []];
        $matrix['package · none · caching+api'] = ['package', 'none', ['caching', 'rest-api']];

        return $matrix;
    }

    #[DataProvider('matrix')]
    public function test_matrix_combination_generates_a_valid_artifact(string $kind, string $plugin, array $features)
    {
        $target = sys_get_temp_dir().'/laranail-matrix-'.uniqid();
        $this->targets[] = $target;

        (new ArtifactGenerator($this->fs, $this->config))
            ->generate(new GenerationRequest($kind, $plugin, $features, 'Widget', 'Acme', 'acme'), $this->source, $target);

        // 1. no half-processed markers survive anywhere
        $leftover = [];
        foreach ($this->fs->allFiles($target) as $f) {
            $c = $this->fs->get($f->getPathname());
            if (str_contains($c, '@artifact:') || preg_match('/\[\[\/?[\w-]+\]\]/', $c) === 1) {
                $leftover[] = $f->getRelativePathname();
            }
        }
        $this->assertSame([], $leftover, 'leftover @artifact / [[inline]] markers');

        // 2. the densest wiring file is valid PHP
        $provider = $target.'/src/Providers/WidgetServiceProvider.php';
        $this->assertFileExists($provider);
        exec('php -l '.escapeshellarg($provider).' 2>&1', $o, $code);
        $this->assertSame(0, $code, 'invalid provider: '.implode("\n", $o));

        // 3. both laranail libraries are required by the generated artifact
        $composer = json_decode($this->fs->get($target.'/composer.json'), true);
        $this->assertArrayHasKey('laranail/console', $composer['require']);
        $this->assertArrayHasKey('laranail/package-tools', $composer['require']);

        // 4. plugin dimension honored
        if ($plugin === 'nova') {
            $this->assertDirectoryExists($target.'/src/Nova');
            $this->assertDirectoryDoesNotExist($target.'/src/Filament');
        } elseif ($plugin === 'filament') {
            $this->assertDirectoryExists($target.'/src/Filament');
            $this->assertDirectoryDoesNotExist($target.'/src/Nova');
        } else { // none ⇒ literal zero Nova/Filament footprint across the WHOLE tree
            $this->assertDirectoryDoesNotExist($target.'/src/Nova');
            $this->assertDirectoryDoesNotExist($target.'/src/Filament');
            $this->assertFileDoesNotExist($target.'/docs/tools/panels.md');

            $refs = [];
            foreach ($this->fs->allFiles($target) as $f) {
                $c = $this->fs->get($f->getPathname());
                // case-insensitive, word-bounded so it catches lowercase product refs
                // (filament/filament, laravel/nova) but not words like "innovation".
                if (preg_match('/\bfilament\b|\bnova\b/i', $c) === 1) {
                    $refs[] = $f->getRelativePathname();
                }
            }
            $this->assertSame([], $refs, 'plugin=none must leave zero Filament/Nova references anywhere');
        }
    }
}
