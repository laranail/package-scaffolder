<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Livewire\PostList;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

/**
 * Proves the component prefix is genuinely configurable end-to-end: a host that
 * sets a custom prefix gets it on every component, while the package's own
 * internal views keep working (they follow the active prefix dynamically).
 */
class CustomComponentPrefixTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('modules.blog.components.prefix', 'Acme Blog'); // not a slug on purpose
    }

    #[Test]
    public function components_render_under_the_normalised_custom_prefix(): void
    {
        // "Acme Blog" is normalised to the slug "acme-blog".
        $this->blade('<x-acme-blog::alert type="success">Hi</x-acme-blog::alert>')
            ->assertSee('Hi')
            ->assertSee('blog__alert--success', false);
    }

    #[Test]
    public function the_fixed_default_prefix_still_works_too(): void
    {
        $this->blade('<x-modules-blog::alert>Hi</x-modules-blog::alert>')
            ->assertSee('Hi');
    }

    #[Test]
    public function internal_views_follow_the_custom_prefix(): void
    {
        Post::factory()->published()->create(['title' => 'Hello prefix']);

        // post-list renders <x-dynamic-component :component="$blogComponentPrefix.'::post-card'" />,
        // which only resolves if the shared prefix and registration agree.
        Livewire::test(PostList::class)->assertSee('Hello prefix');
    }
}
