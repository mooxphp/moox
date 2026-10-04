<?php

declare(strict_types=1);

namespace Moox\Definition\Laravel;

use Illuminate\Database\Schema\Blueprint;
use Moox\Definition\Resolution\ResolvedEntity;

interface FeatureMigration
{
    public function apply(Blueprint $table, ResolvedEntity $entity): void;
}
