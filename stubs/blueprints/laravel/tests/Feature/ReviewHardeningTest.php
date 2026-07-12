<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Events\CommentApproved;
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\Http\Resources\PostResource;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
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

    /**
     * Chunk 1 #3 (observer) — non-HTTP writers (CLI/[[plugins]]Filament/Nova/[[/plugins]]raw Eloquent) that save a
     * scheduled post with no date get it demoted to draft, so it can never strand.
     */
    #[Test]
    public function a_dateless_scheduled_post_is_demoted_to_draft_on_save(): void
    {
        // Raw save (bypasses the FormRequest[[plugins]], like Filament/Nova/CLI[[/plugins]]).
        $stranded = Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'published_at' => null,
        ]);

        $this->assertSame(PostStatus::Draft, $stranded->refresh()->status);

        // A properly-dated scheduled post is left untouched.
        $valid = Post::factory()->scheduled()->create();
        $this->assertSame(PostStatus::Scheduled, $valid->refresh()->status);
        $this->assertNotNull($valid->published_at);
    }

    /** Chunk 6 #20 — the number of tags per post is capped (a post can't attach thousands). */
    #[Test]
    public function the_tag_count_is_capped(): void
    {
        config()->set('modules.blog.validation.tag_count_max', 3);

        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', [
                'title' => 'T', 'body' => 'B', 'status' => 'draft',
                'tags' => ['a', 'b', 'c', 'd'], // 4 > 3
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tags']);
    }

    /** Chunk 2 #8 — case/format variants of a tag name collapse to a single tag. */
    #[Test]
    public function tag_case_variants_collapse_to_one_tag(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'T', 'body' => 'B', 'status' => 'draft',
            'tags' => ['Laravel', 'laravel', 'LARAVEL', 'Laravel '],
        ]));

        $this->assertSame(1, Tag::query()->count());
        $this->assertSame(1, $post->tags()->count());
        $this->assertSame('laravel', Tag::query()->value('slug'));
    }

    /** #8 (regression) — distinct names that share a slug ("C++"/"C#") must NOT collapse. */
    #[Test]
    public function distinct_tag_names_sharing_a_slug_stay_separate(): void
    {
        $post = Blog::create(PostData::fromArray([
            'title' => 'T', 'body' => 'B', 'status' => 'draft',
            'tags' => ['C++', 'C#', 'F#'],
        ]));

        $this->assertSame(3, $post->tags()->count());
        $this->assertEqualsCanonicalizing(['C++', 'C#', 'F#'], $post->tags->pluck('name')->all());
    }

    /** #5 — reading_time is Unicode-aware (str_word_count would return 0 → 1 min for CJK). */
    #[Test]
    public function reading_time_counts_non_ascii_text(): void
    {
        // ~600 CJK "words" at 200/min ⇒ 3 minutes (str_word_count would have given 1).
        $cjk = str_repeat('文', 600);
        $post = Post::factory()->create(['body' => $cjk]);

        $this->assertSame(3, $post->reading_time);

        // Accented Latin counts each whitespace-delimited token (not just a–z runs).
        $accented = Post::factory()->create(['body' => trim(str_repeat('café résumé ', 100))]); // 200 words
        $this->assertSame(1, $accented->reading_time);
    }

    /** #4 — LIKE wildcards in a search term are escaped (matched literally). */
    #[Test]
    public function search_escapes_like_wildcards(): void
    {
        $this->assertSame('50\%off', Post::escapeLike('50%off'));
        $this->assertSame('a\_b', Post::escapeLike('a_b'));

        Post::factory()->published()->create(['title' => 'Big 50% discount']);
        Post::factory()->published()->create(['title' => 'Totally unrelated']);

        // '%' is treated literally — only the post whose title actually contains "50%".
        $results = Blog::search(['search' => '50%']);
        $this->assertCount(1, $results->items());
    }

    /** #9 — fromArray raises a clear exception (not a raw ValueError) on a bad status. */
    #[Test]
    public function post_data_from_array_rejects_an_invalid_status_clearly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid post status [nonsense]');

        PostData::fromArray(['title' => 'T', 'status' => 'nonsense']);
    }

    /** #13 — PostResource never exposes unapproved comments, even if they were eager-loaded. */
    #[Test]
    public function post_resource_hides_unapproved_comments_even_when_loaded(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->for($post, 'commentable')->approved()->create(['body' => 'approved one']);
        Comment::factory()->for($post, 'commentable')->create(['approved' => false, 'body' => 'secret pending']);

        // Force-load ALL comments (the unsafe consumer pattern) then resource it.
        $payload = (new PostResource($post->load('comments')))
            ->toArray(request());

        $bodies = collect($payload['comments']->resolve())->pluck('body');
        $this->assertContains('approved one', $bodies);
        $this->assertNotContains('secret pending', $bodies);
    }

    /** #13 (consistency) — the API show's comments_count reflects approved comments only. */
    #[Test]
    public function the_api_show_comment_count_excludes_pending(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->for($post, 'commentable')->approved()->create();
        Comment::factory()->for($post, 'commentable')->count(2)->create(['approved' => false]);

        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.comments_count', 1);
    }

    /** Chunk C — tags are admin-only taxonomy (a TagPolicy gates panel CRUD, like categories). */
    #[Test]
    public function tag_management_is_restricted_to_admins(): void
    {
        $tag = Tag::factory()->create();

        $user = $this->createUser();
        $admin = $this->createAdmin();

        // Reads are open; writes require admin (the policy the panels enforce).
        $this->assertTrue($user->can('viewAny', Tag::class));
        $this->assertFalse($user->can('create', Tag::class));
        $this->assertFalse($user->can('delete', $tag));

        $this->assertTrue($admin->can('create', Tag::class));
        $this->assertTrue($admin->can('update', $tag));
        $this->assertTrue($admin->can('delete', $tag));
    }
}
