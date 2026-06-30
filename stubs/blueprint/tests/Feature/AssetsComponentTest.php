<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Tests\TestCase;

class AssetsComponentTest extends TestCase
{
    private string $buildDir;

    protected function setUp(): void
    {
        parent::setUp();

        // A temp build directory under public/ with a deterministic fake Vite
        // manifest, so the test never depends on an actual `npm run build`.
        $this->buildDir = 'blog-test-build-'.uniqid();
        $manifest = public_path($this->buildDir.'/.vite/manifest.json');

        File::ensureDirectoryExists(dirname($manifest));
        File::put($manifest, json_encode([
            'resources/assets/scripts/tailwind.js' => [
                'src' => 'resources/assets/scripts/tailwind.js', 'isEntry' => true,
                'file' => 'assets/tailwind-abc123.js', 'css' => ['assets/tailwind-abc123.css'],
            ],
            'resources/assets/scripts/bootstrap.js' => [
                'src' => 'resources/assets/scripts/bootstrap.js', 'isEntry' => true,
                'file' => 'assets/bootstrap-def456.js', 'css' => ['assets/bootstrap-def456.css'],
            ],
            'resources/assets/scripts/blog.js' => [
                'src' => 'resources/assets/scripts/blog.js', 'isEntry' => true,
                'file' => 'assets/blog-ghi789.js', 'css' => ['assets/blog-ghi789.css'],
            ],
        ]));

        config()->set('modules.blog.ui.assets.build_directory', $this->buildDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path($this->buildDir));

        parent::tearDown();
    }

    /**
     * Each framework, its own hashed file token, and the other two it must NOT load.
     *
     * @return array<string, array{0: string, 1: string, 2: array<int, string>}>
     */
    public static function frameworks(): array
    {
        return [
            'tailwind' => ['tailwind', 'tailwind-abc123', ['bootstrap-def456', 'blog-ghi789']],
            'bootstrap' => ['bootstrap', 'bootstrap-def456', ['tailwind-abc123', 'blog-ghi789']],
            'custom (vanilla)' => ['vanilla', 'blog-ghi789', ['tailwind-abc123', 'bootstrap-def456']],
        ];
    }

    /**
     * Compiled mode (the default): every framework loads ONLY its own bundle as
     * plain tags — Tailwind, Bootstrap and custom CSS are first-class peers.
     *
     * @param  array<int, string>  $others
     */
    #[Test]
    #[DataProvider('frameworks')]
    public function each_framework_loads_only_its_own_compiled_bundle(string $framework, string $own, array $others): void
    {
        config()->set('modules.blog.ui.assets.live', false);
        config()->set('modules.blog.ui.framework', $framework);

        $rendered = $this->blade('<x-modules-blog::assets />')
            ->assertSee("{$own}.css", false)
            ->assertSee("{$own}.js", false)
            ->assertDontSee('modulepreload', false); // compiled = no Vite runtime

        foreach ($others as $other) {
            $rendered->assertDontSee($other, false);
        }
    }

    /**
     * Live mode: every framework resolves through Laravel's Vite handler — same
     * three peers, now with modulepreload.
     *
     * @param  array<int, string>  $others
     */
    #[Test]
    #[DataProvider('frameworks')]
    public function each_framework_loads_only_its_own_live_bundle(string $framework, string $own, array $others): void
    {
        config()->set('modules.blog.ui.assets.live', true);
        config()->set('modules.blog.ui.framework', $framework);

        $rendered = $this->blade('<x-modules-blog::assets />')
            ->assertSee("{$own}.js", false)
            ->assertSee('modulepreload', false); // live = Vite runtime

        foreach ($others as $other) {
            $rendered->assertDontSee($other, false);
        }
    }

    #[Test]
    public function it_emits_nothing_when_framework_is_none(): void
    {
        config()->set('modules.blog.ui.framework', 'none');

        $this->blade('<x-modules-blog::assets />')
            ->assertDontSee('tailwind-abc123', false)
            ->assertDontSee('bootstrap-def456', false)
            ->assertDontSee('blog-ghi789', false);
    }

    #[Test]
    public function it_emits_nothing_when_the_build_is_missing(): void
    {
        config()->set('modules.blog.ui.assets.build_directory', 'no-such-build-dir');
        config()->set('modules.blog.ui.framework', 'tailwind');

        $this->blade('<x-modules-blog::assets />')
            ->assertDontSee('tailwind-abc123', false);
    }

    #[Test]
    public function compiled_mode_ignores_the_dev_server_hot_file(): void
    {
        // Default (live=false): a hot file must NOT trigger HMR — the compiled build is used.
        File::put(public_path($this->buildDir.'/hot'), 'http://localhost:5173');
        config()->set('modules.blog.ui.framework', 'tailwind');

        $this->blade('<x-modules-blog::assets />')
            ->assertSee('tailwind-abc123.css', false)
            ->assertDontSee('localhost:5173', false);
    }

    #[Test]
    public function live_mode_serves_the_dev_server_when_hot(): void
    {
        config()->set('modules.blog.ui.assets.live', true);
        File::put(public_path($this->buildDir.'/hot'), 'http://localhost:5173');
        config()->set('modules.blog.ui.framework', 'bootstrap');

        $this->blade('<x-modules-blog::assets />')
            ->assertSee('http://localhost:5173/@vite/client', false)
            ->assertSee('http://localhost:5173/resources/assets/scripts/bootstrap.js', false);
    }
}
