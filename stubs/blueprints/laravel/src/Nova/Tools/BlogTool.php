<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Nova\Tools;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

/**
 * Optional Nova sidebar entry grouping the blog resources. Register it in your
 * NovaServiceProvider::tools():
 *
 *     protected function tools(): array
 *     {
 *         return [ new \Some\NamespacePath\Blog\Nova\Tools\BlogTool() ];
 *     }
 */
class BlogTool extends Tool
{
    public function boot(): void
    {
        // Register front-end assets here if you add a custom dashboard view.
    }

    public function menu(Request $request): mixed
    {
        return MenuSection::make('Blog')
            ->path('/resources/blog-posts')
            ->icon('book-open');
    }
}
