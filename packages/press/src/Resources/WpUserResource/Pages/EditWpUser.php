<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Exception;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Moox\Press\Models\WpBasePost;
use Moox\Press\Models\WpPostMeta;
use Moox\Press\Models\WpUser;
use Moox\Press\Resources\WpUserResource;
use Override;
use Symfony\Component\Mime\MimeTypes;

class EditWpUser extends EditRecord
{
    protected static string $resource = WpUserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    #[Override]
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = WpUser::with(['userMeta', 'attachment'])->find($data['ID']);

        if ($user) {
            foreach ($user->userMeta as $meta) {
                /** @var \Moox\Press\Models\WpUserMeta $meta */
                $data[$meta->meta_key] = $meta->meta_value;
            }
        }

        if ($user->attachment) {
            /** @var \Moox\Press\Models\WpMedia $user->attachment */
            $data['image_url'] = $user->attachment->image_url;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $user = $this->record;

        if ($user instanceof WpUser) {
            $mainFields = [
                'user_login', 'user_email', 'user_url', 'user_registered', 'user_activation_key',
                'user_status', 'display_name', 'user_nicename',
            ];

            foreach ($mainFields as $field) {
                if (isset($this->data[$field])) {
                    $user->setAttribute($field, $this->data[$field]);
                }
            }

            $user->save();

            $metaFields = config('press.default_user_meta', []);
            $userAvatarMetaKey = config('press.user_avatar_meta');

            if ($userAvatarMetaKey) {
                unset($metaFields[$userAvatarMetaKey]);
            }

            foreach ($metaFields as $metaKey => $defaultValue) {
                if (isset($this->data[$metaKey])) {
                    $user->addOrUpdateMeta($metaKey, $this->data[$metaKey]);
                }
            }

            $this->handleAvatarUpload($user, $userAvatarMetaKey);
        } else {
            Log::error('User record is not an instance of WpUser in EditWpUser::afterSave');
        }

        /** @var \Illuminate\Database\Eloquent\Model $record */
        $record = $this->record;
        Event::dispatch('eloquent.updated: '.$record::class, $record);
    }

    protected function handleAvatarUpload(WpUser $user, ?string $userAvatarMetaKey): void
    {
        $temporaryFilePath = $this->data['temporary_file_path'] ?? null;
        $originalName = $this->data['original_name'] ?? null;

        if ($temporaryFilePath && $userAvatarMetaKey) {
            $mimeTypes = new MimeTypes;
            $mimeType = $mimeTypes->guessMimeType(storage_path('app/'.$temporaryFilePath));

            if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])) {
                throw new Exception('The file must be an image of type: jpeg, png, gif, webp, or svg.');
            }

            $currentYear = now()->year;
            $currentMonth = sprintf('%02d', now()->month);
            $relativeDirectory = sprintf('%d/%s', $currentYear, $currentMonth);

            $filenameWithoutExtension = pathinfo((string) $originalName, PATHINFO_FILENAME);
            $extension = pathinfo((string) $originalName, PATHINFO_EXTENSION);
            $filename = sprintf('%s.%s', $filenameWithoutExtension, $extension);

            $disk = Storage::disk('press');
            $newPath = sprintf('%s/%s', $relativeDirectory, $filename);

            $disk->put($newPath, Storage::get($temporaryFilePath));

            $fileSize = $disk->size($newPath);
            $imageSize = $this->getImageSize($disk->path($newPath), $mimeType);

            $imageMeta = [
                'file' => $newPath,
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
                ['post_id' => $postId, 'meta_key' => '_wp_attached_file', 'meta_value' => $newPath],
                ['post_id' => $postId, 'meta_key' => '_wp_attachment_metadata', 'meta_value' => serialize($imageMeta)],
            ]);

            $user->addOrUpdateMeta($userAvatarMetaKey, $postId);

            Storage::delete($temporaryFilePath);
        } elseif ($userAvatarMetaKey && empty($this->data['image_url'])) {
            $user->addOrUpdateMeta($userAvatarMetaKey, '');
        }
    }

    protected function getImageSize(string $path, string $mimeType): array
    {
        if ($mimeType === 'image/svg+xml') {
            $svgContent = file_get_contents($path);
            preg_match('/<svg[^>]+(width|height)="([^"]*)"/i', $svgContent, $width);
            preg_match('/<svg[^>]+(width|height)="([^"]*)"/i', $svgContent, $height);

            return [
                isset($width[2]) ? (float) $width[2] : 0,
                isset($height[2]) ? (float) $height[2] : 0,
            ];
        }

        return getimagesize($path);
    }
}
