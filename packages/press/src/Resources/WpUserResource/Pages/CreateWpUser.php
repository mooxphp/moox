<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Moox\Press\Models\WpBasePost;
use Moox\Press\Models\WpPostMeta;
use Moox\Press\Models\WpUser;
use Moox\Press\Models\WpUserMeta;
use Moox\Press\Resources\WpUserResource;
use Symfony\Component\Mime\MimeTypes;

class CreateWpUser extends CreateRecord
{
    protected static string $resource = WpUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        if (filled($data['first_name']) && filled($data['last_name'])) {
            $data['display_name'] = $data['first_name'].' '.$data['last_name'];
        }

        return $data;
    }

    public function afterCreate(): void
    {
        $temporaryFilePath = $this->data['temporary_file_path'] ?? null;
        $originalName = $this->data['original_name'] ?? null;
        $attachmentId = null;

        if ($temporaryFilePath) {

            $mimeTypes = new MimeTypes;
            $mimeType = $mimeTypes->guessMimeType(storage_path('app/'.$temporaryFilePath));

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])) {
                throw new \Exception('The file must be an image of type: jpeg, png, gif, webp. or svg.');
            }

            $currentYear = now()->year;
            $currentMonth = sprintf('%02d', now()->month);
            $relativeDirectory = "{$currentYear}/{$currentMonth}";

            $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = "{$filenameWithoutExtension}.{$extension}";

            $disk = Storage::disk('press');
            $newPath = "{$relativeDirectory}/{$filename}";

            $fileSize = $disk->size($newPath);
            $imageSize = getimagesize($disk->path($newPath));
            $imageMeta = [
                'file' => "{$relativeDirectory}/{$filename}",
                'width' => $imageSize[0],
                'height' => $imageSize[1],
                'filesize' => $fileSize,
                'sizes' => [],
                'image_meta' => [],
                'original_image' => $filename,
            ];

            $url = asset('wp/wp-content/uploads/'.$newPath);
            $loggedInUserId = Auth::id();

            $postId = WpBasePost::insertGetId([
                'post_author' => $loggedInUserId,
                'post_date' => now(),
                'post_date_gmt' => now(),
                'post_content' => '',
                'post_title' => $filenameWithoutExtension,
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'post_name' => $filenameWithoutExtension,
                'post_type' => 'attachment',
                'guid' => $url,
                'post_modified' => now(),
                'post_modified_gmt' => now(),
                'to_ping' => '',
                'pinged' => '',
                'post_content_filtered' => '',
                'post_mime_type' => $mimeType,
            ]);

            WpPostMeta::insert([
                ['post_id' => $postId, 'meta_key' => '_wp_attached_file', 'meta_value' => $currentYear.'/'.$currentMonth.'/'.$filename],
                ['post_id' => $postId, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize($imageMeta)],
            ]);

            $attachmentId = $postId;

            Storage::delete($temporaryFilePath);

        }

        $metaDataConfig = config('press.default_user_meta');

        foreach ($metaDataConfig as $metaKey => $defaultValue) {
            $metaValue = $this->data[$metaKey] ?? $defaultValue;

            if ($metaKey === 'mm_sua_attachment_id') {
                if ($temporaryFilePath) {
                    $metaValue = $attachmentId;
                }
            }

            if ($this->record instanceof WpUser) {
                $userId = $this->record->ID;

                WpUserMeta::updateOrCreate(
                    ['user_id' => $userId, 'meta_key' => $metaKey],
                    ['meta_value' => $metaValue]
                );
            }
        }

    }
}
