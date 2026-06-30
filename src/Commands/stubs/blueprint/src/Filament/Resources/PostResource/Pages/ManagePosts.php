<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament\Resources\PostResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Some\NamespacePath\Blog\Filament\Resources\PostResource;

/**
 * Simple (modal) resource page — create/edit happen in modals on the list.
 * Persistence is via Eloquent, so the model-layer body sanitization and
 * lifecycle events apply to admin writes too.
 */
class ManagePosts extends ManageRecords
{
    protected static string $resource = PostResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
