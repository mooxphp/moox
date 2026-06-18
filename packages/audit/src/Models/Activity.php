<?php

declare(strict_types=1);

namespace Moox\Audit\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    protected $attributes = [
        'entry_type' => 'audit',
    ];
}
