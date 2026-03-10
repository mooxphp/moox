<?php

namespace Moox\Media\Tables\Columns;

use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Model;
use Moox\Media\Helpers\MediaIconHelper;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

class CustomImageColumn extends ImageColumn
{
    protected ?string $conversion = null;

    public function getState(): mixed
    {
        /** @var Model|Media|null $record */
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if ($record instanceof Media) {
            return $this->conversion
                ? $record->getUrl($this->conversion)
                : $record->getUrl();
        }

        $mediaIds = MediaUsable::query()
            ->where('media_usable_id', $record->getKey())
            ->where('media_usable_type', get_class($record))
            ->pluck('media_id');

        $media = Media::query()->whereIn('id', $mediaIds)->get();

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
                return $this->conversion
                    ? $record->getUrl($this->conversion)
                    : $record->getUrl();
            }

            return MediaIconHelper::getIconPath($record->mime_type);
        }

        $mediaId = MediaUsable::query()
            ->where('media_usable_id', $record->getKey())
            ->where('media_usable_type', get_class($record))
            ->join('media', 'media_usables.media_id', '=', 'media.id')
            ->where('media.uuid', $state)
            ->value('media.id');

        if (! $mediaId) {
            return null;
        }

        $media = Media::query()->find($mediaId);

        if (! $media) {
            return null;
        }

        if (str_starts_with($media->mime_type, 'image/')) {
            return $this->conversion
                ? $media->getUrl($this->conversion)
                : $media->getUrl();
        }

        return MediaIconHelper::getIconPath($media->mime_type);
    }

    public function conversion(?string $conversion): static
    {
        $this->conversion = $conversion;

        return $this;
    }
}
