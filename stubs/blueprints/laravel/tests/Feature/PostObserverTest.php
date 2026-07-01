<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Events\PostPublished;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class PostObserverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function creating_a_published_post_dispatches_the_event_once(): void
    {
        Event::fake([PostPublished::class]);

        Post::factory()->published()->create();

        Event::assertDispatchedTimes(PostPublished::class, 1);
    }

    #[Test]
    public function a_draft_does_not_dispatch_the_event(): void
    {
        Event::fake([PostPublished::class]);

        Post::factory()->create();

        Event::assertNotDispatched(PostPublished::class);
    }

    #[Test]
    public function publishing_a_draft_dispatches_the_event_exactly_once(): void
    {
        $post = Post::factory()->create();

        Event::fake([PostPublished::class]);

        Blog::publish($post);

        Event::assertDispatchedTimes(PostPublished::class, 1);
    }

    #[Test]
    public function a_blank_excerpt_is_auto_generated(): void
    {
        $post = Post::factory()->create(['excerpt' => null, 'body' => 'Some body text for the excerpt.']);

        $this->assertNotEmpty($post->excerpt);
    }
}
