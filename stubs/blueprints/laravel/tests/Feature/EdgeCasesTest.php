<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use BadMethodCallException;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Some\NamespacePath\Blog\Blog as BlogManager;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Events\PostUnpublished;
use Some\NamespacePath\Blog\Events\PostUpdated;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Processing\BodyProcessor;
use Some\NamespacePath\Blog\Search\SearchManager;
use Some\NamespacePath\Blog\Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        BlogManager::flushMacros();
        parent::tearDown();
    }

    #[Test]
    public function recording_a_view_does_not_fire_post_updated_or_touch_the_model(): void
    {
        $post = Post::factory()->create(['views' => 0]);

        Event::fake([PostUpdated::class]);
        Blog::recordView($post);

        Event::assertNotDispatched(PostUpdated::class); // a view must not be a domain update
        $this->assertSame(1, $post->refresh()->views);
    }

    #[Test]
    public function an_unknown_manager_method_throws(): void
    {
        $this->expectException(BadMethodCallException::class);

        Blog::thisMethodDoesNotExist();
    }

    #[Test]
    public function the_body_processor_skips_empty_bodies_and_strips_disallowed_tags(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'T', 'status' => 'draft',
            'body' => 'Hi <strong>there</strong><script>evil()</script>',
        ]));

        $this->assertStringNotContainsString('<script>', $post->refresh()->body);
        $this->assertStringContainsString('<strong>there</strong>', $post->body);
    }

    #[Test]
    public function a_pipe_stage_can_short_circuit_the_body_pipeline(): void
    {
        // A veto stage that ignores $next replaces the rest of the chain.
        app(BodyProcessor::class)->pipe(fn (string $body, Closure $next): string => 'REPLACED');

        $post = Blog::create(PostData::fromArray(['title' => 'T', 'status' => 'draft', 'body' => 'original']));

        $this->assertSame('REPLACED', $post->refresh()->body);
    }

    #[Test]
    public function unpublishing_a_published_post_fires_post_unpublished(): void
    {
        $post = Post::factory()->published()->create();

        Event::fake([PostUnpublished::class]);
        Blog::unpublish($post);

        Event::assertDispatched(PostUnpublished::class, 1);
    }

    #[Test]
    public function selecting_the_scout_driver_without_scout_throws_a_clear_error(): void
    {
        config()->set('modules.blog.search.driver', 'scout');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('laravel/scout');

        app(SearchManager::class)->driver();
    }

    #[Test]
    public function rendered_body_returns_the_raw_body_when_markdown_is_off(): void
    {
        config()->set('modules.blog.processing.markdown', false);
        $post = Post::factory()->make(['body' => '**not markdown**']);

        $this->assertSame('**not markdown**', $post->rendered_body);
    }
}
