<?php

declare(strict_types=1);

namespace Moox\Audit\Contracts;

use Moox\Audit\Models\Activity;

interface ActivitySubjectLabelResolver
{
    /**
     * Return the full human-readable subject label for the activity, or null to
     * fall back to the default type + title presentation.
     */
    public function resolve(Activity $activity): ?string;
}
