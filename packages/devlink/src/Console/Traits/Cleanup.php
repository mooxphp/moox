<?php

namespace Moox\Devlink\Console\Traits;

trait Cleanup
{
    /**
     * Remove the packages directory if it is empty.
     */
    private function cleanup(): void
    {
        if (is_dir($this->packagesPath) && count(scandir($this->packagesPath)) === 2) {
            rmdir($this->packagesPath);
        }
    }
}
