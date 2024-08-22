<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Models\WpUser;
use Moox\Press\Models\WpUserMeta;
use Moox\Press\Resources\WpUserResource;

class CreateWpUser extends CreateRecord
{
    protected static string $resource = WpUserResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {

    //     if (filled($data['first_name']) && filled($data['last_name'])) {
    //         $data['display_name'] = $data['first_name'].' '.$data['last_name'];
    //     }

    //     return $data;
    // }

    public function afterCreate(): void
    {
        $metaDataConfig = config('press.default_user_meta');

        foreach ($metaDataConfig as $metaKey => $defaultValue) {
            $metaValue = $this->data[$metaKey] ?? $defaultValue;
            if ($metaKey === 'nickname') {
                $metaValue = $this->data['user_login'];
            }

            if ($this->record instanceof WpUser) {
                $userId = $this->record->ID;

                WpUserMeta::updateOrCreate(
                    ['user_id' => $userId, 'meta_key' => $metaKey],
                    ['meta_value' => $metaValue]
                );
            }
        }
    }
}
