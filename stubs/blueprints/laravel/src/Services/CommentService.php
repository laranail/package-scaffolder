<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Owns comment CRUD and data processing. The single source of comment creation
 * for the REST API, Livewire and the CLI — `approved` is decided here from
 * config, never from caller input.
 */
class CommentService
{
    /**
     * @param  array{author_id?: int|string|null, author_name: string, email?: string|null, body: string}  $data
     */
    public function create(Post $post, array $data): Comment
    {
        return $post->comments()->create([
            'author_id' => $data['author_id'] ?? null,
            'author_name' => $data['author_name'],
            'email' => $data['email'] ?? null,
            'body' => $data['body'],
            'approved' => (bool) config('modules.blog.comments.auto_approve', false),
        ]);
    }

    public function approve(Comment $comment): Comment
    {
        $comment->update(['approved' => true]);

        return $comment;
    }

    public function delete(Comment $comment): bool
    {
        return (bool) $comment->delete();
    }

    public function paginateApproved(Post $post, int $perPage = 15): LengthAwarePaginator
    {
        return $post->comments()->approved()->latest()->paginate($perPage);
    }

    public function total(): int
    {
        return Comment::query()->count();
    }

    public function pendingCount(): int
    {
        return Comment::query()->where('approved', false)->count();
    }
}
