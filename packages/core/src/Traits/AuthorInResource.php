<?php

declare(strict_types=1);

namespace Moox\Core\Traits;

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;

trait AuthorInResource
{
    protected static ?string $authorModel = null;

    protected static function initAuthorModel(): void
    {
        if (static::$authorModel === null) {
            static::$authorModel = config('builder.author_model');
        }
    }

    protected static function getAuthorOptions(): array
    {
        static::initAuthorModel();

        return static::$authorModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowAuthorField(): bool
    {
        static::initAuthorModel();

        return static::$authorModel && class_exists(static::$authorModel);
    }

    public static function getAuthorFormField(): Select
    {
        return Select::make('author_id')
            ->label(__('core::core.author'))
            ->options(fn () => static::getAuthorOptions())
            ->default(fn () => auth()->id())
            ->required()
            ->searchable()
            ->visible(fn () => static::shouldShowAuthorField());
    }

    public static function getAuthorTableColumn(): ImageColumn
    {
        return ImageColumn::make('author.avatar_url')
            ->label(__('core::core.author'))
            ->tooltip(fn ($record) => $record->author?->name)
            ->alignment('center')
            ->circular()
            ->visible(fn () => static::shouldShowAuthorField())
            ->toggleable();
    }

    public static function getAuthorFilters(): array
    {
        return [
            SelectFilter::make('author_id')
                ->label(__('core::core.author'))
                ->options(fn () => static::getAuthorOptions())
                ->searchable(),
        ];
    }
}
