<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Moox\Media\Models\Media;

/**
 * Outcome of {@see MediaUploadService::upload()} / {@see MediaUploadService::replace()}.
 *
 * Call sites map this to UI (Filament notifications) or HTTP (201 / 409) — the service
 * itself never sends notifications or HTTP responses.
 */
final class MediaUploadResult
{
    public function __construct(
        public readonly MediaUploadStatus $status,
        public readonly ?Media $media = null,
        public readonly ?Media $existingMedia = null,
        public readonly ?string $fileHash = null,
        public readonly ?string $fileName = null,
        public readonly ?Media $replacedMedia = null,
    ) {
    }

    public function wasCreated(): bool
    {
        return $this->status === MediaUploadStatus::Created;
    }

    public function isDuplicate(): bool
    {
        return $this->status === MediaUploadStatus::Duplicate;
    }

    public function wasReplaced(): bool
    {
        return $this->status === MediaUploadStatus::Replaced;
    }

    public function wasEdited(): bool
    {
        return $this->status === MediaUploadStatus::Edited;
    }

    public function succeeded(): bool
    {
        return in_array($this->status, [
            MediaUploadStatus::Created,
            MediaUploadStatus::Replaced,
            MediaUploadStatus::Edited,
        ], true);
    }
}
