<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Moox\Press\Models\WpPost;
use Moox\Press\Models\WpUser;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpUserResource;

class ViewWpUser extends ViewRecord
{
    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = WpUser::with('userMeta')->find($data['ID']);

        if ($user) {
            foreach ($user->userMeta as $meta) {
                $data[$meta->meta_key] = $meta->meta_value;
            }
        }

        $attachmentId = $user->userMeta->where('meta_key', 'mm_sua_attachment_id')->first()?->meta_value;
        if ($attachmentId) {
            $attachment = WpPost::find($attachmentId);
            $data['attachment_guid'] = $attachment['guid'];
        }

        return $data;
    }

 }
