<?php

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Filament\Actions\CreateAction;

trait SingleSoftDeleteInPages
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create'),
        ];
    }
}
