<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Filament\Resources;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Filament\Resources\PostResource\Pages\ManagePosts;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Filament resource for {@see Post}. v4 form() takes a Schema (v3 took a Form).
 */
class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Blog';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('excerpt')->maxLength(500)->columnSpanFull(),
            Textarea::make('body')->required()->rows(12)->columnSpanFull(),
            Select::make('status')
                ->options(collect(PostStatus::cases())->mapWithKeys(fn (PostStatus $s) => [$s->value => $s->label()]))
                ->required(),
            Select::make('category_id')->relationship('category', 'name')->searchable(),
            Select::make('tags')->relationship('tags', 'name')->multiple()->preload(),
            TextInput::make('featured_image')->url()->maxLength(2048),
            Toggle::make('is_featured'),
            DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge(),
                TextColumn::make('category.name')->toggleable(),
                IconColumn::make('is_featured')->boolean(),
                TextColumn::make('views')->sortable()->toggleable(),
                TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->defaultSort('published_at', 'desc');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index' => ManagePosts::route('/'),
        ];
    }
}
