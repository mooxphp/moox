<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property string $image_url
 * @property mixed $attachment
 * @property array $image_sizes
 * @property string $asset
 */
class WpMedia extends WpBasePost
{
    use HasFactory;

    protected $appends = [
        'asset',
        'image_url',
        'image_sizes',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->wpPrefix = config('press.wordpress_prefix');
        $this->table = $this->wpPrefix.'posts';
    }

    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('media', function (Builder $builder): void {
            $builder->where('post_type', 'attachment');
        });
    }

    #[Override]
    public function postMeta(): HasMany
    {
        return $this->hasMany(WpPostMeta::class, 'post_id', 'ID');
    }

    public function getImageUrlAttribute()
    {
        return $this->postMeta()->where('meta_key', '_wp_attached_file')->first()->meta_value ?? '';
    }

    public function getAssetAttribute()
    {
        $file = $this->image_url;

        $wpslug = config('press.wordpress_slug');
        $wpslug = ltrim((string) $wpslug, $wpslug[0]);

        return $file ? asset($wpslug.'/wp-content/uploads/'.$file) : '';
    }

    public function getImageSizesAttribute()
    {
        /** @var ?WpPostMeta $metadata */
        $metadata = $this->postMeta()->where('meta_key', '_wp_attachment_metadata')->first();

        return $metadata ? unserialize($metadata->meta_value)['sizes'] : [];
    }

    public function setImageUrlAttribute($value): void
    {
        $this->postMeta()->updateOrCreate(['meta_key' => '_wp_attached_file'], ['meta_value' => $value]);
    }

    public function setImageSizesAttribute($value): void
    {
        $this->postMeta()->updateOrCreate(['meta_key' => '_wp_attachment_metadata'], ['meta_value' => serialize($value)]);
    }
}
