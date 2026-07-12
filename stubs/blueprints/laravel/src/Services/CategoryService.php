<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Services;

use Illuminate\Database\Eloquent\Collection;
use Some\NamespacePath\Blog\Models\Category;

/**
 * Owns category CRUD and data processing.
 */
class CategoryService
{
    /**
     * @return Collection<int, Category>
     */
    public function listWithCounts(): Collection
    {
        return Category::query()->withCount('posts')->orderBy('name')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }

    public function delete(Category $category): bool
    {
        return (bool) $category->delete();
    }
}
