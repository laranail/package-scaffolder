<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;

/**
 * Public RSS/Atom feed and XML sitemap. Each is toggleable via config('modules.blog.features').
 */
class FeedController extends Controller
{
    public function __construct(
        private readonly Blog $blog,
    ) {}

    public function feed(): Response
    {
        abort_unless((bool) config('modules.blog.features.rss', true), 404);

        return response()
            ->view('modules/blog::feed.rss', ['posts' => $this->blog->recentPosts((int) config('modules.blog.features.feed_limit', 20))])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        abort_unless((bool) config('modules.blog.features.sitemap', true), 404);

        return response()
            ->view('modules/blog::feed.sitemap', ['posts' => $this->blog->recentPosts(1000)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
