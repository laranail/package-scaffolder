<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Some\NamespacePath\Blog\Enums\PostStatus;

/**
 * Validates the public feed's query string. The sort column is constrained to
 * an allow-list so the order-by can never be influenced by arbitrary input.
 */
class IndexPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $filterMax = (int) config('modules.blog.validation.filter_max', 255);

        return [
            'status' => ['sometimes', Rule::enum(PostStatus::class)],
            'category' => ['sometimes', 'string', 'max:'.$filterMax],
            'tag' => ['sometimes', 'string', 'max:'.$filterMax],
            'featured' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:'.$filterMax],
            'sort' => ['sometimes', 'string', Rule::in((array) config('modules.blog.ui.sortable', []))],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('modules.blog.pagination.max_per_page', 100)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->only(['status', 'category', 'tag', 'featured', 'search', 'sort', 'direction']);
    }
}
