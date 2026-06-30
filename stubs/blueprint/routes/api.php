<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Some\NamespacePath\Blog\Http\Controllers\Api\CategoryController;
use Some\NamespacePath\Blog\Http\Controllers\Api\CommentController;
use Some\NamespacePath\Blog\Http\Controllers\Api\PostController;
use Some\NamespacePath\Blog\Http\Controllers\Api\TagController;

/*
|--------------------------------------------------------------------------
| Blog REST API
|--------------------------------------------------------------------------
|
| Everything is config-driven (config/blog.php → routes.api): toggle, prefix,
| name prefix and middleware. Reads are public; writes require the configured
| auth middleware (Sanctum by default, swappable). Drafts are hidden by the
| blog.published middleware and comment submission is rate limited.
|
*/

$config = (array) config('modules.blog.routes.api', []);

if (! ($config['enabled'] ?? true)) {
    return;
}

Route::middleware($config['middleware'] ?? ['api'])
    ->prefix($config['prefix'] ?? 'api/v1')
    ->name($config['name'] ?? 'api.blog.')
    ->group(function () use ($config): void {
        $auth = $config['auth_middleware'] ?? ['auth:sanctum'];

        Route::get('ping', fn () => response()->json([
            'ok' => true,
            'package' => 'blog',
        ]))->name('ping');

        // ---- Public reads -------------------------------------------------
        Route::get('posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('posts/{post}', [PostController::class, 'show'])
            ->middleware('blog.published')
            ->name('posts.show');

        Route::get('posts/{post}/comments', [CommentController::class, 'index'])->name('posts.comments.index');
        Route::post('posts/{post}/comments', [CommentController::class, 'store'])
            ->middleware('throttle:blog-comments')
            ->name('posts.comments.store');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

        Route::get('tags', [TagController::class, 'index'])->name('tags.index');
        Route::get('tags/{tag}', [TagController::class, 'show'])->name('tags.show');

        // ---- Authenticated writes (+ optional token ability gate) --------
        Route::middleware([...$auth, 'blog.ability:write'])->group(function (): void {
            Route::post('posts', [PostController::class, 'store'])->name('posts.store');
            Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update'])->name('posts.update');
            Route::delete('posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
            Route::post('posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');
            Route::post('posts/{post}/unpublish', [PostController::class, 'unpublish'])->name('posts.unpublish');
            Route::post('posts/{post}/restore', [PostController::class, 'restore'])
                ->withTrashed()
                ->name('posts.restore');
            Route::delete('posts/{post}/force', [PostController::class, 'forceDestroy'])
                ->withTrashed()
                ->name('posts.force');

            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::match(['put', 'patch'], 'categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::patch('comments/{comment}/approve', [CommentController::class, 'approve'])
                ->middleware('blog.ability:moderate')
                ->name('comments.approve');
            Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        });
    });
