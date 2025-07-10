<?php

namespace Moox\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class NewsTranslation extends Model
{
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        // Translation fields
        'locale',
        'news_id',
        'title',
        'slug',
        'status',
        'excerpt',
        'content',
        'author_id',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'deleted_by_id',
        'deleted_by_type',

        // Publishing schedule fields
        'to_publish_at',
        'published_at',
        'to_unpublish_at',
        'unpublished_at',

        // Actor fields
        'published_by_id',
        'published_by_type',
        'unpublished_by_id',
        'unpublished_by_type',

        // Soft delete and restoration fields
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    protected $casts = [
        // DateTime casts
        'to_publish_at' => 'datetime',
        'published_at' => 'datetime',
        'to_unpublish_at' => 'datetime',
        'unpublished_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->morphTo(null, 'created_by_type', 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->morphTo(null, 'updated_by_type', 'updated_by_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::id();

                $model->created_by_id = $userId;
                $model->created_by_type = 'App\\Models\\User';
                $model->updated_by_id = $userId;
                $model->updated_by_type = 'App\\Models\\User';

                if (empty($model->author_id)) {
                    $model->author_id = $userId;
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $userId = Auth::id();

                $model->updated_by_id = $userId;
                $model->updated_by_type = 'App\\Models\\User';
            }
        });
    }
}
