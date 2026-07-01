<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests;

use PHPUnit\Framework\TestCase;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Repositories\InMemoryPostRepository;

final class BlogTest extends TestCase
{
    public function test_creates_and_retrieves_a_post(): void
    {
        $blog = new Blog(new InMemoryPostRepository);

        $post = $blog->create('Hello', 'World');

        $this->assertNotNull($post->id);
        $this->assertSame('Hello', $post->title);
        $this->assertSame($post->id, $blog->find($post->id)?->id);
        $this->assertCount(1, $blog->all());
    }
}
