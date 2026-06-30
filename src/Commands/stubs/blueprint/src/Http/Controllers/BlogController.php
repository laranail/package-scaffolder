<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Http\Requests\StorePostRequest;
use Some\NamespacePath\Blog\Http\Requests\UpdatePostRequest;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;

class BlogController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Blog $blog,
    ) {
        $this->authorizeResource(Post::class, 'post');
    }

    public function index(): View
    {
        return view('modules/blog::posts.index', [
            'posts' => $this->blog->feed(),
        ]);
    }

    public function create(): View
    {
        return view('modules/blog::posts.create', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $post = $this->blog->create(
            PostData::fromRequest($request)->withAuthor($request->user()->getAuthIdentifier()),
        );

        return redirect()
            ->route('blog.show', $post)
            ->with('status', __('modules/blog::blog.post_created'));
    }

    public function show(Post $post): View
    {
        // Opt-in view counting (atomic). Enable with config('modules.blog.features.count_views').
        if (config('modules.blog.features.count_views', false)) {
            $this->blog->recordView($post);
        }

        return view('modules/blog::posts.show', [
            // Eager-load what the post view actually renders: category + tags. Comments
            // are NOT loaded here — the <x-comments> component fetches approved-only.
            'post' => $post->load('category', 'tags'),
        ]);
    }

    public function edit(Post $post): View
    {
        return view('modules/blog::posts.edit', [
            'post' => $post,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->blog->update($post, PostData::fromRequest($request));

        return redirect()
            ->route('blog.show', $post)
            ->with('status', __('modules/blog::blog.post_updated'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->blog->delete($post);

        return redirect()
            ->route('blog.index')
            ->with('status', __('modules/blog::blog.post_deleted'));
    }
}
