<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Observers;

use Illuminate\Support\Str;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Events\PostPublished;
use Some\NamespacePath\Blog\Events\PostUnpublished;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Processing\BodyProcessor;
use Some\NamespacePath\Blog\Providers\BlogServiceProvider;

/**
 * Keeps post invariants intact across its lifecycle and is the single source
 * of the {@see PostPublished} event (no action or service dispatches it
 * directly, so it fires exactly once per published transition). Registered in
 * {@see BlogServiceProvider}.
 */
class PostObserver
{
    public function creating(Post $post): void
    {
        // Derive a readable excerpt when the author left it blank.
        if (empty($post->excerpt) && ! empty($post->body)) {
            $post->excerpt = Str::limit(strip_tags($post->body), 160);
        }
    }

    public function saving(Post $post): void
    {
        // A post can only carry a publish date once it is actually published.
        if ($post->status === PostStatus::Published && $post->published_at === null) {
            $post->published_at = now();
        }

        // A scheduled post with no date can never go live (scopeDue requires a date)
        // and is hidden in the meantime — so it would be stranded. Demote it to a
        // draft. The HTTP path rejects this at validation (clearer feedback); this
        // covers every other writer (CLI, [[plugins]]Filament, Nova, [[/plugins]]raw Eloquent).
        if ($post->status === PostStatus::Scheduled && $post->published_at === null) {
            $post->status = PostStatus::Draft;
        }

        // Run the body through the save-time processing pipeline (sanitize +
        // any consumer stages) here so EVERY writer — facade/Action, [[plugins]]Filament,[[/plugins]]
        // [[plugins]]Nova, [[/plugins]]raw Eloquent — is consistent. Only when the body actually changed.
        // Cast handles a null/unset body; "0" is a valid (non-empty) body.
        if ($post->isDirty('body') && (string) $post->body !== '') {
            $post->body = app(BodyProcessor::class)->process((string) $post->body);
        }
    }

    public function created(Post $post): void
    {
        // A post created already in the published state announces itself too —
        // the `updated` hook below only covers later transitions.
        if ($post->isPublished()) {
            PostPublished::dispatch($post);
        }
    }

    public function forceDeleted(Post $post): void
    {
        // Polymorphic comments/tags have no DB-level cascade — clean them up on a
        // hard delete (a soft delete intentionally leaves them, since it's recoverable).
        $post->comments()->delete();
        $post->tags()->detach();
    }

    public function updated(Post $post): void
    {
        if (! $post->wasChanged('status')) {
            return;
        }

        // Compare against the RAW previous value: getOriginal() would return the
        // cast enum, which never equals the string ->value.
        $wasPublished = $post->getRawOriginal('status') === PostStatus::Published->value;
        $isPublished = $post->status === PostStatus::Published;

        // Announce the transition into "published" exactly once.
        if ($isPublished && ! $wasPublished) {
            PostPublished::dispatch($post);

            return;
        }

        // Announce leaving the published state (publish → draft/archived).
        if (! $isPublished && $wasPublished) {
            PostUnpublished::dispatch($post);
        }
    }
}
