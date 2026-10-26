<?php

declare(strict_types=1);

namespace Moox\Customer\Resources\Customer\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Customer\Resources\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
