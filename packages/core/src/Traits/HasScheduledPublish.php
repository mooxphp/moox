<?php

namespace Moox\Core\Traits;

trait HasScheduledPublish
{
    /**
     * Scope to find records that should be published.
     */
    public function scopePendingPublish($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to find records that should be unpublished.
     */
    public function scopePendingUnpublish($query)
    {
        return $query->whereNotNull('unpublished_at')
            ->where('unpublished_at', '<=', now());
    }
}
