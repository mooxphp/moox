<?php

namespace Moox\Core\Entities\Items\Draft;

use Filament\Forms\Components\Actions;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class BaseDraftResource extends BaseResource
{
    use HasResourceTabs;

    protected static function getReadonlyConfig(): bool
    {
        $entityType = static::getEntityType();

        return config("{$entityType}.readonly", false);
    }

    protected static function getEntityType(): string
    {
        return 'draft';
    }

    public static function enableCreate(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enableEdit(): bool
    {
        if (static::getReadonlyConfig()) {
            return false;
        }

        return true;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function enableDelete(): bool
    {
        if (static::getReadonlyConfig()) {
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
