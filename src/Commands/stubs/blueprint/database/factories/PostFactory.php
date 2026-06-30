<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'title' => rtrim($title, '.'),
            'excerpt' => fake()->sentence(12),
            'body' => fake()->paragraphs(5, true),
            'status' => PostStatus::Draft,
            'category_id' => Category::factory(),
            'author_id' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => PostStatus::Scheduled,
            'published_at' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
