<?php

namespace Moox\Core\Traits\Simple;

use Filament\Forms\Components\Actions;

trait SingleSimpleInResource
{
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

    public static function getTableActions()
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

    public static function getBulkActions()
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
}
