<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Tests\TestCase;

class AssetsComponentTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();

        // A deterministic fake Vite manifest, so the test never depends on a build.
        $this->manifestPath = sys_get_temp_dir().'/blog-manifest-'.uniqid().'.json';
        file_put_contents($this->manifestPath, json_encode([
            'resources/assets/scripts/tailwind.js' => [
                'file' => 'assets/tailwind-abc123.js',
                'css' => ['assets/tailwind-abc123.css'],
            ],
            'resources/assets/scripts/bootstrap.js' => [
                'file' => 'assets/bootstrap-def456.js',
                'css' => ['assets/bootstrap-def456.css'],
            ],
            'resources/assets/scripts/app.js' => [
                'file' => 'assets/app-ghi789.js',
                'css' => ['assets/app-ghi789.css'],
            ],
        ]));

        config()->set('modules.blog.ui.assets.manifest', $this->manifestPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->manifestPath);

        parent::tearDown();
    }

    #[Test]
    public function it_emits_the_tailwind_bundle(): void
    {
        config()->set('modules.blog.ui.framework', 'tailwind');

        $this->blade('<x-modules-blog::assets />')
            ->assertSee('tailwind-abc123.css', false)
            ->assertSee('tailwind-abc123.js', false)
            ->assertDontSee('bootstrap-def456', false);
    }

    #[Test]
    public function it_emits_the_bootstrap_bundle(): void
    {
        config()->set('modules.blog.ui.framework', 'bootstrap');

        $this->blade('<x-modules-blog::assets />')
            ->assertSee('bootstrap-def456.css', false)
            ->assertSee('bootstrap-def456.js', false)
            ->assertDontSee('tailwind-abc123', false);
    }

    #[Test]
    public function it_emits_the_vanilla_bundle(): void
    {
        config()->set('modules.blog.ui.framework', 'vanilla');

        $this->blade('<x-modules-blog::assets />')
            ->assertSee('app-ghi789.css', false)
            ->assertSee('app-ghi789.js', false)
            ->assertDontSee('tailwind-abc123', false)
            ->assertDontSee('bootstrap-def456', false);
    }

    #[Test]
    public function it_emits_nothing_when_framework_is_none(): void
    {
        config()->set('modules.blog.ui.framework', 'none');

        $this->blade('<x-modules-blog::assets />')
            ->assertDontSee('tailwind-abc123', false)
            ->assertDontSee('bootstrap-def456', false);
    }
}
