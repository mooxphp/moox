<?php

namespace Moox\Draft\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\HasScheduledPublish;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Draft extends BaseDraftModel implements HasMedia
{
    use HasModelTaxonomy, HasScheduledPublish, InteractsWithMedia, SoftDeletes;

    public $translatedAttributes = ['title', 'slug', 'description', 'content'];

    protected $fillable = [
        'is_active',
        'data',
        'image',
        'author_id',
        'type',
        'color',
        'due_at',
        'uuid',
        'ulid',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'data' => 'json',
        'image' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
            $model->ulid = (string) \Illuminate\Support\Str::ulid();
        });
    }

    public function getUlidAttribute(): string
    {
        return $this->ulid ?? (string) \Illuminate\Support\Str::ulid();
    }

    public function getUuidAttribute(): string
    {
        return $this->uuid ?? (string) \Illuminate\Support\Str::uuid();
    }

    public static function getResourceName(): string
    {
        return 'draft';
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
