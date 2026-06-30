<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Some\NamespacePath\Blog\Filament\Resources\CategoryResource;
use Some\NamespacePath\Blog\Filament\Resources\CommentResource;
use Some\NamespacePath\Blog\Filament\Resources\PostResource;
use Some\NamespacePath\Blog\Filament\Resources\TagResource;

/**
 * Filament panel plugin for the blog. Add it to a panel:
 *
 *     $panel->plugin(\Some\NamespacePath\Blog\Filament\BlogPlugin::make());
 *     // or, omitting comment moderation:
 *     $panel->plugin(BlogPlugin::make()->comments(false));
 *
 * The resources persist via Eloquent; body sanitization and lifecycle events run
 * at the model layer, so admin writes stay consistent with the rest of the package.
 *
 * Adapter skeleton — verify field/component names against your installed Filament
 * version (targets v4; the form() signature differs in v3, and v5 has moved on).
 */
class BlogPlugin implements Plugin
{
    protected bool $comments = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public function comments(bool $condition = true): static
    {
        $this->comments = $condition;

        return $this;
    }

    public function getId(): string
    {
        return 'blog';
    }

    public function register(Panel $panel): void
    {
        $panel->resources(array_values(array_filter([
            PostResource::class,
            CategoryResource::class,
            TagResource::class,
            $this->comments ? CommentResource::class : null,
        ])));
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
