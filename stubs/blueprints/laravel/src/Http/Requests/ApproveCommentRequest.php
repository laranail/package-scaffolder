<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('blog.moderate-comments') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
