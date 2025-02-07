<?php

declare(strict_types=1);

namespace Moox\Core\Traits\UserRelation;

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;

trait UserInResource
{
    protected static ?string $userModel = null;

    protected static function initUserModel(): void
    {
        if (static::$userModel === null) {
            static::$userModel = config('builder.user_model');
        }
    }

    protected static function getUserOptions(): array
    {
        static::initUserModel();

        return static::$userModel::query()->get()->pluck('name', 'id')->toArray();
    }

    protected static function shouldShowUserField(): bool
    {
        static::initUserModel();

        return static::$userModel && class_exists(static::$userModel);
    }

    public static function getUserFormField(): Select
    {
        return Select::make('user_id')
            ->label(__('core::core.user'))
            ->options(fn (): array => static::getUserOptions())
            ->default(fn () => auth()->id())
            ->required()
            ->searchable()
            ->visible(fn (): bool => static::shouldShowUserField());
    }

    public static function getUserTableColumn(): ImageColumn
    {
        return ImageColumn::make('user.avatar_url')
            ->label(__('core::core.user'))
            ->tooltip(fn ($record) => $record->user?->name)
            ->alignment('center')
            ->circular()
            ->visible(fn (): bool => static::shouldShowUserField())
            ->toggleable();
    }

    public static function getUserFilters(): array
    {
        return [
            SelectFilter::make('user_id')
                ->label(__('core::core.user'))
                ->options(fn (): array => static::getUserOptions())
                ->searchable(),
        ];
    }
}
