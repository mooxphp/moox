<?php

namespace Moox\Core\Entities\Items\Item;

use Filament\Schemas\Components\Actions;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class BaseItemResource extends BaseResource
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
            static::getSaveAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
            static::getCancelAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
        ];

        if (static::enableCreate()) {
            $actions[] = static::getSaveAndCreateAnotherAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableDelete()) {
            $actions[] = static::getDeleteAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

        if (static::enableEdit()) {
            $actions[] = static::getEditAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
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

    public static function getStandardTimestampFields(): array
    {
        return [
            static::getCreatedAtTextEntry(),
            static::getUpdatedAtTextEntry(),
        ];
    }
}
