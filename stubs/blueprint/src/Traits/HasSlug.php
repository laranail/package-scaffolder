<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Automatically derives a unique slug from a source attribute when a model is
 * created or its source attribute changes and no slug was supplied.
 *
 * A consuming model may override {@see slugSource()} and {@see slugColumn()}.
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var static $model */
            $source = $model->slugSource();
            $column = $model->slugColumn();

            $needsSlug = empty($model->{$column})
                || ($model->isDirty($source) && ! $model->isDirty($column));

            if ($needsSlug && ! empty($model->{$source})) {
                $model->{$column} = $model->generateUniqueSlug((string) $model->{$source});
            }
        });
    }

    /**
     * Build a slug that is unique across the table, ignoring the current row.
     */
    public function generateUniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        return static::query()
            ->where($this->slugColumn(), $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    /**
     * The attribute the slug is generated from.
     */
    public function slugSource(): string
    {
        return 'title';
    }

    /**
     * The column the slug is stored in.
     */
    public function slugColumn(): string
    {
        return 'slug';
    }

    public function getRouteKeyName(): string
    {
        return $this->slugColumn();
    }
}
