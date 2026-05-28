<?php

namespace Moox\User\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Moox\User\Support\HasRolesTrait;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property string|null $avatar_url
 * @property string|null $first_name
 * @property string|null $last_name
 */
class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    use HasFactory, HasRolesTrait, InteractsWithMedia, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'gender',
        'title',
        'first_name',
        'last_name',
        'email',
        'website',
        'description',
        'password',
        'profile_photo_path',
        'avatar_url',

    ];

    protected $searchableFields = ['*'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        $email = strtolower((string) $this->email);

        if ($panel->getId() === 'admin' && $email === 'thomas.herrmann@wilo.com') {
            return false;
        }

        return preg_match('/@[^@\s]+\.[a-z]{2,63}$/i', $email) === 1
            && $this->hasVerifiedEmail();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $value = $this->avatar_url;

        if (blank($value)) {
            return null;
        }

        // The MediaPicker may store JSON objects/arrays in this column.
        $trimmed = trim((string) $value);

        if ($trimmed !== '' && (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))) {
            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                    // Sometimes the payload is an object with a single numeric key, e.g. {"1": {...}}.
                    // Treat that key as a directory hint (media/{id}/{file_name}), without requiring Spatie Media.
                    if (is_array($decoded) && ! array_is_list($decoded) && count($decoded) === 1) {
                        $firstKey = array_key_first($decoded);
                        $first = $decoded[$firstKey] ?? null;

                        if (is_array($first)) {
                            if (
                                (is_int($firstKey) || ctype_digit((string) $firstKey))
                                && is_string($first['file_name'] ?? null)
                            ) {
                                $mediaPath = 'media/'.((int) $firstKey).'/'.$first['file_name'];

                                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                                $disk = Storage::disk('public');

                                if ($disk->exists($mediaPath)) {
                                    return $disk->url($mediaPath);
                                }
                            }

                            $decoded = $first;
                        }
                    }

                    if (is_array($decoded) && array_is_list($decoded)) {
                        $decoded = $decoded[0] ?? null;
                    }

                    if (is_array($decoded)) {
                        $value = $decoded['path']
                            ?? $decoded['file_path']
                            ?? $decoded['file_name']
                            ?? null;
                    } elseif (is_string($decoded)) {
                        $value = $decoded;
                    } else {
                        $value = null;
                    }
            }
        }

        if (blank($value) || ! is_string($value)) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $path = ltrim($value, '/');

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        // If it's just a filename, it may live under "media/{id}/{file_name}".
        // Keep it package-standalone: filesystem search, no DB required.
        if (! str_contains($path, '/')) {
            $mediaRoot = Storage::disk('public')->path('media');
            $matches = glob($mediaRoot.'/*/'.$path) ?: [];

            if (! empty($matches)) {
                $absoluteMatch = (string) $matches[0];
                $publicRoot = Storage::disk('public')->path('');
                $relativeMatch = ltrim(str_replace($publicRoot, '', $absoluteMatch), '/');

                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('public');

                if ($disk->exists($relativeMatch)) {
                    return $disk->url($relativeMatch);
                }
            }
        }

        return null;
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300);
    }

    public function mediaThroughUsables()
    {
        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id'
        )->where('media_usables.media_usable_type', '=', static::class);
    }
}
