<?php

declare(strict_types=1);

namespace Moox\Devlink\Console\Traits;

use Moox\Devlink\Support\EffectivePackages;

trait UsesEffectivePackages
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function effectivePackages(): array
    {
        return EffectivePackages::resolve(base_path(), config('devlink.packages', []));
    }

    protected function isEffectivelyActive(string $slug): bool
    {
        return isset($this->effectivePackages()[$slug]);
    }
}
