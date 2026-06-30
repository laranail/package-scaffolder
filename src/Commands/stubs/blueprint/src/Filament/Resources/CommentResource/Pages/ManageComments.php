<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament\Resources\CommentResource\Pages;

use Filament\Resources\Pages\ManageRecords;
use Some\NamespacePath\Blog\Filament\Resources\CommentResource;

class ManageComments extends ManageRecords
{
    protected static string $resource = CommentResource::class;
}
