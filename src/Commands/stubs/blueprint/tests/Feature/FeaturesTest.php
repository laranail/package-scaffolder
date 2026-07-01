<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Tests\TestCase;

class FeaturesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reading_time_is_estimated_from_the_body(): void
    {
        $post = Post::factory()->create(['body' => str_repeat('word ', 400)]);

        $this->assertSame(2, $post->reading_time); // 400 words / 200 = 2
    }

    #[Test]
    public function related_posts_share_a_category_and_exclude_self(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->published()->create(['category_id' => $category->id]);
        Post::factory()->count(2)->published()->create(['category_id' => $category->id]);
        Post::factory()->published()->create(); // other category

        $related = Blog::related($post);

        $this->assertCount(2, $related);
        $this->assertFalse($related->contains($post));
    }

    #[Test]
    public function creating_a_post_with_tags_syncs_them(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'Tagged', 'body' => 'Body', 'status' => 'draft',
            'tags' => ['Laravel', 'PHP'],
        ]));

        $this->assertEqualsCanonicalizing(['Laravel', 'PHP'], $post->tags()->pluck('name')->all());
        $this->assertSame(2, Tag::query()->count());
    }

    // @artifact:start rest-api
    #[Test]
    public function the_api_can_filter_posts_by_tag(): void
    {
        $tagged = Post::factory()->published()->create();
        $tagged->tags()->attach(Tag::factory()->create(['name' => 'Featured']));
        Post::factory()->published()->create();

        $this->getJson('/api/v1/posts?tag=featured')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
    // @artifact:end rest-api

    #[Test]
    public function the_body_is_sanitised_of_dangerous_html_on_save(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'XSS', 'status' => 'draft',
            'body' => 'Hello <strong>world</strong> <script>alert(1)</script>',
        ]));

        $this->assertStringNotContainsString('<script>', $post->refresh()->body);
        $this->assertStringContainsString('<strong>', $post->body);
    }

    // @artifact:start feeds
    #[Test]
    public function the_rss_feed_renders(): void
    {
        Post::factory()->published()->create(['title' => 'Feed item']);

        $this->get('/blog/feed')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->assertSee('Feed item')
            ->assertSee('<rss', false);
    }
    // @artifact:end feeds

    // @artifact:start feeds
    #[Test]
    public function the_sitemap_renders(): void
    {
        $post = Post::factory()->published()->create();

        $this->get('/blog/sitemap.xml')
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertSee($post->slug);
    }
    // @artifact:end feeds

    // @artifact:start feeds
    #[Test]
    public function feeds_can_be_disabled(): void
    {
        config()->set('modules.blog.features.rss', false);

        $this->get('/blog/feed')->assertNotFound();
    }
    // @artifact:end feeds
}
