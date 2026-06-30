<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Http\Requests\StoreCommentRequest;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Web endpoint backing the non-Livewire <x-{prefix}::comment-form>. Validation,
 * authorization and anti-spam live in StoreCommentRequest; "approved" is decided
 * server-side in the service.
 */
class CommentController extends Controller
{
    public function __construct(
        private readonly Blog $blog,
    ) {}

    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $user = $request->user();

        $this->blog->createComment($post, [
            'author_id' => $user?->getAuthIdentifier(),
            // Fall back gracefully when the host User model has no "name".
            'author_name' => $user?->name ?? ($request->input('author_name') ?: 'Anonymous'),
            // Guest-supplied (for gravatar/notify); authed commenters link via author_id.
            'email' => $request->input('email'),
            'body' => (string) $request->string('body'),
        ]);

        return back()->with('status', __('modules/blog::blog.comment_submitted'));
    }
}
