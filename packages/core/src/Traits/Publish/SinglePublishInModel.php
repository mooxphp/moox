<?php

declare(strict_types=1);

namespace Moox\Core\Traits\Publish;

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
        if (method_exists($this, 'trashed') && $this->trashed()) {
            return 'deleted';
        }

        return $this->getAttribute('publish_at')
            ? ($this->getAttribute('publish_at')->isFuture()
                ? 'scheduled'
                : 'published')
            : 'draft';
    }
}
