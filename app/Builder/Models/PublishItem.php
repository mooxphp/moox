<?php

declare(strict_types=1);

namespace App\Builder\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Publish\SinglePublishInModel;

class PublishItem extends Model
{
    use BaseInModel, SinglePublishInModel;

    protected $table = 'preview_publish_items';

    protected $fillable = [
        'title',
        'slug',
        'tabs',
        'publish',
        'content',
    ];

    protected $casts = [
        'slug' => 'string',
        'title' => 'string',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publish->status', 'published');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('publish->status', 'scheduled');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('publish->status', 'draft');
    }
}
