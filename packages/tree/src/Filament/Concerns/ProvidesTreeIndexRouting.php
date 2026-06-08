<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Concerns;

use Filament\Resources\Pages\PageRegistration;
use Moox\Tree\Filament\Pages\TreeIndexListRecords;

trait ProvidesTreeIndexRouting
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
