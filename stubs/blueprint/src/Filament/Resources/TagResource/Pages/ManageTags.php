<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament\Resources\TagResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Some\NamespacePath\Blog\Filament\Resources\TagResource;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

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
