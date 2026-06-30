<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Illuminate\Support\Facades\DB;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Services\CommentService;

/**
 * Use-case: create a comment on a post via {@see CommentService::create()}.
 * The single write path for the REST API, Livewire and the CLI — and the
 * natural place to add spam screening or events later.
 *
 * @phpstan-param array{author_id?: int|string|null, author_name: string, body: string} $data
 */
class CreateCommentAction
{
    public function __construct(
        private readonly CommentService $comments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Post $post, array $data): Comment
    {
        return DB::transaction(fn (): Comment => $this->comments->create($post, $data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Post $post, array $data): Comment
    {
        return $this($post, $data);
    }
}
