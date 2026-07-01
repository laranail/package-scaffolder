<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Some\NamespacePath\Blog\Filament\Resources\CommentResource\Pages\ManageComments;
use Some\NamespacePath\Blog\Models\Comment;

/**
 * Comment moderation. Toggling "approved" persists via Eloquent, so the
 * CommentApproved event (model-layer) fires for the host to react to.
 */
class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $recordTitleAttribute = 'author_name';

    protected static ?string $navigationGroup = 'Blog';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('author_name')->required()->maxLength(255),
            Textarea::make('body')->required()->columnSpanFull(),
            Toggle::make('approved'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')->searchable(),
                TextColumn::make('commentable.title')->label('On')->limit(30)->toggleable(),
                TextColumn::make('body')->limit(50),
                IconColumn::make('approved')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('approved'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ManageComments::route('/'),
        ];
    }
}
