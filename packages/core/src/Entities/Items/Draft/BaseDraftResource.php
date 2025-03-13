<?php

namespace Moox\Core\Entities\Items\Draft;

use Filament\Forms\Components\Actions;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class BaseDraftResource extends BaseResource
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
        if (config('draft.single')) {
            return false;
        }

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
            static::getSaveAction()->extraAttributes(attributes: ['class' => 'w-full']),
            static::getCancelAction()->extraAttributes(attributes: ['class' => 'w-full']),
        ];

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction()->extraAttributes(attributes: ['class' => 'w-full']);
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction()->extraAttributes(attributes: ['class' => 'w-full']);
        }

        if (static::enableEdit()) {
            $actions[] = static::getEditAction()->extraAttributes(attributes: ['class' => 'w-full']);
        }

        return Actions::make($actions);
    }

    public static function getFooterActions(): Actions
    {
        return Actions::make([
            static::getSaveAction(),
            static::getCancelAction(),
        ]);
    }

    public static function query(): Builder
    {
        return parent::getEloquentQuery();
    }
}
