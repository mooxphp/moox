<?php

declare(strict_types=1);

namespace Moox\Transform\Contracts;

use Illuminate\Support\Collection;
use Moox\Transform\Models\TransformDefinition;

interface HandlesScheduledTransformDefinitions
{
    /**
     * Claim due definitions this handler runs itself.
     *
     * @param  Collection<int, TransformDefinition>  $due
     * @return Collection<int, TransformDefinition>
     */
    public function claim(Collection $due): Collection;
}
