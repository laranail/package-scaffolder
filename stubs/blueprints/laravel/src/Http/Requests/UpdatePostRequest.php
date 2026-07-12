<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Some\NamespacePath\Blog\Http\Requests\Concerns\ProvidesPostRules;

class UpdatePostRequest extends FormRequest
{
    use ProvidesPostRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->postRules(creating: false, ignoreId: $this->route('post')?->id);
    }
}
