<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Nova\Resources;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\MorphToMany;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Some\NamespacePath\Blog\Enums\PostStatus;

/**
 * Nova resource for the blog Post model. Writes persist via Eloquent, so the
 * model-layer body sanitization + lifecycle events apply to Nova too.
 *
 * Adapter skeleton — verify field signatures against your installed Nova version
 * (targets v5: typed fields(NovaRequest)).
 */
class Post extends Resource
{
    /** @var class-string<Model> */
    public static $model = \Some\NamespacePath\Blog\Models\Post::class;

    public static $title = 'title';

    public static $group = 'Blog';

    /** @var array<int, string> */
    public static $search = ['id', 'title', 'excerpt'];

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Title')->rules('required', 'max:255'),
            Textarea::make('Excerpt')->hideFromIndex(),
            Textarea::make('Body')->rules('required')->hideFromIndex(),
            Select::make('Status')
                ->options(collect(PostStatus::cases())->mapWithKeys(fn (PostStatus $s) => [$s->value => $s->label()])->all())
                ->displayUsingLabels(),
            Boolean::make('Featured', 'is_featured'),
            Number::make('Views')->exceptOnForms(),
            BelongsTo::make('Category', 'category', Category::class)->nullable(),
            MorphToMany::make('Tags', 'tags', Tag::class),
            DateTime::make('Published At', 'published_at')->nullable(),
        ];
    }
}
