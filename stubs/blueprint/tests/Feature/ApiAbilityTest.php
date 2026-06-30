<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class ApiAbilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return ['title' => 'X', 'body' => 'Y', 'status' => 'draft'];
    }

    #[Test]
    public function a_configured_write_ability_is_required(): void
    {
        config()->set('modules.blog.routes.api.abilities.write', 'blog:write');

        $user = $this->createUser();
        $user->tokenAbilities = []; // token lacks the ability

        $this->actingAs($user)
            ->postJson('/api/v1/posts', $this->payload())
            ->assertForbidden();
    }

    #[Test]
    public function a_token_with_the_ability_is_allowed(): void
    {
        config()->set('modules.blog.routes.api.abilities.write', 'blog:write');

        $user = $this->createUser();
        $user->tokenAbilities = ['blog:write'];

        $this->actingAs($user)
            ->postJson('/api/v1/posts', $this->payload())
            ->assertCreated();
    }

    #[Test]
    public function without_a_configured_ability_the_gate_is_a_noop(): void
    {
        // Default: blog.routes.api.abilities.write is null → no gate.
        $this->actingAs($this->createUser())
            ->postJson('/api/v1/posts', $this->payload())
            ->assertCreated();
    }

    #[Test]
    public function a_guest_can_submit_a_web_comment(): void
    {
        $post = Post::factory()->published()->create();

        $this->post("/blog/{$post->slug}/comments", [
            'author_name' => 'Jane',
            'body' => 'Nice post!',
        ])->assertRedirect();

        $this->assertDatabaseHas('blog_comments', [
            'author_name' => 'Jane',
            'approved' => false,
        ]);
    }
}
