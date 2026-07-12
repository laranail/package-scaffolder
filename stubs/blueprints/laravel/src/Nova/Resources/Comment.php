<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Nova\Resources;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphTo;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

/**
 * Comment moderation. Toggling "approved" persists via Eloquent, so the
 * CommentApproved event fires (model layer).
 */
class Comment extends Resource
{
    /** @var class-string<Model> */
    public static $model = \Some\NamespacePath\Blog\Models\Comment::class;

    public static $title = 'author_name';

    public static $group = 'Blog';

    /** @var array<int, string> */
    public static $search = ['id', 'author_name', 'body'];

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            MorphTo::make('Commentable', 'commentable')->types([Post::class]),
            Text::make('Author', 'author_name')->rules('required', 'max:255'),
            Textarea::make('Body')->rules('required')->hideFromIndex(),
            Boolean::make('Approved'),
        ];
    }
}
