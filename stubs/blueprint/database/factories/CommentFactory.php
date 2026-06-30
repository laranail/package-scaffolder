<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Default commentable is a Post; the morph type stores the alias
            // ('blog_post') via the morph map, not the placeholder FQCN.
            'commentable_id' => Post::factory(),
            'commentable_type' => (new Post)->getMorphClass(),
            'author_id' => null,
            'author_name' => fake()->name(),
            'body' => fake()->paragraph(),
            'approved' => fake()->boolean(70),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['approved' => true]);
    }
}
