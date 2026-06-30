<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Facades;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;
use Some\NamespacePath\Blog\Blog as BlogManager;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;

/**
 * @method static LengthAwarePaginator feed(?int $perPage = null)
 * @method static LengthAwarePaginator search(array $filters, ?int $perPage = null, bool $onlyPublished = true)
 * @method static Post find(int $id)
 * @method static Post|null findByKey(string $key, bool $withTrashed = false)
 * @method static Post|null findBySlug(string $slug)
 * @method static Post create(PostData $data)
 * @method static Post update(Post $post, PostData $data)
 * @method static bool delete(Post $post)
 * @method static bool restore(Post $post)
 * @method static bool forceDelete(Post $post)
 * @method static Post publish(Post $post)
 * @method static Post unpublish(Post $post)
 * @method static Comment createComment(Post $post, array $data)
 * @method static Comment approveComment(Comment $comment)
 * @method static bool deleteComment(Comment $comment)
 * @method static LengthAwarePaginator comments(Post $post, ?int $perPage = null)
 * @method static Collection categories()
 * @method static Category createCategory(array $data)
 * @method static Category updateCategory(Category $category, array $data)
 * @method static bool deleteCategory(Category $category)
 * @method static \Illuminate\Support\Collection tags()
 * @method static bool deleteTag(\Some\NamespacePath\Blog\Models\Tag $tag)
 * @method static \Illuminate\Support\Collection related(Post $post, ?int $limit = null)
 * @method static \Illuminate\Support\Collection recentPosts(?int $limit = null)
 * @method static \Illuminate\Support\Collection popularPosts(?int $limit = null)
 * @method static \Illuminate\Support\Collection featured(?int $limit = null)
 * @method static \Illuminate\Support\Collection archive()
 * @method static Post recordView(Post $post)
 * @method static array<string, int> stats()
 * @method static BlogManager pipe(string|\Closure $stage)
 * @method static BlogManager extendSearch(string $driver, \Closure $factory)
 * @method static BlogManager searchUsing(string $driver)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 *
 * @see BlogManager
 *
 * @mixin BlogManager
 */
class Blog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BlogManager::class;
    }
}
