<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Some\NamespacePath\Blog\Database\Factories\TagFactory;
use Some\NamespacePath\Blog\Events\TagDeleted;
use Some\NamespacePath\Blog\Events\TagSaved;
use Some\NamespacePath\Blog\Traits\HasSlug;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    use HasSlug;

    protected $table = 'blog_tags';

    protected $fillable = [
        'name',
        'slug',
    ];

    /** @var array<string, class-string> */
    protected $dispatchesEvents = [
        'saved' => TagSaved::class,
        'deleted' => TagDeleted::class,
    ];

    /**
     * Posts carrying this tag (the inverse side of the polymorphic taggable pivot).
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable', 'blog_taggables');
    }

    public function slugSource(): string
    {
        return 'name';
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
