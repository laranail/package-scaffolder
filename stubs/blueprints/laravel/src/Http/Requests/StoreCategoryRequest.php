<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Some\NamespacePath\Blog\Http\Requests\Concerns\ProvidesCategoryRules;
use Some\NamespacePath\Blog\Models\Category;

class StoreCategoryRequest extends FormRequest
{
    use ProvidesCategoryRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->categoryRules(creating: true);
    }
}
