<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Some\NamespacePath\Blog\Models\Tag;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
        ];
    }
}
