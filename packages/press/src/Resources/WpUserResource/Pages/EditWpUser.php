<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource;

class EditWpUser extends EditRecord
{
    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    public function edit($record)
    {
        $user = WpUser::find($record);
        dd($user->toArray());
        // Continue with the rest of your edit logic...
    }
}
