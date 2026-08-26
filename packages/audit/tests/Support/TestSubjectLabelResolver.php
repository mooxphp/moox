<?php

declare(strict_types=1);

namespace Moox\Audit\Tests\Support;

use Moox\Audit\Contracts\ActivitySubjectLabelResolver;
use Moox\Audit\Models\Activity;

final class TestSubjectLabelResolver implements ActivitySubjectLabelResolver
{
    public function resolve(Activity $activity): ?string
    {
        return 'Custom subject label';
    }
}
