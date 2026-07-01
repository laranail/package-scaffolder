<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Some\NamespacePath\Blog\Database\Factories\CommentFactory;
use Some\NamespacePath\Blog\Events\CommentCreated;
use Some\NamespacePath\Blog\Events\CommentDeleted;
use Some\NamespacePath\Blog\Observers\CommentObserver;

/**
 * @property int $id
 * @property int $commentable_id
 * @property string $commentable_type
 * @property int|null $author_id
 * @property string $author_name
 * @property string|null $email
 * @property string $body
 * @property bool $approved
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    protected $table = 'blog_comments';

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'author_id',
        'author_name',
        'email',
        'body',
        'approved',
    ];

    /**
     * Generic lifecycle events (the "approved" transition fires from
     * {@see CommentObserver}).
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => CommentCreated::class,
        'deleted' => CommentDeleted::class,
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'boolean',
        ];
    }

    /**
     * The model this comment is attached to (a Post, or any host model).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
