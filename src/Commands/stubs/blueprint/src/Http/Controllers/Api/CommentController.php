<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Http\Requests\ApproveCommentRequest;
use Some\NamespacePath\Blog\Http\Requests\StoreCommentRequest;
use Some\NamespacePath\Blog\Http\Resources\CommentResource;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Blog $blog,
    ) {}

    /**
     * Approved comments for a post (publicly readable).
     */
    public function index(Post $post): AnonymousResourceCollection
    {
        return CommentResource::collection($this->blog->comments($post));
    }

    /**
     * Submit a comment. Authorization + anti-spam live in StoreCommentRequest;
     * "approved" is decided server-side (in the service), never from the payload.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $user = $request->user();

        $comment = $this->blog->createComment($post, [
            'author_id' => $user?->getAuthIdentifier(),
            'author_name' => $user?->name ?? ($request->input('author_name') ?: 'Anonymous'),
            // Guest-supplied (for gravatar/notify); authed commenters link via author_id.
            'email' => $request->input('email'),
            'body' => (string) $request->string('body'),
        ]);

        return CommentResource::make($comment)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function approve(ApproveCommentRequest $request, Comment $comment): CommentResource
    {
        return CommentResource::make($this->blog->approveComment($comment));
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $this->blog->deleteComment($comment);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
