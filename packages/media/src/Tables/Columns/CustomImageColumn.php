<?php

namespace Moox\Media\Tables\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

class CustomImageColumn extends ImageColumn
{
    private array $iconMap = [
        'application/pdf' => '/vendor/file-icons/pdf.svg',
        'application/msword' => '/vendor/file-icons/doc.svg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '/vendor/file-icons/doc.svg',
        'application/vnd.ms-excel' => '/vendor/file-icons/xls.svg',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '/vendor/file-icons/xls.svg',
        'application/vnd.ms-powerpoint' => '/vendor/file-icons/ppt.svg',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '/vendor/file-icons/ppt.svg',
        'video/mp4' => '/vendor/file-icons/mp4.svg',
        'video/webm' => '/vendor/file-icons/mp4.svg',
        'video/quicktime' => '/vendor/file-icons/mp4.svg',
        'audio/mpeg' => '/vendor/file-icons/mp3.svg',
        'audio/wav' => '/vendor/file-icons/mp3.svg',
        'audio/ogg' => '/vendor/file-icons/mp3.svg',
        'image/svg+xml' => '/vendor/file-icons/svg.svg',
        'image/jpeg' => '/vendor/file-icons/jpg.svg',
        'image/png' => '/vendor/file-icons/png.svg',
        'image/vnd.adobe.photoshop' => '/vendor/file-icons/psd.svg',
        'application/illustrator' => '/vendor/file-icons/ai.svg',
        'application/eps' => '/vendor/file-icons/eps.svg',
        'application/acad' => '/vendor/file-icons/cad.svg',
        'application/dwg' => '/vendor/file-icons/cad.svg',
        'application/dxf' => '/vendor/file-icons/cad.svg',
        'message/rfc822' => '/vendor/file-icons/eml.svg',
        'application/vnd.ms-outlook' => '/vendor/file-icons/oft.svg',
        'application/onenote' => '/vendor/file-icons/one.svg',
        'application/zip' => '/vendor/file-icons/zip.svg',
        'application/x-zip-compressed' => '/vendor/file-icons/zip.svg',
        'application/x-rar-compressed' => '/vendor/file-icons/zip.svg',
        'application/x-acrobat' => '/vendor/file-icons/acrobat.svg',
        'application/after-effects' => '/vendor/file-icons/ae.svg',
        'video/x-msvideo' => '/vendor/file-icons/avi.svg',
        'text/css' => '/vendor/file-icons/css.svg',
        'text/csv' => '/vendor/file-icons/csv.svg',
        'application/x-dwg' => '/vendor/file-icons/dwg.svg',
        'application/postscript' => '/vendor/file-icons/eps.svg',
        'application/x-folder' => '/vendor/file-icons/folder.svg',
        'text/html' => '/vendor/file-icons/html.svg',
        'application/x-indesign' => '/vendor/file-icons/indd.svg',
        'application/javascript' => '/vendor/file-icons/js.svg',
        'audio/mp3' => '/vendor/file-icons/mp3.svg',
        'application/x-onedrive' => '/vendor/file-icons/onedrive.svg',
        'application/x-outlook' => '/vendor/file-icons/outlook.svg',
        'application/x-ppj' => '/vendor/file-icons/ppj.svg',
        'text/plain' => '/vendor/file-icons/txt.svg',
        'application/xml' => '/vendor/file-icons/xml.svg',
    ];

    public function getState(): mixed
    {
        /** @var Model|Media|null $record */
        $record = $this->getRecord();

        if (!$record) {
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

        if (!$record) {
            return null;
        }

        if ($record instanceof Media) {
            if (str_starts_with($record->mime_type, 'image/')) {
                return $record->getUrl();
            }
            return $this->iconMap[$record->mime_type] ?? '/vendor/file-icons/svg/unknown.svg';
        }

        $mediaId = MediaUsable::where('media_usable_id', $record->getKey())
            ->where('media_usable_type', get_class($record))
            ->join('media', 'media_usables.media_id', '=', 'media.id')
            ->where('media.uuid', $state)
            ->value('media.id');

        if (!$mediaId) {
            return null;
        }

        $media = Media::find($mediaId);

        if (!$media) {
            return null;
        }

        if (str_starts_with($media->mime_type, 'image/')) {
            return $media->getUrl();
        }

        return $this->iconMap[$media->mime_type] ?? '/vendor/file-icons/svg/unknown.svg';
    }
}
