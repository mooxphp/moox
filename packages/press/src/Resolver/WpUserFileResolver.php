<?php

namespace Moox\Press\Resolver;

use Illuminate\Support\Facades\Storage;
use Moox\Press\Models\WpUser;
use Moox\Sync\Resolver\AbstractFileResolver;

class WpUserFileResolver extends AbstractFileResolver
{
    protected $wpUser;

    public function __construct(WpUser $wpUser)
    {
        parent::__construct($wpUser);
        $this->wpUser = $wpUser;
    }

    public function getFileFields(): array
    {
        $fileFields = [];
        $fieldsToCheck = config('sync.file_sync_fieldsearch', []);

        foreach ($this->wpUser->getAttributes() as $field => $value) {
            foreach ($fieldsToCheck as $keyword) {
                if (stripos($field, $keyword) !== false) {
                    $fileFields[] = $field;
                    break;
                }
            }
        }

        // Add any custom logic for WpUser-specific file fields
        $avatarField = $this->wpUser->getMeta('wp_user_avatar');
        if ($avatarField) {
            $fileFields[] = 'wp_user_avatar';
        }

        return array_unique($fileFields);
    }

    public function getFileData(string $field): ?array
    {
        if ($field === 'wp_user_avatar') {
            $attachmentPath = $this->wpUser->getMeta('wp_user_avatar_path');
        } else {
            $attachmentPath = $this->wpUser->$field;
        }

        if (! $attachmentPath || ! Storage::exists($attachmentPath)) {
            return null;
        }

        return [
            'path' => $attachmentPath,
            'size' => Storage::size($attachmentPath),
            'last_modified' => Storage::lastModified($attachmentPath),
            'mime_type' => Storage::mimeType($attachmentPath),
            'extension' => pathinfo($attachmentPath, PATHINFO_EXTENSION),
        ];
    }
}
