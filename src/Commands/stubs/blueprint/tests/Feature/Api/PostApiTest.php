<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_returns_only_published_posts(): void
    {
        Post::factory()->count(3)->published()->create();
        Post::factory()->count(2)->create();

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function a_draft_is_hidden_from_guests(): void
    {
        $draft = Post::factory()->create(); // draft

        $this->getJson("/api/v1/posts/{$draft->slug}")->assertNotFound();
    }

    #[Test]
    public function a_published_post_is_visible(): void
    {
        $post = Post::factory()->published()->create();

        $this->getJson("/api/v1/posts/{$post->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $post->slug);
    }

    #[Test]
    public function guests_cannot_create_posts(): void
    {
        $this->postJson('/api/v1/posts', ['title' => 'X', 'body' => 'Y', 'status' => 'draft'])
            ->assertUnauthorized();
    }

    #[Test]
    public function an_authenticated_user_can_create_a_post(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson('/api/v1/posts', [
                'title' => 'Hello API',
                'body' => 'Body content here.',
                'status' => 'published',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Hello API');

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Hello API',
            'author_id' => $user->id,
        ]);
    }

    #[Test]
    public function creating_a_post_validates_input(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', ['title' => '', 'status' => 'nonsense'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body', 'status']);
    }

    #[Test]
    public function a_client_cannot_set_the_author(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', [
                'title' => 'X', 'body' => 'Y', 'status' => 'draft', 'author_id' => 999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['author_id']);
    }

    #[Test]
    public function only_the_owner_can_update_a_post(): void
    {
        $owner = $this->createUser();
        $post = Post::factory()->published()->create(['author_id' => $owner->id]);

        $this->actingAs($this->createUser())
            ->patchJson("/api/v1/posts/{$post->slug}", ['title' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patchJson("/api/v1/posts/{$post->slug}", ['title' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated');
    }

    #[Test]
    public function the_owner_can_publish_and_unpublish(): void
    {
        $owner = $this->createUser();
        $post = Post::factory()->create(['author_id' => $owner->id]);

        $this->actingAs($owner)->postJson("/api/v1/posts/{$post->slug}/publish")->assertOk();
        $this->assertSame(PostStatus::Published, $post->refresh()->status);

        $this->actingAs($owner)->postJson("/api/v1/posts/{$post->slug}/unpublish")->assertOk();
        $this->assertSame(PostStatus::Draft, $post->refresh()->status);
    }

    #[Test]
    public function a_post_can_be_deleted_restored_and_force_deleted(): void
    {
        $owner = $this->createUser();
        $post = Post::factory()->create(['author_id' => $owner->id]);

        $this->actingAs($owner)->deleteJson("/api/v1/posts/{$post->slug}")->assertNoContent();
        $this->assertSoftDeleted($post);

        $this->actingAs($owner)->postJson("/api/v1/posts/{$post->slug}/restore")->assertOk();
        $this->assertNotSoftDeleted($post);

        $this->actingAs($owner)->deleteJson("/api/v1/posts/{$post->slug}/force")->assertNoContent();
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }

    #[Test]
    public function an_invalid_sort_column_is_rejected(): void
    {
        $this->getJson('/api/v1/posts?sort=id')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort']);
    }

    #[Test]
    public function the_feed_can_be_searched(): void
    {
        Post::factory()->published()->create(['title' => 'Laravel rocks']);
        Post::factory()->published()->create(['title' => 'Something else']);

        $this->getJson('/api/v1/posts?search=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
