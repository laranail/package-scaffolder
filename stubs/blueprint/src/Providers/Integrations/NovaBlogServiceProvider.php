<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Providers\Integrations;

use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;
use Some\NamespacePath\Blog\Nova\Resources\Category;
use Some\NamespacePath\Blog\Nova\Resources\Comment;
use Some\NamespacePath\Blog\Nova\Resources\Post;
use Some\NamespacePath\Blog\Nova\Resources\Tag;
use Some\NamespacePath\Blog\Nova\Tools\BlogTool;

/**
 * Optional Laravel Nova integration. Self-disabling: a no-op unless Nova is
 * installed, so the package stays headless. Registers the package's Nova
 * resources; a host may additionally add {@see BlogTool}
 * in their NovaServiceProvider::tools().
 */
class NovaBlogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // String guard so the autoload path never hard-references an absent class.
        if (! class_exists('Laravel\\Nova\\Nova')) {
            return;
        }

        Nova::serving(static function (): void {
            Nova::resources([
                Post::class,
                Category::class,
                Tag::class,
                Comment::class,
            ]);
        });
    }
}
