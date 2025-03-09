<?php

namespace Moox\Core\Entities\Items\Item;

use Filament\Forms\Components\Actions;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class ItemResource extends BaseResource
{
    use HasResourceTabs;

    public static function enableCreate(): bool
    {
        return true;
    }

    public static function enableEdit(): bool
    {
        return true;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        return true;
    }

    /**
     * @return mixed[]
     */
    public static function getTableActions(): array
    {
        $actions = [];

        if (static::enableEdit()) {
            $actions[] = static::getEditTableAction();
        }

        if (static::enableView()) {
            $actions[] = static::getViewTableAction();
        }

        return $actions;
    }

    /**
     * @return mixed[]
     */
    public static function getBulkActions(): array
    {
        $actions = [];

        if (static::enableDelete()) {
            $actions[] = static::getDeleteBulkAction();
        }

        return $actions;
    }

    public static function getFormActions(): Actions
    {
        $actions = [
            static::getSaveAction(),
            static::getCancelAction(),
        ];

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction();
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction();
        }

        if (static::enableEdit()) {
            $actions[] = static::getEditAction();
        }

        return Actions::make($actions);
    }

    public static function query(): Builder
    {
        return parent::getEloquentQuery();
    }
}
