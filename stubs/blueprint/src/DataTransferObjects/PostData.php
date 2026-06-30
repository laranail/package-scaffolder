<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\DataTransferObjects;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Some\NamespacePath\Blog\Enums\PostStatus;

/**
 * An immutable, framework-agnostic representation of post attributes. Every
 * field is optional so the same object models both a full create and a partial
 * update — {@see toAttributes()} drops nulls, so only the values actually
 * supplied are written. `tags` is a relation (synced separately), not a column.
 */
final readonly class PostData
{
    /**
     * @param  array<int, string>|null  $tags
     */
    public function __construct(
        public ?string $title = null,
        public ?string $body = null,
        public ?string $excerpt = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $featuredImage = null,
        public ?bool $isFeatured = null,
        public ?PostStatus $status = null,
        public ?int $categoryId = null,
        public ?int $authorId = null,
        public ?Carbon $publishedAt = null,
        public ?string $slug = null,
        public ?array $tags = null,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $status = $attributes['status'] ?? null;

        return new self(
            title: isset($attributes['title']) ? (string) $attributes['title'] : null,
            body: isset($attributes['body']) ? (string) $attributes['body'] : null,
            excerpt: $attributes['excerpt'] ?? null,
            metaTitle: $attributes['meta_title'] ?? null,
            metaDescription: $attributes['meta_description'] ?? null,
            featuredImage: $attributes['featured_image'] ?? null,
            isFeatured: array_key_exists('is_featured', $attributes) ? (bool) $attributes['is_featured'] : null,
            status: self::toStatus($status),
            categoryId: isset($attributes['category_id']) ? (int) $attributes['category_id'] : null,
            authorId: isset($attributes['author_id']) ? (int) $attributes['author_id'] : null,
            publishedAt: self::toCarbon($attributes['published_at'] ?? null),
            slug: $attributes['slug'] ?? null,
            tags: isset($attributes['tags']) ? array_values((array) $attributes['tags']) : null,
        );
    }

    /**
     * Build from a request. The author is never read from the payload (it is
     * server-controlled); attach it explicitly with {@see withAuthor()} on create.
     */
    public static function fromRequest(Request $request): self
    {
        $attributes = $request->all();
        unset($attributes['author_id']);

        return self::fromArray($attributes);
    }

    public function withAuthor(int|string|null $authorId): self
    {
        return $this->with(['authorId' => $authorId !== null ? (int) $authorId : null]);
    }

    public function withBody(?string $body): self
    {
        return $this->with(['body' => $body]);
    }

    /**
     * Cast to a database-ready attribute array, dropping nulls so model and
     * column defaults take over and partial updates touch only what changed.
     * `tags` is intentionally excluded (it is a relation).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return Arr::whereNotNull([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'featured_image' => $this->featuredImage,
            'is_featured' => $this->isFeatured,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'author_id' => $this->authorId,
            'published_at' => $this->publishedAt,
        ]);
    }

    /**
     * Immutable clone with overrides.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function with(array $overrides): self
    {
        return new self(
            title: $overrides['title'] ?? $this->title,
            body: array_key_exists('body', $overrides) ? $overrides['body'] : $this->body,
            excerpt: $overrides['excerpt'] ?? $this->excerpt,
            metaTitle: $overrides['metaTitle'] ?? $this->metaTitle,
            metaDescription: $overrides['metaDescription'] ?? $this->metaDescription,
            featuredImage: $overrides['featuredImage'] ?? $this->featuredImage,
            isFeatured: array_key_exists('isFeatured', $overrides) ? $overrides['isFeatured'] : $this->isFeatured,
            status: $overrides['status'] ?? $this->status,
            categoryId: $overrides['categoryId'] ?? $this->categoryId,
            authorId: array_key_exists('authorId', $overrides) ? $overrides['authorId'] : $this->authorId,
            publishedAt: $overrides['publishedAt'] ?? $this->publishedAt,
            slug: $overrides['slug'] ?? $this->slug,
            tags: $overrides['tags'] ?? $this->tags,
        );
    }

    /**
     * Resolve a status value to the enum, raising a clear exception (not a raw
     * ValueError) on invalid direct input. The validated request path never hits
     * the failure branch.
     */
    private static function toStatus(mixed $status): ?PostStatus
    {
        if ($status === null || $status instanceof PostStatus) {
            return $status;
        }

        return PostStatus::tryFrom((string) $status)
            ?? throw new InvalidArgumentException("Invalid post status [{$status}].");
    }

    /**
     * @param  mixed  $value
     */
    private static function toCarbon($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            $shown = is_scalar($value) ? (string) $value : get_debug_type($value);

            throw new InvalidArgumentException("Invalid published_at date [{$shown}].", previous: $e);
        }
    }
}
