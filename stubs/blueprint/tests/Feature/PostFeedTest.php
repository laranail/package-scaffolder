<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class PostFeedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_feed_only_returns_published_posts(): void
    {
        Post::factory()->count(3)->published()->create();
        Post::factory()->count(2)->create(); // drafts
        Post::factory()->scheduled()->create();

        $feed = Blog::feed();

        $this->assertCount(3, $feed);
    }
}
