<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Enums;

use Illuminate\Support\Arr;

/**
 * The lifecycle states a blog post can be in.
 */
enum PostStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * A human friendly label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /**
     * Whether a post in this status is publicly visible.
     */
    public function isVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * Key/value pairs suitable for a <select> or validation `in:` rule.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * All backing values, handy for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return Arr::pluck(self::cases(), 'value');
    }
}
