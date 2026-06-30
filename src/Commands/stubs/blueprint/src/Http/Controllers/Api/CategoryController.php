<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Http\Requests\StoreCategoryRequest;
use Some\NamespacePath\Blog\Http\Requests\UpdateCategoryRequest;
use Some\NamespacePath\Blog\Http\Resources\CategoryResource;
use Some\NamespacePath\Blog\Models\Category;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly Blog $blog,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->blog->categories());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->blog->createCategory($request->validated());

        return CategoryResource::make($category)
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category->loadCount('posts'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        return CategoryResource::make($this->blog->updateCategory($category, $request->validated()));
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->blog->deleteCategory($category);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
