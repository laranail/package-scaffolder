<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class ConsoleCommandTest extends TestCase
{
    use RefreshDatabase;

    // @artifact:start scheduling
    #[Test]
    public function it_publishes_due_scheduled_posts(): void
    {
        $due = Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'published_at' => now()->subMinute(),
        ]);
        $future = Post::factory()->scheduled()->create([
            'published_at' => now()->addWeek(),
        ]);

        $this->artisan('blog:publish-scheduled')->assertSuccessful();

        $this->assertSame(PostStatus::Published, $due->refresh()->status);
        $this->assertSame(PostStatus::Scheduled, $future->refresh()->status);
    }
    // @artifact:end scheduling

    #[Test]
    public function the_stats_command_runs(): void
    {
        Post::factory()->count(2)->published()->create();

        $this->artisan('blog:stats')->assertSuccessful();
    }

    #[Test]
    public function the_post_list_command_runs(): void
    {
        Post::factory()->count(3)->create();

        $this->artisan('blog:post:list')->assertSuccessful();
    }

    #[Test]
    public function the_publish_command_publishes_a_post(): void
    {
        $post = Post::factory()->create();

        $this->artisan('blog:post:publish', ['post' => $post->slug])->assertSuccessful();

        $this->assertSame(PostStatus::Published, $post->refresh()->status);
    }
}
