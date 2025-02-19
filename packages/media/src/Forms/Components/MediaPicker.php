<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

class MediaPicker extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'media::forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveRelationshipsUsing(function (self $component, $state) {
            $record = $component->getRecord();
            if (!$record) {
                return;
            }

            $mediaData = is_array($state) ? $state : [$state];
            $mediaData = array_filter($mediaData, fn($value) => !is_null($value) && $value !== '');

            $attachments = [];

            foreach ($mediaData as $item) {
                $mediaId = is_array($item) ? ($item['id'] ?? null) : $item;

                $media = Media::find($mediaId);

                if (!$media) {
                    continue;
                }

                MediaUsable::firstOrCreate([
                    'media_id' => $media->id,
                    'media_usable_id' => $record->id,
                    'media_usable_type' => get_class($record),
                ]);

                $attachments[] = [
                    'file_name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'alt' => is_array($item) ? ($item['alt'] ?? '') : '',
                    'title' => is_array($item) ? ($item['title'] ?? '') : '',
                    'description' => is_array($item) ? ($item['description'] ?? '') : '',
                    'internal_note' => is_array($item) ? ($item['internal_note'] ?? '') : '',
                ];
            }

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $record->{$fieldName} = json_encode($attachments);
            $record->save();
        });

    }
}
