<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Services;

use Illuminate\Database\Eloquent\Collection;
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
     *
     * @param  array<int, string>  $names
     */
    public function syncForPost(Post $post, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($name): string => trim((string) $name))
            ->filter()
            ->unique()
            ->map(fn (string $name): int => Tag::query()->firstOrCreate(['name' => $name])->id)
            ->all();

        $post->tags()->sync($ids);
    }

    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }
}
