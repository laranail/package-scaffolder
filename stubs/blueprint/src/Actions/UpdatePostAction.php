<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Illuminate\Support\Facades\DB;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Services\PostService;

class UpdatePostAction
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function __invoke(Post $post, PostData $data): Post
    {
        return DB::transaction(fn (): Post => $this->posts->update($post, $data));
    }

    public function handle(Post $post, PostData $data): Post
    {
        return $this($post, $data);
    }
}
