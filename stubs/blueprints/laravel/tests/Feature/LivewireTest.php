<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Livewire\CommentForm;
use Some\NamespacePath\Blog\Livewire\PostList;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class LivewireTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_post_list_component_searches(): void
    {
        Post::factory()->published()->create(['title' => 'Alpha release']);
        Post::factory()->published()->create(['title' => 'Beta notes']);

        Livewire::test(PostList::class)
            ->assertSee('Alpha release')
            ->assertSee('Beta notes')
            ->set('search', 'Alpha')
            ->assertSee('Alpha release')
            ->assertDontSee('Beta notes');
    }

    #[Test]
    public function the_comment_form_creates_an_unapproved_comment(): void
    {
        $post = Post::factory()->published()->create();

        Livewire::test(CommentForm::class, ['post' => $post])
            ->set('author_name', 'Jane')
            ->set('body', 'Nice write-up!')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('blog_comments', [
            'commentable_id' => $post->id,
            'commentable_type' => 'blog_post',
            'author_name' => 'Jane',
            'approved' => false,
        ]);
    }

    /** Chunk 5 #17 — the Livewire form must honour comments.allow_guests (like the HTTP path). */
    #[Test]
    public function the_livewire_comment_form_rejects_guests_when_disabled(): void
    {
        config()->set('modules.blog.comments.allow_guests', false);
        $post = Post::factory()->published()->create();

        Livewire::test(CommentForm::class, ['post' => $post])
            ->set('author_name', 'Bot')
            ->set('body', 'spam comment')
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, Comment::query()->count());
    }

    /** Chunk 5 #17 — the Livewire form must be rate limited (like the throttled HTTP route). */
    #[Test]
    public function the_livewire_comment_form_is_rate_limited(): void
    {
        config()->set('modules.blog.rate_limiting.comments_per_minute', 2);
        $post = Post::factory()->published()->create();

        for ($i = 0; $i < 2; $i++) {
            Livewire::test(CommentForm::class, ['post' => $post])
                ->set('author_name', 'Jane')
                ->set('body', "comment number {$i}")
                ->call('submit')
                ->assertHasNoErrors();
        }

        // The third within the window is throttled.
        Livewire::test(CommentForm::class, ['post' => $post])
            ->set('author_name', 'Jane')
            ->set('body', 'one too many')
            ->call('submit')
            ->assertHasErrors('body');

        $this->assertSame(2, Comment::query()->count());

        RateLimiter::clear('blog-comments:127.0.0.1');
    }
}
