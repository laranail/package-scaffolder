<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Some\NamespacePath\Blog\Http\Requests\Concerns\ProvidesCategoryRules;

class UpdateCategoryRequest extends FormRequest
{
    use ProvidesCategoryRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('category')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->categoryRules(creating: false, ignoreId: $this->route('category')?->id);
    }
}
