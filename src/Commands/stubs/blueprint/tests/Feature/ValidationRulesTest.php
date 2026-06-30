<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [...['title' => 'T', 'body' => 'Body', 'status' => 'draft'], ...$overrides];
    }

    #[Test]
    public function the_config_is_namespaced_under_modules_blog(): void
    {
        // Proves the namespacing is wired (not just falling through to defaults).
        $this->assertSame('Blog', config('modules.blog.name'));
        $this->assertNull(config('blog.name')); // the bare key no longer exists
    }

    #[Test]
    public function views_and_translations_are_namespaced_under_modules_blog(): void
    {
        // Translations resolve under the vendor namespace; the old bare alias is gone.
        $this->assertSame('Posts', __('modules/blog::blog.posts'));
        $this->assertSame('blog::blog.posts', __('blog::blog.posts')); // unregistered → raw key

        // The view namespace resolves too.
        $this->assertTrue(view()->exists('modules/blog::posts.index'));
        $this->assertFalse(view()->exists('blog::posts.index'));
    }

    #[Test]
    public function a_reserved_slug_is_rejected(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', $this->payload(['slug' => 'create']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('slug');
    }

    #[Test]
    public function an_oversized_tag_is_rejected(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', $this->payload(['tags' => [str_repeat('x', 60)]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tags');
    }

    #[Test]
    public function a_valid_tag_list_passes(): void
    {
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', $this->payload(['tags' => ['Laravel', 'PHP']]))
            ->assertCreated();
    }

    #[Test]
    public function a_comment_submitted_too_quickly_is_rejected(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/v1/posts/{$post->slug}/comments", [
            'author_name' => 'Jane',
            'body' => 'Nice post!',
            'rendered_at' => now()->timestamp, // 0 seconds elapsed
        ])->assertStatus(422)->assertJsonValidationErrors('rendered_at');
    }
}
