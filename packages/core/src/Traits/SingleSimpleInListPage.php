<?php

namespace Moox\Core\Traits;

use Filament\Pages\Actions\CreateAction;

trait SingleSimpleInListPage
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create'),
        ];
    }
}
