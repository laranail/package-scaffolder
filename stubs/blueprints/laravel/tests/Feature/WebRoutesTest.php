<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class WebRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_index_lists_published_posts(): void
    {
        Post::factory()->published()->create(['title' => 'A web post']);

        $this->actingAs($this->createUser())
            ->get('/blog')
            ->assertOk()
            ->assertSee('A web post');
    }

    #[Test]
    public function a_published_post_page_renders(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Readable']);

        $this->actingAs($this->createUser())
            ->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('Readable');
    }

    #[Test]
    public function a_draft_page_is_hidden_from_other_users(): void
    {
        $draft = Post::factory()->create(['author_id' => $this->createUser()->id]);

        $this->actingAs($this->createUser())
            ->get("/blog/{$draft->slug}")
            ->assertNotFound();
    }

    #[Test]
    public function the_create_form_renders(): void
    {
        $this->actingAs($this->createUser())
            ->get('/blog/create')
            ->assertOk();
    }

    #[Test]
    public function a_post_can_be_created_via_the_web_form(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->post('/blog', [
                'title' => 'Posted from the web',
                'body' => 'Some body content.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Posted from the web',
            'author_id' => $user->id,
        ]);
    }
}
