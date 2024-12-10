<?php

namespace Moox\Core\Traits\Simple;

use Filament\Forms\Components\Actions;

trait SingleSimpleInResource
{
    public static function getTableActions()
    {
        return [
            static::getEditTableAction(),
            static::getViewTableAction(),
        ];
    }

    public static function getBulkActions()
    {
        return [
            static::getDeleteBulkAction(),
        ];
    }

    public static function getFormActions(): Actions
    {
        return Actions::make([
            static::getSaveAction(),
            static::getSaveAndCreateAnotherAction(),
            static::getCancelAction(),
            static::getDeleteAction(),
            static::getEditAction(),
        ]);
    }
}
