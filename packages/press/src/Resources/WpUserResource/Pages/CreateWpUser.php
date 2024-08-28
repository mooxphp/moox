<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        if ($temporaryFilePath) {
            $absolutePath = storage_path('app/'.$temporaryFilePath);

            $mimeTypes = new MimeTypes;
            $mimeType = $mimeTypes->guessMimeType($absolutePath);

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                throw new \Exception('The file must be an image of type: jpeg, png, gif, webp.');
            }

            $currentYear = now()->year;
            $currentMonth = sprintf('%02d', now()->month);
            $relativeDirectory = "wp/wp-content/uploads/{$currentYear}/{$currentMonth}";
            $absoluteDirectory = base_path("public/{$relativeDirectory}");

            if (! file_exists($absoluteDirectory)) {
                mkdir($absoluteDirectory, 0755, true);
            }

            $filename = $originalName;
            $newPath = "{$absoluteDirectory}/{$filename}";
            copy($absolutePath, $newPath);

            $fileSize = filesize($newPath);
            $imageSize = getimagesize($newPath);
            $imageMeta = [
                'file' => "{$relativeDirectory}/{$filename}",
                'width' => $imageSize[0],
                'height' => $imageSize[1],
                'filesize' => $fileSize,
                'sizes' => [],
                'image_meta' => [],
                'original_image' => $filename,
            ];

            $url = env('APP_URL').'/'.$relativeDirectory.'/'.$filename;
            $loggedInUserId = Auth::id();

            $postId = DB::table(config('press.wordpress_prefix').'posts')->insertGetId([
                'post_author' => $loggedInUserId,
                'post_date' => now(),
                'post_date_gmt' => now(),
                'post_content' => '',
                'post_title' => $filename,
                'post_excerpt' => '',
                'post_status' => 'inherit',
                'post_name' => $filename,
                'post_type' => 'attachment',
                'guid' => $url,
                'post_modified' => now(),
                'post_modified_gmt' => now(),
                'to_ping' => '',
                'pinged' => '',
                'post_content_filtered' => '',
                'post_mime_type' => $mimeType,
            ]);

            DB::table(config('press.wordpress_prefix').'postmeta')->insert([
                ['post_id' => $postId, 'meta_key' => '_wp_attached_file', 'meta_value' => $relativeDirectory.'/'.$filename],
                ['post_id' => $postId, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize($imageMeta)],
            ]);

            $metaDataConfig = config('press.default_user_meta');
            $attachmentId = $postId;

            foreach ($metaDataConfig as $metaKey => $defaultValue) {
                $metaValue = $this->data[$metaKey] ?? $defaultValue;

                if ($metaKey === 'mm_sua_attachment_id') {
                    $metaValue = $attachmentId;
                }

                if ($this->record instanceof WpUser) {
                    $userId = $this->record->ID;

                    WpUserMeta::updateOrCreate(
                        ['user_id' => $userId, 'meta_key' => $metaKey],
                        ['meta_value' => $metaValue]
                    );
                }
            }

            if (file_exists($absolutePath)) {
                unlink($absolutePath);
            }
        }

    }
}
