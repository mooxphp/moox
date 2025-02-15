<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Cleanup
{
    /**
     * Remove the packages directory if it is empty.
     */
    private function cleanup(): void
    {
        if (is_dir($this->packagesPath) && count(scandir($this->packagesPath)) === 2) {
            info('Removing packages directory...');
            rmdir($this->packagesPath);
        } else {
            $this->errorMessage = 'Packages directory not found!';
            error($this->errorMessage);
        }
    }
}
