<?php

namespace Moox\Core\Traits\Simple;

use Filament\Pages\Actions\CreateAction;

trait SingleSimpleInListPage
{
    protected function getHeaderActions(): array
    {
        $actions = [];

        $resource = static::getResource();

        if ($resource::enableCreate()) {
            $actions[] = CreateAction::make('create');
        }

        return $actions;
    }
}
