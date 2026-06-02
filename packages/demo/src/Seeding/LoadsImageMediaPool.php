<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Illuminate\Support\Collection;
use Moox\Media\Models\Media;

trait LoadsImageMediaPool
{
    /**
     * @return Collection<int, Media>
     */
    protected function loadImageMediaPool(): Collection
    {
        if (! class_exists(Media::class)) {
            return collect();
        }

        $mediaClass = Media::class;

        $query = $mediaClass::query()
            ->where(function ($builder): void {
                $builder
                    ->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('mime_type', [
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                        'image/gif',
                        'image/svg+xml',
                    ]);
            });

        $ids = $query->pluck('id');

        if ($ids->isEmpty()) {
            $ids = $mediaClass::query()->limit(500)->pluck('id');
        }

        if ($ids->isEmpty()) {
            return collect();
        }

        return $mediaClass::query()->whereIn('id', $ids)->get();
    }

    /**
     * @return array{media_id: int, locale: string}|null
     */
    protected function randomImageFieldFromPool(Collection $mediaPool, string $locale): ?array
    {
        if ($mediaPool->isEmpty()) {
            return null;
        }

        /** @var Media $media */
        $media = $mediaPool->random();

        return [
            'media_id' => (int) $media->getKey(),
            'locale' => $locale,
        ];
    }
}
