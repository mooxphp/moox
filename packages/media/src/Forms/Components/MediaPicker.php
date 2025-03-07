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
            if (! $record) {
                return;
            }

            $mediaIds = is_array($state) ? $state : [$state];

            MediaUsable::where('media_usable_id', $record->id)
                ->where('media_usable_type', get_class($record))
                ->whereNotIn('media_id', $mediaIds)
                ->delete();

            $attachments = [];
            $index = 1;

            foreach ($mediaIds as $mediaId) {
                $media = Media::find($mediaId);

                if (! $media) {
                    continue;
                }

                MediaUsable::firstOrCreate([
                    'media_id' => $media->id,
                    'media_usable_id' => $record->id,
                    'media_usable_type' => get_class($record),
                ]);

                $attachments[$index] = [
                    'file_name' => $media->file_name,
                    'title' => $media->title,
                    'description' => $media->description,
                    'internal_note' => $media->internal_note,
                    'alt' => $media->alt,
                ];

                $index++;
            }

            $statePath = $component->getStatePath();
            $fieldName = last(explode('.', $statePath));

            $columnType = \Schema::getColumnType($record->getTable(), $fieldName);

            if ($columnType === 'json') {
                $record->{$fieldName} = $component->isMultiple() ? $attachments : ($attachments[1] ?? null);
            } else {
                $record->{$fieldName} = json_encode($component->isMultiple() ? $attachments : ($attachments[1] ?? null), JSON_UNESCAPED_UNICODE);
            }

            $record->save();
        });
    }
}
