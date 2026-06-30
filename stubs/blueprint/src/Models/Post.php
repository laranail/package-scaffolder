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
     * Generic lifecycle events fire for every writer (facade, [[plugins]]Filament, Nova, [[/plugins]]raw
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
            function (Builder $q, string $term): void {
                $like = '%'.self::escapeLike($term).'%';

                // Explicit ESCAPE clause so the escaping is honoured on every driver
                // (SQLite has no default LIKE escape char; MySQL/Postgres use `\`).
                $q->where(fn (Builder $w) => $w
                    ->whereRaw('title LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('excerpt LIKE ? ESCAPE ?', [$like, '\\']));
            },
        );

        $sortable = (array) config('modules.blog.ui.sortable', ['published_at']);
        $sort = in_array($filters['sort'] ?? null, $sortable, true)
            ? $filters['sort']
            : ($sortable[0] ?? 'published_at');
        $direction = Str::lower((string) ($filters['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }

    /**
     * Escape LIKE wildcards (`%`, `_`, `\`) so a search term is matched literally
     * and can't widen the result set. Relies on the connection's default LIKE
     * escape character (`\`), which MySQL and Postgres use.
     */
    public static function escapeLike(string $value): string
    {
        return Str::replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
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
                $perMinute = max(1, (int) config('modules.blog.reading.words_per_minute', 200));

                return max(1, (int) ceil($this->countWords(strip_tags((string) $this->body)) / $perMinute));
            },
        )->shouldCache();
    }

    /**
     * Unicode-aware word count. `str_word_count` only sees ASCII a–z, so it
     * undercounts accented/Cyrillic/etc. text and ignores CJK entirely. Here,
     * scripts with no word spaces (CJK/Japanese/Korean) count per character, and
     * everything else counts whitespace-delimited tokens.
     */
    private function countWords(string $text): int
    {
        $text = trim($text);

        if ($text === '') {
            return 0;
        }

        $cjk = '/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}\x{3040}-\x{30ff}\x{ac00}-\x{d7af}]/u';
        $cjkCount = (int) preg_match_all($cjk, $text);

        $rest = trim((string) preg_replace($cjk, ' ', $text));
        $wordCount = $rest === '' ? 0 : count(preg_split('/\s+/u', $rest) ?: []);

        return $cjkCount + $wordCount;
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
