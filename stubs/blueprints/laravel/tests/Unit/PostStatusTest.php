<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Tests\TestCase;

class PostStatusTest extends TestCase
{
    #[Test]
    public function only_published_is_visible(): void
    {
        $this->assertTrue(PostStatus::Published->isVisible());
        $this->assertFalse(PostStatus::Draft->isVisible());
        $this->assertFalse(PostStatus::Scheduled->isVisible());
        $this->assertFalse(PostStatus::Archived->isVisible());
    }

    #[Test]
    public function options_are_keyed_by_value(): void
    {
        $options = PostStatus::options();

        $this->assertSame('Draft', $options['draft']);
        $this->assertCount(4, $options);
    }

    #[Test]
    public function values_lists_every_case(): void
    {
        $this->assertEqualsCanonicalizing(
            ['draft', 'scheduled', 'published', 'archived'],
            PostStatus::values(),
        );
    }
}
