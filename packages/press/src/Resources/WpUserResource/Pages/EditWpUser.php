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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = WpUser::with('userMeta')->find($data['ID']);

        if ($user) {
            foreach ($user->userMeta as $meta) {
                $data[$meta->meta_key] = $meta->meta_value;
            }
        }

        return $data;
    }
}
