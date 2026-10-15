<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\HonorificTitle\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Salutation\Resources\HonorificTitleResource;

class ListHonorificTitles extends ListRecords
{
    protected static string $resource = HonorificTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
