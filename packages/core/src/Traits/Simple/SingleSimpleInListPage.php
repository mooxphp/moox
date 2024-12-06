<?php

namespace Moox\Core\Traits\Simple;

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
