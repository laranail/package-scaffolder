<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Events\CommentApproved;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Notifications\PostPublishedNotification;
use Some\NamespacePath\Blog\Tests\TestCase;

/**
 * Regression tests for the end-to-end review/hardening pass. Each test pins a fixed
 * bug so it can't silently come back.
 */
class ReviewHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** Chunk 1 #3 — a scheduled post with no date stranded itself (never published, never visible). */
    #[Test]
    public function a_scheduled_post_requires_a_future_published_at(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', ['title' => 'S', 'body' => 'B', 'status' => 'scheduled'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_at']);

        // A past date is rejected too (can't schedule into the past).
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', [
                'title' => 'S2', 'body' => 'B', 'status' => 'scheduled',
                'published_at' => now()->subDay()->toIso8601String(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['published_at']);

        // A future date is accepted.
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', [
                'title' => 'S3', 'body' => 'B', 'status' => 'scheduled',
                'published_at' => now()->addDay()->toIso8601String(),
            ])
            ->assertCreated();
    }

    /** Chunk 1 #1 — featured_image column was varchar(255) but validation allows url max:2048. */
    #[Test]
    public function a_long_featured_image_url_is_accepted_and_not_truncated(): void
    {
        $url = 'https://cdn.example.com/'.str_repeat('a', 1000).'.jpg'; // ~1024 chars, > 255

        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', [
                'title' => 'F', 'body' => 'B', 'status' => 'draft', 'featured_image' => $url,
            ])
            ->assertCreated();

        $this->assertSame($url, Post::query()->where('title', 'F')->value('featured_image'));
    }

    /** Chunk 1 #2 — the published feed's hot path needs a composite (status, published_at) index. */
    #[Test]
    public function the_posts_table_has_a_composite_status_published_at_index(): void
    {
        $columns = collect(Schema::getIndexes('blog_posts'))
            ->map(fn (array $index): array => array_values($index['columns']));

        $this->assertTrue(
            $columns->contains(['status', 'published_at']),
            'Expected a composite index on (status, published_at) for the published feed.',
        );
    }

    /** Chunk 2 #6 — findByKey('a-slug') must not add an `id = 'a-slug'` branch (Postgres-safe). */
    #[Test]
    public function find_by_key_resolves_slugs_and_ids_without_an_id_type_error(): void
    {
        $post = Post::factory()->create(['slug' => 'a-real-slug']);

        $this->assertTrue(Blog::findByKey('a-real-slug')?->is($post));   // by slug
        $this->assertTrue(Blog::findByKey((string) $post->id)?->is($post)); // by numeric id
        $this->assertNull(Blog::findByKey('no-such-post'));               // non-numeric miss, no error
    }

    /** Chunk 2 #7 — popularPosts ranks/counts by APPROVED comments only (no spam/pending inflation). */
    #[Test]
    public function popular_posts_count_only_approved_comments(): void
    {
        $hot = Post::factory()->published()->create();
        $quiet = Post::factory()->published()->create();

        // $hot has 1 approved + 3 pending; $quiet has 2 approved.
        Comment::factory()->for($hot, 'commentable')->approved()->create();
        Comment::factory()->for($hot, 'commentable')->count(3)->create(['approved' => false]);
        Comment::factory()->for($quiet, 'commentable')->approved()->count(2)->create();

        $popular = Blog::popularPosts(2);

        // Ranked by approved count: $quiet (2) before $hot (1) — pending comments ignored.
        $this->assertTrue($popular->first()->is($quiet));
        $this->assertSame(2, (int) $popular->first()->comments_count);
        $this->assertSame(1, (int) $popular->last()->comments_count);
    }

    /** Chunk 3 #10 — the feed eager-loads category so post-card listings don't N+1. */
    #[Test]
    public function the_feed_eager_loads_the_category_relation(): void
    {
        Post::factory()->published()->count(3)->create();

        $feed = Blog::feed();

        $this->assertNotEmpty($feed->items());
        foreach ($feed->items() as $post) {
            $this->assertTrue($post->relationLoaded('category'), 'category should be eager-loaded');
        }
    }

    /** Chunk 3 #11 — a non-token (session/web) user must not be falsely 403'd when an ability is set. */
    #[Test]
    public function a_session_user_is_not_blocked_by_the_token_ability_gate(): void
    {
        config()->set('modules.blog.routes.api.abilities.write', 'blog:write');

        $user = $this->createUser();
        $user->hasToken = false; // authenticated, but not via an access token

        $this->actingAs($user)
            ->postJson('/api/v1/posts', ['title' => 'X', 'body' => 'Y', 'status' => 'draft'])
            ->assertCreated();
    }

    /**
     * Chunk 4 #14 — the HTML sanitizer must resist the obfuscation bypasses that a
     * whitespace-only / literal-string filter missed.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function xssBypassPayloads(): array
    {
        return [
            'slash-separated handler (no space)' => ['<a/onmouseover=alert(1)>x</a>', 'onmouseover'],
            'slash-separated handler on img' => ['<img/onerror=alert(1)>', 'onerror'],
            'entity-encoded scheme' => ['<a href="java&#115;cript:alert(1)">x</a>', 'alert'],
            'tab-obfuscated scheme' => ['<a href="java&Tab;script:alert(1)">x</a>', 'alert'],
            'uppercase event handler' => ['<a href="#" OnClick="alert(1)">x</a>', 'alert'],
            'plain javascript url' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
        ];
    }

    #[Test]
    #[DataProvider('xssBypassPayloads')]
    public function the_sanitizer_resists_obfuscated_xss(string $payload, string $needle): void
    {
        $post = Blog::create(PostData::fromArray(['title' => 'T', 'status' => 'draft', 'body' => $payload]));

        $body = $post->refresh()->body;

        $this->assertStringNotContainsStringIgnoringCase($needle, $body);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $body);
        $this->assertStringNotContainsStringIgnoringCase('vbscript:', $body);
        // An event-handler attribute must never survive in any form.
        $this->assertDoesNotMatchRegularExpression('/\bon[a-z]+\s*=/i', $body);
    }

    /** The hardened sanitizer must not mangle legitimate content (no false positives). */
    #[Test]
    public function the_sanitizer_preserves_legitimate_content(): void
    {
        $body = '<p>See <a href="https://example.com/online=2?x=1">the docs</a> and '
            .'<img src="https://cdn.example.com/a.png"> — version/online=2 is fine.</p>';

        $post = Blog::create(PostData::fromArray(['title' => 'T', 'status' => 'draft', 'body' => $body]));
        $clean = $post->refresh()->body;

        $this->assertStringContainsString('https://example.com/online=2?x=1', $clean);
        $this->assertStringContainsString('https://cdn.example.com/a.png', $clean);
        $this->assertStringContainsString('version/online=2 is fine.', $clean); // text not mangled
    }

    /** Chunk 4 #15 — the publish notification links via the named (configurable) route, not a hardcoded /blog path. */
    #[Test]
    public function the_publish_notification_uses_the_named_route_for_its_link(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'my-live-post']);

        $mail = (new PostPublishedNotification($post))
            ->toMail($this->createUser());

        $this->assertSame(route('blog.show', $post->slug), $mail->actionUrl);
        $this->assertStringContainsString('my-live-post', (string) $mail->actionUrl);
    }

    /** Chunk 6 #18 — bulk `comment:approve --all` must fire CommentApproved (cache flush + listeners). */
    #[Test]
    public function bulk_approving_comments_fires_the_approved_event_per_comment(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->for($post, 'commentable')->count(3)->create(['approved' => false]);

        Event::fake([CommentApproved::class]);

        $this->artisan('blog:comment:approve', ['--all' => true])->assertSuccessful();

        Event::assertDispatchedTimes(CommentApproved::class, 3);
        $this->assertSame(0, Comment::query()->where('approved', false)->count());
    }

    /** Chunk 7 #21 — a bad --status flag is a clean failure, not a ValueError stack trace. */
    #[Test]
    public function creating_a_post_with_an_invalid_status_flag_fails_gracefully(): void
    {
        $this->artisan('blog:post:create', ['--title' => 'Hi', '--status' => 'bogus'])
            ->assertFailed();

        $this->assertSame(0, Post::query()->count());
    }
}
