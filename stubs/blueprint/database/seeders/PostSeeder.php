<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->get();

        if ($categories->isEmpty()) {
            $categories = Category::factory()->count(3)->create();
        }

        Post::factory()
            ->count(15)
            ->published()
            ->recycle($categories)
            ->has(Comment::factory()->count(3)->approved())
            ->create();

        Post::factory()->count(5)->create();        // drafts
        Post::factory()->count(3)->scheduled()->create();
    }
}
