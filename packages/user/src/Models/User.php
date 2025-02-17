<?php

namespace Moox\User\Models;

use Filament\Panel;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Moox\Media\Models\MediaUsable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Moox\Media\Models\Media as CustomMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    use HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

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
        // TODO: Implement roles and permissions.
        // return $this->hasAnyRole(['super_admin', 'filament_user']);
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }

    public function mediaUsables()
    {
        return $this->morphMany(MediaUsable::class, 'media_usable');
    }

     /**
     * Holt alle Medien über die `media_usables`-Tabelle und lädt die Medienobjekte.
     *
     * @param string $collectionName Der Name der Sammlung (Standard: 'default').
     * @param callable|array $filters Filter für die Medien.
     * @return \Illuminate\Support\Collection
     */
    public function getMedia(string $collectionName = 'default', $filters = []): Collection
    {
        // Get the media usable entries related to the user
        $mediaUsables = $this->mediaUsables()
            ->whereHas('media', function ($query) use ($collectionName) {
                $query->where('collection_name', $collectionName);
            })
            ->get();

        // Map media usable entries to media objects
        $media = $mediaUsables->map(function (MediaUsable $mediaUsable) {
            return $mediaUsable->media;
        });

        // Apply filters if provided
        if ($filters) {
            $media = $media->filter($filters);
        }

        return $media;
    }

}
