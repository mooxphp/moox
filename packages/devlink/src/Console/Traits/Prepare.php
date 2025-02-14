<?php

namespace Moox\Devlink\Console\Traits;

trait Prepare
{
    /**
     * Create the packages directory if it doesn't exist.
     */
    private function prepare(): void
    {
        if (! is_dir($this->packagesPath)) {
            mkdir($this->packagesPath, 0755, true);
        }
    }
}
