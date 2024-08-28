<?php

namespace Moox\PressTrainings\Models;

use Moox\Press\Models\WpBasePost;
use Illuminate\Database\Eloquent\Builder;

class WpTraining extends WpBasePost
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('training', function (Builder $builder) {
            $builder
                ->where('post_type', 'schulung')
                ->whereIn('post_status', ['publish', 'draft', 'pending', 'trash', 'future', 'private'])
                ->orderBy('post_modified', 'desc');
        });
    }

    public function trainingsTopic()
    {
        return $this->belongsToMany(WpTrainingsTopic::class, config('press.wordpress_prefix') . 'term_relationships', 'object_id', 'term_taxonomy_id');
    }
}
