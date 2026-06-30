<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Rules\NotReservedSlug;
use Some\NamespacePath\Blog\Rules\ValidTagList;

/**
 * Single source of the post validation rules, shared by Store/UpdatePostRequest.
 * The only difference between create and update is `required` vs `sometimes` and
 * the unique-slug ignore id. All limits come from config('modules.blog.validation').
 */
trait ProvidesPostRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function postRules(bool $creating, ?int $ignoreId = null): array
    {
        $present = $creating ? ['required'] : ['sometimes', 'required'];

        return [
            'title' => [...$present, 'string', 'max:'.$this->limit('title_max', 255)],
            'slug' => [
                'nullable', 'string', 'alpha_dash', 'max:'.$this->limit('slug_max', 255),
                new NotReservedSlug,
                Rule::unique(Post::class, 'slug')->ignore($ignoreId),
            ],
            'excerpt' => ['nullable', 'string', 'max:'.$this->limit('excerpt_max', 500)],
            'body' => [...$present, 'string', 'max:'.$this->limit('body_max', 65535)],
            'meta_title' => ['nullable', 'string', 'max:'.$this->limit('meta_title_max', 255)],
            'meta_description' => ['nullable', 'string', 'max:'.$this->limit('meta_description_max', 500)],
            'featured_image' => ['nullable', 'string', 'url', 'max:'.$this->limit('url_max', 2048)],
            'is_featured' => ['sometimes', 'boolean'],
            'tags' => ['nullable', 'array', new ValidTagList],
            'status' => [...$present, Rule::enum(PostStatus::class)],
            'category_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
            'published_at' => [
                'nullable', 'date',
                // A scheduled post MUST carry a future date — otherwise scopeDue
                // (which requires a non-null published_at) never publishes it and
                // scopePublished hides it, stranding the post permanently.
                Rule::when(
                    $this->input('status') === PostStatus::Scheduled->value,
                    ['required', 'after_or_equal:now'],
                ),
            ],
            // Authorship is always derived server-side from the authenticated user.
            'author_id' => ['prohibited'],
        ];
    }

    private function limit(string $key, int $default): int
    {
        return (int) config("modules.blog.validation.{$key}", $default);
    }
}
