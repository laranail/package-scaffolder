<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Rules\NotSubmittedTooQuickly;

/**
 * Validates a public comment submission. Two anti-spam measures back the
 * normal validation: a honeypot field that must stay empty, and a minimum
 * elapsed time since the form was rendered. `approved` is never accepted from
 * the client — it is decided server-side from config.
 */
class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Comment::class)
            ?? (bool) config('modules.blog.comments.allow_guests', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $honeypot = (string) config('modules.blog.comments.honeypot', 'website');

        return [
            'author_name' => ['required', 'string', 'max:'.(int) config('modules.blog.validation.author_name_max', 100)],
            'email' => ['nullable', 'email', 'max:'.(int) config('modules.blog.validation.email_max', 255)],
            'body' => ['required', 'string', 'min:2', 'max:'.(int) config('modules.blog.comments.max_length', 2000)],

            // Honeypot: bots fill it, humans never see it. Must be empty.
            $honeypot => ['prohibited'],

            // Anti-flood: the rendered_at timestamp must be old enough.
            'rendered_at' => ['nullable', 'numeric', new NotSubmittedTooQuickly],

            // approved is server-controlled; reject any attempt to set it.
            'approved' => ['prohibited'],
        ];
    }
}
