<?php

namespace Moox\Press\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        $this->table = $this->wpPrefix . 'posts';
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('media', function (Builder $builder) {
            $builder->where('post_type', 'attachment');
        });
    }

    public function postMeta()
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
        $wpslug = ltrim($wpslug, $wpslug[0]);

        // TODO: Check if the file is an image
        // TODO: Read wp-config.php to get the upload path

        return $file ? asset($wpslug . '/wp-content/uploads/' . $file) : '';
    }

    public function getImageSizesAttribute()
    {
        $sizes = $this->postMeta()->where('meta_key', '_wp_attachment_metadata')->first()->meta_value;

        return $sizes ? unserialize($sizes)['sizes'] : [];
    }

    public function setImageUrlAttribute($value)
    {
        $this->postMeta()->updateOrCreate(['meta_key' => '_wp_attached_file'], ['meta_value' => $value]);
    }

    public function setImageSizesAttribute($value)
    {
        $this->postMeta()->updateOrCreate(['meta_key' => '_wp_attachment_metadata'], ['meta_value' => serialize($value)]);
    }
}
