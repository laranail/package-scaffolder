<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Some\NamespacePath\Blog\Models\Comment;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commentable_id' => $this->commentable_id,
            'commentable_type' => $this->commentable_type,
            'author_name' => $this->author_name,
            'body' => $this->body,
            'approved' => $this->approved,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
