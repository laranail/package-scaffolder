<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests;

use PHPUnit\Framework\TestCase;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Providers\BlogServiceProvider;
use Some\NamespacePath\Blog\Repositories\InMemoryPostRepository;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    public function test_service_provider_registers_in_a_symfony_container(): void
    {
        // mirrors how laranail/package-management's SymfonyLoaderAdapter loads the plugin
        $container = new ContainerBuilder;
        $container->set(BlogServiceProvider::class, new BlogServiceProvider);

        $provider = $container->get(BlogServiceProvider::class);

        $this->assertInstanceOf(BlogServiceProvider::class, $provider);
        $this->assertInstanceOf(Blog::class, $provider->blog());
    }
}
