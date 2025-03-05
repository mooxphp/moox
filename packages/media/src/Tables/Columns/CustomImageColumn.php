<?php



namespace Moox\Media\Tables\Columns;



use Filament\Tables\Columns\ImageColumn;

use Moox\Media\Models\Media;

use Moox\Media\Models\MediaUsable;



class CustomImageColumn extends ImageColumn
{

    public function getState(): mixed
    {

        $record = $this->getRecord();



        if (!$record) {

            return null;

        }


        if ($record instanceof Media) {

            return $record->getUrl();

        }


        $mediaIds = MediaUsable::where('media_usable_id', $record->id)

            ->where('media_usable_type', get_class($record))

            ->pluck('media_id');



        $media = Media::whereIn('id', $mediaIds)->get();

        return $media

            ->sortBy('order_column')

            ->pluck('uuid')

            ->all();

    }

    public function getImageUrl(?string $state = null): ?string
    {

        $record = $this->getRecord();



        if (!$record) {

            return null;

        }



        if ($record instanceof Media) {

            return $record->getUrl();

        }



        $mediaId = MediaUsable::where('media_usable_id', $record->id)

            ->where('media_usable_type', get_class($record))

            ->join('media', 'media_usables.media_id', '=', 'media.id')

            ->where('media.uuid', $state)

            ->value('media.id');



        if (!$mediaId) {

            return null;

        }

        $media = Media::find($mediaId);

        return $media ? $media->getUrl() : null;

    }

}