<?php

namespace Moox\Passkey\Resources\PasskeyResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Passkey\Models\Passkey;
use Moox\Passkey\Resources\PasskeyResource;
use Moox\Passkey\Resources\PasskeyResource\Widgets\PasskeyWidgets;

class ListPage extends ListRecords
{
    public static string $resource = PasskeyResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            //PasskeyWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('passkey::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Passkey {
                    return $model::create($data);
                }),
        ];
    }
}
