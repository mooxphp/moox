<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Moox\Press\Models\WpUserMeta;
use Moox\Press\Models\WpMedia;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource;
use Override;

class ViewWpUser extends ViewRecord
{
    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = WpUser::with(['userMeta', 'attachment'])->find($data['ID']);

        if ($user) {
            foreach ($user->userMeta as $meta) {
                /** @var WpUserMeta $meta */
                $data[$meta->meta_key] = $meta->meta_value;
            }
        }

        if ($user->attachment) {
            /** @var WpMedia $user ->attachment */
            $data['image_url'] = $user->attachment->image_url;
        }

        return $data;
    }
}
