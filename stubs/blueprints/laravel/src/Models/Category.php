<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Some\NamespacePath\Blog\Database\Factories\CategoryFactory;
use Some\NamespacePath\Blog\Events\CategoryDeleted;
use Some\NamespacePath\Blog\Events\CategorySaved;
use Some\NamespacePath\Blog\Traits\HasSlug;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasSlug;

    protected $table = 'blog_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /** @var array<string, class-string> */
    protected $dispatchesEvents = [
        'saved' => CategorySaved::class,
        'deleted' => CategoryDeleted::class,
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function slugSource(): string
    {
        return 'name';
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
