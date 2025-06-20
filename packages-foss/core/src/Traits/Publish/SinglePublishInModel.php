<?php

/**
 * @deprecated Use Base classes in Entities instead.
 */

declare(strict_types=1);

namespace Moox\Core\Traits\Publish;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

trait SinglePublishInModel
{
    use SoftDeletes;

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
            'deleted' => 'Deleted',
        ];
    }

    public function getStatusAttribute(): string
    {
        /** @phpstan-ignore-next-line */
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return 'deleted';
        }

        return $this->getAttribute('publish_at')
            ? ($this->getAttribute('publish_at')->isFuture()
                ? 'scheduled'
                : 'published')
            : 'draft';
    }

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
