<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Events\PostUpdated;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Repositories\CachingPostRepository;
use Some\NamespacePath\Blog\Tests\TestCase;

class CachingTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        // Caching must be enabled before the provider registers (decorator is
        // wired in packageRegistered) and booted (FlushBlogCache listener).
        $app['config']->set('modules.blog.cache.enabled', true);
        $app['config']->set('cache.default', 'array');
    }

    #[Test]
    public function the_repository_is_decorated_when_caching_is_enabled(): void
    {
        $this->assertInstanceOf(CachingPostRepository::class, app(PostRepositoryInterface::class));
    }

    #[Test]
    public function reads_are_served_from_cache_and_busted_by_lifecycle_events(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'cached', 'title' => 'First']);
        $repo = app(PostRepositoryInterface::class);

        $this->assertSame('First', $repo->findBySlug('cached')?->title);

        // Mutate via the query builder (no model events) → cache is NOT busted.
        Post::query()->where('slug', 'cached')->update(['title' => 'Changed']);
        $this->assertSame('First', $repo->findBySlug('cached')?->title, 'should be served from cache');

        // A real lifecycle event busts the cache (covers writers that bypass the repo).
        event(new PostUpdated($post));
        $this->assertSame('Changed', $repo->findBySlug('cached')?->title);
    }

    #[Test]
    public function missing_slugs_are_negatively_cached(): void
    {
        $repo = app(PostRepositoryInterface::class);

        $this->assertNull($repo->findBySlug('ghost')); // miss → sentinel cached

        // Insert the row via the query builder (NO model events → cache NOT busted).
        DB::table('blog_posts')->insert([
            'title' => 'Ghost', 'slug' => 'ghost', 'body' => 'x', 'status' => 'published',
            'published_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Served from the negative cache (proves the miss was cached).
        $this->assertNull($repo->findBySlug('ghost'));

        // A real lifecycle event busts the version → the post is now found.
        event(new PostUpdated(Post::query()->where('slug', 'ghost')->firstOrFail()));
        $this->assertNotNull($repo->findBySlug('ghost'));
    }

    #[Test]
    public function category_and_tag_changes_also_bust_the_cache(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'c2', 'title' => 'One']);
        $repo = app(PostRepositoryInterface::class);

        $repo->findBySlug('c2'); // prime
        Post::query()->where('slug', 'c2')->update(['title' => 'Two']);

        // A tag change must invalidate cached posts/feeds (eager-loaded relations go stale).
        Tag::factory()->create();

        $this->assertSame('Two', $repo->findBySlug('c2')?->title);
    }
}
