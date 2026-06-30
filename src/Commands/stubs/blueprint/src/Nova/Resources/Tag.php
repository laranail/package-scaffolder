<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Nova\Resources;

use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;

class Tag extends Resource
{
    /** @var class-string<Model> */
    public static $model = \Some\NamespacePath\Blog\Models\Tag::class;

    public static $title = 'name';

    public static $group = 'Blog';

    /** @var array<int, string> */
    public static $search = ['id', 'name'];

    /**
     * @return array<int, Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->rules('required', 'max:50'),
        ];
    }
}
