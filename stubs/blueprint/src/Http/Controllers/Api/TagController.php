<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Http\Resources\TagResource;
use Some\NamespacePath\Blog\Models\Tag;

class TagController extends Controller
{
    public function __construct(
        private readonly Blog $blog,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection($this->blog->tags());
    }

    public function show(Tag $tag): TagResource
    {
        return TagResource::make($tag->loadCount('posts'));
    }
}
