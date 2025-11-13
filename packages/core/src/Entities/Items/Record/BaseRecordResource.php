<?php

namespace Moox\Core\Entities\Items\Record;

use Filament\Schemas\Components\Actions;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Moox\Core\Entities\BaseResource;
use Moox\Core\Traits\HasStatusColors;
use Moox\Core\Traits\Tabs\HasResourceTabs;

class BaseRecordResource extends BaseResource
{
    use HasResourceTabs, HasStatusColors;

    protected static function getReadonlyConfig(): bool
    {
        $entityType = static::getEntityType();

        return config("{$entityType}.readonly", false);
    }

    protected static function getEntityType(): string
    {
        return 'record';
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

    public static function enableRestore(): bool
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

        if (static::enableRestore()) {
            $actions[] = static::getRestoreTableAction();
        }

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

        if (static::enableRestore()) {
            $actions[] = static::getRestoreBulkAction();
        }

        return $actions;
    }

    public static function getFormActions(): Actions
    {
        $actions = [
            static::getSaveAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
            static::getCancelAction()->extraAttributes(attributes: ['style' => 'width: 100%;']),
        ];

        if (static::enableRestore()) {
            $actions[] = static::getRestoreAction()->extraAttributes(attributes: ['style' => 'width: 100%;']);
        }

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

    public static function query(): Builder
    {
        return parent::getEloquentQuery();
    }

    /**
     * Get standard timestamp fields
     */
    public static function getStandardTimestampFields(): array
    {
        return [
            static::getCreatedAtTextEntry(),
            static::getUpdatedAtTextEntry(),
        ];
    }

    public static function getStatusColumn(): TextColumn
    {
        return TextColumn::make('status')
            ->label(__('core::core.status'))
            ->badge()
            ->formatStateUsing(function ($state) {
                if ($state instanceof \BackedEnum) {
                    return $state->value;
                }

                return (string) $state;
            })
            ->color(function ($state): string {
                $value = $state instanceof \BackedEnum ? $state->value : (string) $state;

                return static::getStatusColor(strtolower($value));
            });
    }
}
