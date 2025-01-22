<?php

namespace Moox\PressWiki\Models;

use Illuminate\Database\Eloquent\Builder;
use Moox\Press\Models\WpBasePost;
use Override;

class WpWiki extends WpBasePost
{
    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('wiki', function (Builder $builder): void {
            $builder
                ->where('post_type', 'wiki')
                ->whereIn('post_status', ['publish', 'draft', 'pending', 'trash', 'future', 'private'])
                ->orderBy('post_modified', 'desc');
        });
    }

    public function letterTopics()
    {
        return $this->belongsToMany(WpWikiLetterTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function companyTopics()
    {
        return $this->belongsToMany(WpWikiCompanyTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function departmentTopics()
    {
        return $this->belongsToMany(WpWikiDepartmentTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function locationTopics()
    {
        return $this->belongsToMany(WpWikiLocationTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }

    public function wikiTopics()
    {
        return $this->belongsToMany(WpWikiTopic::class, config('press.wordpress_prefix').'term_relationships', 'object_id', 'term_taxonomy_id');
    }
}
