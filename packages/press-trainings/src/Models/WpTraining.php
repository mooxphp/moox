<?php

namespace Moox\PressTrainings\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpBasePost;
use Override;

class WpTraining extends WpBasePost
{
    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('training', function (Builder $builder): void {
            $builder
                ->where('post_type', 'schulung')
                ->whereIn('post_status', ['publish', 'draft', 'pending', 'trash', 'future', 'private'])
                ->orderBy('post_modified', 'desc');
        });
    }

    public function trainingsTopic()
    {
        return $this->belongsToMany(WpTrainingsTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }
}
