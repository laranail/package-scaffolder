<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Some\NamespacePath\Blog\Database\Seeders\PostSeeder;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Tests\TestCase;

class SeederSmokeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_post_seeder_creates_no_stray_records(): void
    {
        $this->seed(PostSeeder::class);

        // 15 published + 5 drafts + 3 scheduled = 23 — the has(Comment) default must NOT spawn extra posts.
        $this->assertSame(23, Post::query()->count());
        $this->assertSame(45, Comment::query()->count()); // 15 × 3
        // Every comment morphs to a real post via the alias.
        $this->assertSame(45, DB::table('blog_comments')->where('commentable_type', 'blog_post')->count());
    }
}
