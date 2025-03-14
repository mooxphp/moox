<?php

namespace Moox\Media\Tables\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

class CustomImageColumn extends ImageColumn
{
    public function getState(): mixed
    {
        /** @var Model|Media|null $record */
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if ($record instanceof Media) {
            return $record->getUrl();
        }

        $mediaIds = MediaUsable::where('media_usable_id', $record->getKey())
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
        /** @var Model|Media|null $record */
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if ($record instanceof Media) {
            if (str_starts_with($record->mime_type, 'image/')) {
                return $record->getUrl();
            }

            $iconMap = [
                'application/pdf' => '/vendor/media/icons/pdf.svg',
                'application/msword' => '/vendor/media/icons/doc.svg',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '/vendor/media/icons/doc.svg',
                'application/vnd.ms-excel' => '/vendor/media/icons/xls.svg',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '/vendor/media/icons/xls.svg',
                'application/vnd.ms-powerpoint' => '/vendor/media/icons/ppt.svg',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '/vendor/media/icons/ppt.svg',
                'video/mp4' => '/vendor/media/icons/vid.svg',
                'video/webm' => '/vendor/media/icons/vid.svg',
                'video/quicktime' => '/vendor/media/icons/vid.svg',
                'audio/mpeg' => '/vendor/media/icons/vid.svg',
                'audio/wav' => '/vendor/media/icons/vid.svg',
                'audio/ogg' => '/vendor/media/icons/vid.svg',
                'image/svg+xml' => '/vendor/media/icons/svg.svg',
                'image/jpeg' => '/vendor/media/icons/jpg.svg',
                'image/png' => '/vendor/media/icons/png.svg',
                'image/vnd.adobe.photoshop' => '/vendor/media/icons/psd.svg',
                'application/illustrator' => '/vendor/media/icons/ai.svg',
                'application/eps' => '/vendor/media/icons/eps.svg',
                'application/acad' => '/vendor/media/icons/cad.svg',
                'application/dwg' => '/vendor/media/icons/cad.svg',
                'application/dxf' => '/vendor/media/icons/cad.svg',
                'message/rfc822' => '/vendor/media/icons/eml.svg',
                'application/vnd.ms-outlook' => '/vendor/media/icons/oft.svg',
                'application/onenote' => '/vendor/media/icons/one.svg',
                'application/zip' => '/vendor/media/icons/zip.svg',
                'application/x-zip-compressed' => '/vendor/media/icons/zip.svg',
                'application/x-rar-compressed' => '/vendor/media/icons/zip.svg',
            ];

            return $iconMap[$record->mime_type] ?? '/vendor/media/icons/fck.svg';
        }

        $mediaId = MediaUsable::where('media_usable_id', $record->getKey())
            ->where('media_usable_type', get_class($record))
            ->join('media', 'media_usables.media_id', '=', 'media.id')
            ->where('media.uuid', $state)
            ->value('media.id');

        if (! $mediaId) {
            return null;
        }

        $media = Media::find($mediaId);

        if (! $media) {
            return null;
        }

        if (str_starts_with($media->mime_type, 'image/')) {
            return $media->getUrl();
        }

        $iconMap = [
            'application/pdf' => '/vendor/media/icons/pdf.svg',
            'application/msword' => '/vendor/media/icons/doc.svg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '/vendor/media/icons/doc.svg',
            'application/vnd.ms-excel' => '/vendor/media/icons/xls.svg',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '/vendor/media/icons/xls.svg',
            'application/vnd.ms-powerpoint' => '/vendor/media/icons/ppt.svg',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '/vendor/media/icons/ppt.svg',
            'video/mp4' => '/vendor/media/icons/vid.svg',
            'video/webm' => '/vendor/media/icons/vid.svg',
            'video/quicktime' => '/vendor/media/icons/vid.svg',
            'audio/mpeg' => '/vendor/media/icons/vid.svg',
            'audio/wav' => '/vendor/media/icons/vid.svg',
            'audio/ogg' => '/vendor/media/icons/vid.svg',
            'image/svg+xml' => '/vendor/media/icons/svg.svg',
            'image/jpeg' => '/vendor/media/icons/jpg.svg',
            'image/png' => '/vendor/media/icons/png.svg',
            'image/vnd.adobe.photoshop' => '/vendor/media/icons/psd.svg',
            'application/illustrator' => '/vendor/media/icons/ai.svg',
            'application/eps' => '/vendor/media/icons/eps.svg',
            'application/acad' => '/vendor/media/icons/cad.svg',
            'application/dwg' => '/vendor/media/icons/cad.svg',
            'application/dxf' => '/vendor/media/icons/cad.svg',
            'message/rfc822' => '/vendor/media/icons/eml.svg',
            'application/vnd.ms-outlook' => '/vendor/media/icons/oft.svg',
            'application/onenote' => '/vendor/media/icons/one.svg',
            'application/zip' => '/vendor/media/icons/zip.svg',
            'application/x-zip-compressed' => '/vendor/media/icons/zip.svg',
            'application/x-rar-compressed' => '/vendor/media/icons/zip.svg',
        ];

        return $iconMap[$media->mime_type] ?? '/vendor/media/icons/fck.svg';
    }
}
