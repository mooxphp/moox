<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Filament\Concerns;

use Filament\Resources\Pages\PageRegistration;
use Heco\FilamentTreeIndex\Filament\Pages\TreeIndexListRecords;

trait ConfiguresTreeIndex
{
    public static function getPages(): array
    {
        return array_merge(
            [
                'index' => static::getTreeIndexListPage()::route('/'),
            ],
            static::getAdditionalResourcePages(),
        );
    }

    /**
     * @return class-string<TreeIndexListRecords>
     */
    abstract protected static function getTreeIndexListPage(): string;

    /**
     * @return array<string, PageRegistration>
     */
    protected static function getAdditionalResourcePages(): array
    {
        return [];
    }
}
