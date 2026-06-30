<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Some\NamespacePath\Blog\Models\Category;

/**
 * Single source of the category validation rules, shared by
 * Store/UpdateCategoryRequest (create vs update = `required`/`sometimes` + ignore id).
 */
trait ProvidesCategoryRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function categoryRules(bool $creating, ?int $ignoreId = null): array
    {
        $present = $creating ? ['required'] : ['sometimes', 'required'];

        return [
            'name' => [...$present, 'string', 'max:'.(int) config('modules.blog.validation.title_max', 255)],
            'slug' => [
                'nullable', 'string', 'alpha_dash', 'max:'.(int) config('modules.blog.validation.slug_max', 255),
                Rule::unique(Category::class, 'slug')->ignore($ignoreId),
            ],
            'description' => ['nullable', 'string', 'max:'.(int) config('modules.blog.validation.category_description_max', 1000)],
        ];
    }
}
