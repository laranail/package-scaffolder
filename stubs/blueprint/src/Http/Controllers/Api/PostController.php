<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Http\Requests\IndexPostRequest;
use Some\NamespacePath\Blog\Http\Requests\StorePostRequest;
use Some\NamespacePath\Blog\Http\Requests\UpdatePostRequest;
use Some\NamespacePath\Blog\Http\Resources\PostResource;
use Some\NamespacePath\Blog\Models\Post;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Blog $blog,
    ) {}

    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $posts = $this->blog->search(
            $request->filters(),
            $request->integer('per_page') ?: null,
        );

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->blog->create(
            PostData::fromRequest($request)->withAuthor($request->user()->getAuthIdentifier()),
        );

        return PostResource::make($post)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Post $post): PostResource
    {
        return PostResource::make($post->load('category')->loadCount('comments'));
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post = $this->blog->update($post, PostData::fromRequest($request));

        return PostResource::make($post);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $this->blog->delete($post);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }

    public function publish(Post $post): PostResource
    {
        $this->authorize('publish', $post);

        return PostResource::make($this->blog->publish($post));
    }

    public function unpublish(Post $post): PostResource
    {
        $this->authorize('publish', $post);

        return PostResource::make($this->blog->unpublish($post));
    }

    public function restore(Post $post): PostResource
    {
        $this->authorize('restore', $post);
        $this->blog->restore($post);

        return PostResource::make($post);
    }

    public function forceDestroy(Post $post): JsonResponse
    {
        $this->authorize('forceDelete', $post);
        $this->blog->forceDelete($post);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
