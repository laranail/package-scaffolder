<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class UsefulFeaturesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function record_view_increments_atomically(): void
    {
        $post = Post::factory()->create(['views' => 0]);

        Blog::recordView($post);
        Blog::recordView($post);

        $this->assertSame(2, $post->refresh()->views);
    }

    #[Test]
    public function featured_returns_only_pinned_published_posts(): void
    {
        Post::factory()->published()->create(['is_featured' => true, 'title' => 'Pinned']);
        Post::factory()->published()->create(['is_featured' => false]);

        $featured = Blog::featured();

        $this->assertCount(1, $featured);
        $this->assertSame('Pinned', $featured->first()->title);
    }

    #[Test]
    public function popular_by_views_changes_the_ranking(): void
    {
        config()->set('modules.blog.popular_by', 'views');
        Post::factory()->published()->create(['views' => 5]);
        $top = Post::factory()->published()->create(['views' => 50]);

        $this->assertSame($top->id, Blog::popularPosts()->first()->id);
    }

    #[Test]
    public function markdown_renders_on_display_and_preserves_the_source(): void
    {
        config()->set('modules.blog.processing.markdown', true);

        $post = Blog::create(PostData::fromArray([
            'title' => 'M', 'body' => '**bold** text', 'status' => 'draft',
        ]));

        // Stored body stays the author's Markdown source (round-trip editing).
        $this->assertSame('**bold** text', $post->refresh()->body);
        // Display renders it to HTML.
        $this->assertStringContainsString('<strong>bold</strong>', $post->rendered_body);
    }
}
