<?php

declare(strict_types=1);

namespace Moox\Media\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class MediaUploadValidator
{
    /**
     * @param  array<int, string>|null  $acceptedFileTypes  Flow-specific allowlist.
     *                                                      Null falls back to media.upload.resource.accepted_file_types.
     */
    public function ensureAccepted(
        UploadedFile|TemporaryUploadedFile $file,
        ?array $acceptedFileTypes = null,
    ): void {
        $acceptedFileTypes = $this->normalizeAcceptedFileTypes(
            $acceptedFileTypes ?? config('media.upload.resource.accepted_file_types', []),
        );

        if ($acceptedFileTypes === []) {
            return;
        }

        $mimeType = strtolower((string) $file->getMimeType());
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($this->matchesAcceptedTypes($mimeType, $extension, $acceptedFileTypes)) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => __('media::fields.file_type_not_allowed', [
                'fileName' => $file->getClientOriginalName(),
            ]),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeAcceptedFileTypes(mixed $acceptedFileTypes): array
    {
        if (! is_array($acceptedFileTypes)) {
            return [];
        }

        return array_values(array_filter(
            $acceptedFileTypes,
            static fn (mixed $type): bool => is_string($type) && $type !== '',
        ));
    }

    /**
     * @param  array<int, string>  $acceptedFileTypes
     */
    private function matchesAcceptedTypes(string $mimeType, string $extension, array $acceptedFileTypes): bool
    {
        foreach ($acceptedFileTypes as $acceptedFileType) {
            if ($this->matchesAcceptedType($mimeType, $extension, $acceptedFileType)) {
                return true;
            }
        }

        return false;
    }

    private function matchesAcceptedType(string $mimeType, string $extension, string $acceptedFileType): bool
    {
        $acceptedFileType = strtolower(trim($acceptedFileType));

        if ($acceptedFileType === '') {
            return false;
        }

        if (str_starts_with($acceptedFileType, '.')) {
            return $extension === ltrim($acceptedFileType, '.');
        }

        if (str_ends_with($acceptedFileType, '/*')) {
            $prefix = substr($acceptedFileType, 0, -1);

            return str_starts_with($mimeType, $prefix);
        }

        return $mimeType === $acceptedFileType;
    }
}
