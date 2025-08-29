<?php

namespace Moox\Core\Traits;

trait HasScheduledPublish
{
    /**
     * Scope to find records that should be published.
     */
    public function scopeScheduledForPublishing($query)
    {
        return $query->whereNotNull('to_publish_at')
            ->where('to_publish_at', '<=', now())
            ->whereNull('published_at');
    }

    /**
     * Scope to find records that should be unpublished.
     */
    public function scopeScheduledForUnpublishing($query)
    {
        return $query->whereNotNull('to_unpublish_at')
            ->where('to_unpublish_at', '<=', now())
            ->whereNull('unpublished_at');
    }

    /**
     * Scope to find published records.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope to find unpublished records.
     */
    public function scopeUnpublished($query)
    {
        return $query->whereNotNull('unpublished_at');
    }

    /**
     * Check if record is scheduled for publishing.
     */
    public function isScheduledForPublishing(): bool
    {
        return $this->to_publish_at !== null
            && $this->to_publish_at <= now()
            && $this->published_at === null;
    }

    /**
     * Check if record is scheduled for unpublishing.
     */
    public function isScheduledForUnpublishing(): bool
    {
        return $this->to_unpublish_at !== null
            && $this->to_unpublish_at <= now()
            && $this->unpublished_at === null;
    }
}
