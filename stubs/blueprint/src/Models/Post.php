<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Models;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;
use Some\NamespacePath\Blog\Database\Factories\PostFactory;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Events\PostCreated;
use Some\NamespacePath\Blog\Events\PostDeleted;
use Some\NamespacePath\Blog\Events\PostForceDeleted;
use Some\NamespacePath\Blog\Events\PostRestored;
use Some\NamespacePath\Blog\Events\PostUpdated;
use Some\NamespacePath\Blog\Observers\PostObserver;
use Some\NamespacePath\Blog\Traits\HasSlug;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $featured_image
 * @property bool $is_featured
 * @property int $views
 * @property PostStatus $status
 * @property int|null $category_id
 * @property int|null $author_id
 * @property Carbon|null $published_at
 * @property-read int $reading_time
 * @property-read string $rendered_body
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    use HasSlug;
    use SoftDeletes;

    protected $table = 'blog_posts';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'meta_title',
        'meta_description',
        'featured_image',
        'is_featured',
        'status',
        'category_id',
        'author_id',
        'published_at',
    ];

    /**
     * Generic lifecycle events fire for every writer (facade, admin panels, raw
     * Eloquent, factories). State-transition events (published/unpublished) live
     * in {@see PostObserver}.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => PostCreated::class,
        'updated' => PostUpdated::class,
        'deleted' => PostDeleted::class,
        'restored' => PostRestored::class,
        'forceDeleted' => PostForceDeleted::class,
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'views' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'blog_taggables');
    }

    /**
     * The author of the post. Points at the host application's user model so
     * the package stays decoupled from a specific auth implementation.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(config('modules.blog.user_model'), 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Posts that are scheduled and now due to go live.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Scheduled)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Apply a whitelisted set of filters/sorting. Every input is constrained:
     * status casts through the enum, search/tag/category are bound (never
     * interpolated), and the sort column is validated against the config
     * allow-list to prevent order-by injection.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->when(
            // tryFrom (not from) so an unexpected value from Livewire/CLI bails
            // instead of throwing a ValueError → 500.
            PostStatus::tryFrom((string) ($filters['status'] ?? '')),
            fn (Builder $q, PostStatus $status) => $q->where('status', $status),
        );

        $query->when(
            $filters['category'] ?? null,
            fn (Builder $q, string $category) => $q->whereHas(
                'category',
                fn (Builder $c) => $c->where($this->slugOrId($category)),
            ),
        );

        $query->when(
            $filters['tag'] ?? null,
            fn (Builder $q, string $tag) => $q->whereHas(
                'tags',
                fn (Builder $t) => $t->where($this->slugOrId($tag)),
            ),
        );

        $query->when(
            filter_var($filters['featured'] ?? null, FILTER_VALIDATE_BOOL),
            fn (Builder $q) => $q->where('is_featured', true),
        );

        $query->when(
            $filters['search'] ?? null,
            fn (Builder $q, string $term) => $q->where(
                fn (Builder $w) => $w
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%"),
            ),
        );

        $sortable = (array) config('modules.blog.ui.sortable', ['published_at']);
        $sort = in_array($filters['sort'] ?? null, $sortable, true)
            ? $filters['sort']
            : ($sortable[0] ?? 'published_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }

    /**
     * A grouped "slug OR numeric id" matcher for relationship filters. The OR is
     * wrapped (via where(Closure)) so it stays correlated inside whereHas, and the
     * id branch is only added for numeric input (Postgres rejects `id = 'a-slug'`).
     */
    private function slugOrId(string $value): Closure
    {
        return function (Builder $query) use ($value): void {
            $query->where('slug', $value);

            if (is_numeric($value)) {
                $query->orWhere('id', (int) $value);
            }
        };
    }

    /**
     * Whether the post is live right now. A method (not an Attribute) because it
     * depends on the current time, so caching would be a footgun.
     */
    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /**
     * Estimated reading time in minutes (≈words/200, minimum 1). Accessed as
     * `$post->reading_time`.
     */
    protected function readingTime(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                $words = Str::wordCount(strip_tags((string) $this->body));
                $perMinute = max(1, (int) config('modules.blog.reading.words_per_minute', 200));

                return max(1, (int) ceil($words / $perMinute));
            },
        )->shouldCache();
    }

    /**
     * The body as HTML for display (accessed as `$post->rendered_body`). When
     * Markdown is enabled (and CommonMark is installed) the stored body is treated
     * as Markdown and rendered to HTML — the stored value stays the author's source
     * (render-on-display, so editing round-trips). Otherwise the (already sanitized)
     * stored body is returned.
     */
    protected function renderedBody(): Attribute
    {
        return Attribute::make(get: function (): string {
            $body = (string) $this->body;

            if (config('modules.blog.processing.markdown', false) && class_exists(CommonMarkConverter::class)) {
                return (new CommonMarkConverter([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ]))->convert($body)->getContent();
            }

            return $body;
        });
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
