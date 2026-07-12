<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Actions\CreatePostAction;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Tests\TestCase;

class CreatePostActionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_post_and_generates_a_slug(): void
    {
        $post = app(CreatePostAction::class)->handle(PostData::fromArray([
            'title' => 'Hello World',
            'body' => 'Some body content for the post.',
            'status' => PostStatus::Draft,
        ]));

        $this->assertDatabaseHas('blog_posts', ['title' => 'Hello World']);
        $this->assertSame('hello-world', $post->slug);
    }

    #[Test]
    public function publishing_immediately_stamps_published_at(): void
    {
        $post = app(CreatePostAction::class)->handle(PostData::fromArray([
            'title' => 'Live Now',
            'body' => 'Body.',
            'status' => PostStatus::Published,
        ]));

        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->isPublished());
    }
}
