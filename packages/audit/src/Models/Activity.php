<?php

declare(strict_types=1);

namespace Moox\Audit\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $attributes = [
        'entry_type' => 'audit',
    ];

    /**
     * Soft-deleted subjects stay resolvable so the audit UI can still link
     * to the Filament edit/view page when the record exists in trash.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
