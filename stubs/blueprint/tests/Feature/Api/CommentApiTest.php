<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_guest_can_submit_a_comment_but_it_is_not_auto_approved(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Jane',
            'body' => 'Great post!',
        ])->assertCreated();

        $this->assertDatabaseHas('blog_comments', [
            'commentable_id' => $post->id,
            'commentable_type' => 'blog_post',
            'author_name' => 'Jane',
            'approved' => false,
        ]);
    }

    #[Test]
    public function the_honeypot_blocks_bots(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Bot',
            'body' => 'spam',
            'website' => 'http://spam.example',
        ])->assertUnprocessable()->assertJsonValidationErrors(['website']);
    }

    #[Test]
    public function a_client_cannot_self_approve_a_comment(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Jane',
            'body' => 'Great post!',
            'approved' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['approved']);
    }

    #[Test]
    public function the_index_only_returns_approved_comments(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->for($post, 'commentable')->approved()->count(2)->create();
        Comment::factory()->for($post, 'commentable')->create(['approved' => false]);

        $this->getJson("/api/v1/posts/{$post->slug}/comments")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function only_an_admin_can_approve_a_comment(): void
    {
        $comment = Comment::factory()->create(['approved' => false]);

        $this->actingAs($this->createUser())
            ->patchJson("/api/v1/comments/{$comment->id}/approve")
            ->assertForbidden();

        $this->actingAs($this->createAdmin())
            ->patchJson("/api/v1/comments/{$comment->id}/approve")
            ->assertOk();

        $this->assertTrue($comment->refresh()->approved);
    }
}
