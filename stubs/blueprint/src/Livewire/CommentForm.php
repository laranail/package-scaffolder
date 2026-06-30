<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post;

/**
 * A comment submission form for a post. Delegates creation to the Blog manager
 * (so the approval rule lives in one place). New comments are never
 * auto-approved unless comments.auto_approve is enabled.
 */
class CommentForm extends Component
{
    public Post $post;

    public string $author_name = '';

    public string $body = '';

    public bool $submitted = false;

    public function mount(Post $post): void
    {
        $this->post = $post;
    }

    public function submit(): void
    {
        // Mirror the HTTP comment path's protections (this is a parallel write path):
        // respect comments.allow_guests, and apply the same per-user/IP rate limit.
        abort_unless(
            auth()->check() || (bool) config('modules.blog.comments.allow_guests', true),
            403,
        );

        $key = 'blog-comments:'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, (int) config('modules.blog.rate_limiting.comments_per_minute', 5))) {
            throw ValidationException::withMessages([
                'body' => __('modules/blog::blog.validation.comment_too_fast'),
            ]);
        }

        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'author_name' => ['required', 'string', 'max:'.(int) config('modules.blog.validation.author_name_max', 100)],
            'body' => ['required', 'string', 'min:2', 'max:'.(int) config('modules.blog.comments.max_length', 2000)],
        ]);

        app(Blog::class)->createComment($this->post, [
            'author_id' => auth()->id(),
            'author_name' => $validated['author_name'],
            'body' => $validated['body'],
        ]);

        $this->reset(['author_name', 'body']);
        $this->submitted = true;
    }

    public function render(): View
    {
        return view('modules/blog::livewire.comment-form');
    }
}
