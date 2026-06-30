<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Illuminate\Support\Facades\DB;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Services\PostService;

/**
 * Use-case: create a post. Wraps {@see PostService::create()} in a transaction.
 * The published_at stamp and the PostPublished event are handled by the observer.
 */
class CreatePostAction
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function __invoke(PostData $data): Post
    {
        return DB::transaction(fn (): Post => $this->posts->create($data));
    }

    public function handle(PostData $data): Post
    {
        return $this($data);
    }
}
