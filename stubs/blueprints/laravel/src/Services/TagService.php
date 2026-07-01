<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;

/**
 * Owns tag CRUD and post↔tag syncing.
 */
class TagService
{
    /**
     * @return Collection<int, Tag>
     */
    public function listWithCounts(): Collection
    {
        return Tag::query()->withCount('posts')->orderBy('name')->get();
    }

    /**
     * Resolve tag names to ids (creating missing ones) and sync them onto a post.
     * Matching is case-insensitive by NAME, so "Laravel" / "laravel" collapse to one
     * tag — but distinct names that happen to share a slug ("C++" / "C#", both slug
     * "c") stay separate (the slug column itself stays unique via the HasSlug trait).
     *
     * @param  array<int, string>  $names
     */
    public function syncForPost(Post $post, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->reject(fn (string $name): bool => Str::slug($name) === '') // no slug → not a usable tag
            ->unique(fn (string $name): string => Str::lower($name))     // dedupe case variants within the request
            ->map(fn (string $name): int => $this->resolveTagId($name))
            ->all();

        $post->tags()->sync($ids);
    }

    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }

    /**
     * Find a tag by case-insensitive name (so "Laravel" reuses an existing
     * "laravel"), creating it if absent. LOWER() keeps this consistent across
     * drivers — MySQL is case-insensitive by default, SQLite/Postgres are not.
     */
    private function resolveTagId(string $name): int
    {
        $id = Tag::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->value('id');

        return $id !== null ? (int) $id : Tag::query()->create(['name' => $name])->id;
    }
}
