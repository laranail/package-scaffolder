<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog;

use BadMethodCallException;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Traits\Macroable;
use Some\NamespacePath\Blog\Actions\CreateCommentAction;
use Some\NamespacePath\Blog\Actions\CreatePostAction;
use Some\NamespacePath\Blog\Actions\DeletePostAction;
use Some\NamespacePath\Blog\Actions\PublishPostAction;
use Some\NamespacePath\Blog\Actions\UnpublishPostAction;
use Some\NamespacePath\Blog\Actions\UpdatePostAction;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Processing\BodyProcessor;
use Some\NamespacePath\Blog\Search\SearchManager;
use Some\NamespacePath\Blog\Services\CategoryService;
use Some\NamespacePath\Blog\Services\CommentService;
use Some\NamespacePath\Blog\Services\PostService;
use Some\NamespacePath\Blog\Services\TagService;

/**
 * The package's single public entry point — the "secretary". Controllers, the
 * CLI and Livewire talk only to this manager (or the {@see Facades\Blog} facade);
 * it routes writes through Actions and reads straight to Services, and never
 * contains business logic of its own.
 */
class Blog
{
    /**
     * Lets consumers add methods to the public API at runtime without
     * subclassing — `Blog::macro('trending', fn () => $this->popularPosts())`.
     * `$this` inside a macro binds to this manager, so macros reuse its services.
     */
    use Macroable {
        __call as macroCall;
    }

    /**
     * Default component tag prefix (slug of the vendor/package — Blade/Livewire
     * tags can't contain `/`). Overridable via config('modules.blog.components.prefix').
     */
    public const string COMPONENT_PREFIX = 'modules-blog';

    /**
     * Canonical polymorphic-type aliases for the package's OWN models. The service
     * provider always registers these (regardless of config), so the package's
     * commentable/taggable records store a stable alias — never the find-replaceable
     * placeholder FQCN — even if a host clears config('modules.blog.morph_map'). That
     * config key is purely for the host's own commentable/taggable models.
     *
     * @return array<string, class-string<Model>>
     */
    public static function morphMap(): array
    {
        return [
            'blog_post' => Post::class,
            'blog_comment' => Comment::class,
            'blog_category' => Category::class,
            'blog_tag' => Tag::class,
        ];
    }

    public function __construct(
        private readonly PostService $postService,
        private readonly CommentService $commentService,
        private readonly CategoryService $categoryService,
        private readonly TagService $tagService,
        private readonly CreatePostAction $createPostAction,
        private readonly UpdatePostAction $updatePostAction,
        private readonly DeletePostAction $deletePostAction,
        private readonly PublishPostAction $publishPostAction,
        private readonly UnpublishPostAction $unpublishPostAction,
        private readonly CreateCommentAction $createCommentAction,
    ) {}

    public function feed(?int $perPage = null): LengthAwarePaginator
    {
        return $this->postService->paginatePublished($this->perPage($perPage));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, ?int $perPage = null, bool $onlyPublished = true): LengthAwarePaginator
    {
        return $this->postService->search($filters, $this->perPage($perPage), $onlyPublished);
    }

    public function find(int $id): Post
    {
        return $this->postService->find($id);
    }

    public function findByKey(string $key, bool $withTrashed = false): ?Post
    {
        return $this->postService->findByKey($key, $withTrashed);
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->postService->findBySlug($slug);
    }

    public function create(PostData $data): Post
    {
        return $this->createPostAction->handle($data);
    }

    public function update(Post $post, PostData $data): Post
    {
        return $this->updatePostAction->handle($post, $data);
    }

    public function delete(Post $post): bool
    {
        return $this->deletePostAction->handle($post);
    }

    public function restore(Post $post): bool
    {
        return $this->postService->restore($post);
    }

    public function forceDelete(Post $post): bool
    {
        return $this->postService->forceDelete($post);
    }

    public function publish(Post $post): Post
    {
        return $this->publishPostAction->handle($post);
    }

    public function unpublish(Post $post): Post
    {
        return $this->unpublishPostAction->handle($post);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createComment(Post $post, array $data): Comment
    {
        return $this->createCommentAction->handle($post, $data);
    }

    public function approveComment(Comment $comment): Comment
    {
        return $this->commentService->approve($comment);
    }

    public function deleteComment(Comment $comment): bool
    {
        return $this->commentService->delete($comment);
    }

    public function comments(Post $post, ?int $perPage = null): LengthAwarePaginator
    {
        return $this->commentService->paginateApproved($post, $this->perPage($perPage));
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->categoryService->listWithCounts();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): Category
    {
        return $this->categoryService->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(Category $category, array $data): Category
    {
        return $this->categoryService->update($category, $data);
    }

    public function deleteCategory(Category $category): bool
    {
        return $this->categoryService->delete($category);
    }

    /**
     * @return SupportCollection<int, Tag>
     */
    public function tags(): SupportCollection
    {
        return $this->tagService->listWithCounts();
    }

    public function deleteTag(Tag $tag): bool
    {
        return $this->tagService->delete($tag);
    }

    /**
     * @return SupportCollection<int, Post>
     */
    public function related(Post $post, ?int $limit = null): SupportCollection
    {
        return $this->postService->related($post, $limit ?? (int) config('modules.blog.features.related_limit', 5));
    }

    /**
     * @return SupportCollection<int, Post>
     */
    public function recentPosts(?int $limit = null): SupportCollection
    {
        return $this->postService->recent($limit ?? (int) config('modules.blog.features.widget_limit', 5));
    }

    /**
     * @return SupportCollection<int, Post>
     */
    public function popularPosts(?int $limit = null): SupportCollection
    {
        return $this->postService->popular($limit ?? (int) config('modules.blog.features.widget_limit', 5));
    }

    /**
     * @return SupportCollection<int, Post>
     */
    public function featured(?int $limit = null): SupportCollection
    {
        return $this->postService->featured($limit ?? (int) config('modules.blog.features.widget_limit', 5));
    }

    /**
     * Record a page view for a post (atomic increment). Opt-in: call it from
     * your show route, or enable config('modules.blog.features.count_views').
     */
    public function recordView(Post $post): Post
    {
        return $this->postService->recordView($post);
    }

    /**
     * @return SupportCollection<int, object>
     */
    public function archive(): SupportCollection
    {
        return $this->postService->archive();
    }

    /**
     * Cross-entity dashboard counts — the only place that aggregates services.
     *
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            ...$this->postService->counts(),
            'comments' => $this->commentService->total(),
            'pending_comments' => $this->commentService->pendingCount(),
        ];
    }

    /**
     * Append a save-time body-processing stage (class-string or closure).
     * Consumers chain this from their own provider's boot(): no package edit.
     */
    public function pipe(string|Closure $stage): static
    {
        app(BodyProcessor::class)->pipe($stage);

        return $this;
    }

    /**
     * Register a custom search driver (Manager `extend` seam).
     */
    public function extendSearch(string $driver, Closure $factory): static
    {
        app(SearchManager::class)->extend($driver, $factory);

        return $this;
    }

    /**
     * Set the active search driver for the current request.
     */
    public function searchUsing(string $driver): static
    {
        config(['modules.blog.search.driver' => $driver]);

        return $this;
    }

    /**
     * Macro dispatch: unknown methods resolve to a registered macro, else throw.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
    }

    private function perPage(?int $perPage): int
    {
        $perPage ??= (int) config('modules.blog.pagination.per_page', 15);
        $max = (int) config('modules.blog.pagination.max_per_page', 100);

        return max(1, min($perPage, $max));
    }
}
