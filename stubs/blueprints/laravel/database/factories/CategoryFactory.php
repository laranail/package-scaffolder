<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Some\NamespacePath\Blog\Models\Category;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
