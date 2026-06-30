<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Blog as BlogManager;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Processing\BodyProcessor;
use Some\NamespacePath\Blog\Tests\TestCase;

class RegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        BlogManager::flushMacros();
        parent::tearDown();
    }

    #[Test]
    public function filtering_by_category_id_returns_only_that_categorys_posts(): void
    {
        // Regression: an ungrouped orWhere inside whereHas made the id branch
        // uncorrelated, so ?category=<id> returned the ENTIRE table.
        $a = Category::factory()->create();
        $b = Category::factory()->create();
        Post::factory()->published()->create(['category_id' => $a->id]);
        Post::factory()->published()->count(3)->create(['category_id' => $b->id]);

        $this->getJson("/api/v1/posts?category={$a->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function filtering_by_tag_id_returns_only_tagged_posts(): void
    {
        $tag = Tag::factory()->create();
        $tagged = Post::factory()->published()->create();
        $tagged->tags()->attach($tag);
        Post::factory()->published()->count(2)->create();

        $this->getJson("/api/v1/posts?tag={$tag->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function a_post_can_be_featured_through_the_write_path(): void
    {
        // Regression: is_featured was fillable/exposed but not in the DTO/requests,
        // so it could never be set via Blog::create / web / API.
        $post = Blog::create(PostData::fromArray([
            'title' => 'Pinned', 'body' => 'x', 'status' => 'published', 'is_featured' => true,
        ]));

        $this->assertTrue($post->refresh()->is_featured);
    }

    #[Test]
    public function the_featured_filter_narrows_the_api_feed(): void
    {
        Post::factory()->published()->create(['is_featured' => true]);
        Post::factory()->published()->count(2)->create(['is_featured' => false]);

        $this->getJson('/api/v1/posts?featured=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function a_consumer_pipe_stage_cannot_reintroduce_unsanitized_html(): void
    {
        // Regression: sanitize must run LAST, after consumer transform stages.
        app(BodyProcessor::class)->pipe(fn (string $body, Closure $next): string => $next($body.'<script>x()</script>'));

        $post = Blog::create(PostData::fromArray(['title' => 'T', 'status' => 'draft', 'body' => 'clean']));

        $this->assertStringNotContainsString('<script>', $post->refresh()->body);
    }

    #[Test]
    public function the_sanitizer_strips_event_handlers_and_javascript_urls(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'T', 'status' => 'draft',
            'body' => '<a href="javascript:alert(1)">x</a><img src="y" onerror="alert(1)">',
        ]));

        $body = $post->refresh()->body;
        $this->assertStringNotContainsString('onerror', $body);
        $this->assertStringNotContainsString('javascript:', $body);
    }

    #[Test]
    public function recording_a_view_does_not_touch_updated_at(): void
    {
        $post = Post::factory()->create();
        $original = $post->updated_at;

        Blog::recordView($post);

        $this->assertEquals($original, $post->refresh()->updated_at);
    }

    #[Test]
    public function a_comment_email_is_persisted(): void
    {
        $post = Post::factory()->published()->create();

        $this->post("/blog/{$post->slug}/comments", [
            'author_name' => 'Jane', 'email' => 'jane@example.com', 'body' => 'Nice post!',
        ])->assertRedirect();

        $this->assertDatabaseHas('blog_comments', ['author_name' => 'Jane', 'email' => 'jane@example.com']);
    }
}
