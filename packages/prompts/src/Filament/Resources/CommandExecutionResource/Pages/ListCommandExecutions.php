<?php

namespace Moox\Prompts\Filament\Resources\CommandExecutionResource\Pages;

use Moox\Core\Entities\Items\Item\Pages\BaseListItems;
use Moox\Prompts\Filament\Resources\CommandExecutionResource;

class ListCommandExecutions extends BaseListItems
{
    public static string $resource = CommandExecutionResource::class;

    public function getHeaderActions(): array
    {
        return [];
    }
}
