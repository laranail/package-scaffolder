<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Some\NamespacePath\Blog\Http\Controllers\BlogController;
use Some\NamespacePath\Blog\Http\Controllers\CommentController;
use Some\NamespacePath\Blog\Http\Controllers\FeedController;

/*
|--------------------------------------------------------------------------
| Blog web routes
|--------------------------------------------------------------------------
|
| Config-driven (config/blog.php → routes.web). Reads (index/show) and guest
| comment submission are public; author writes require the configured
| auth_middleware. Posts resolve by slug; "show" is guarded by blog.published.
|
*/

$config = (array) config('modules.blog.routes.web', []);

if (! ($config['enabled'] ?? true)) {
    return;
}

$auth = $config['auth_middleware'] ?? ['auth'];

Route::middleware($config['middleware'] ?? ['web'])
    ->prefix($config['prefix'] ?? 'blog')
    ->name('blog.')
    ->group(function () use ($auth): void {
        // ---- Authenticated author writes (registered first so /create wins) ---
        Route::middleware($auth)->group(function (): void {
            Route::get('/create', [BlogController::class, 'create'])->name('create');
            Route::post('/', [BlogController::class, 'store'])->name('store');
            Route::get('/{post}/edit', [BlogController::class, 'edit'])->name('edit');
            Route::match(['put', 'patch'], '/{post}', [BlogController::class, 'update'])->name('update');
            Route::delete('/{post}', [BlogController::class, 'destroy'])->name('destroy');
        });

        // ---- Public reads + guest comment submission -------------------------
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/feed', [FeedController::class, 'feed'])->name('feed');
        Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');
        Route::post('/{post}/comments', [CommentController::class, 'store'])
            ->middleware('throttle:blog-comments')
            ->name('comments.store');
        Route::get('/{post}', [BlogController::class, 'show'])
            ->middleware('blog.published')
            ->name('show');
    });
