<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Some\NamespacePath\Blog\Models\Post;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'body_html' => $this->rendered_body,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'featured_image' => $this->featured_image,
            'is_featured' => $this->is_featured,
            'views' => $this->views,
            'reading_time' => $this->reading_time,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->whenCounted('comments'),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
