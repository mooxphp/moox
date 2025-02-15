<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;

trait Unlink
{
    /**
     * Remove all symlinks in the packages directory.
     */
    private function unlink(): void
    {
        if (is_dir($this->packagesPath)) {
            $i = 0;
            foreach (scandir($this->packagesPath) as $item) {
                if ($item !== '.' && $item !== '..' && is_link("$this->packagesPath/$item")) {
                    unlink("$this->packagesPath/$item");
                    $i++;
                }
            }

            info("Removed $i symlinks");
        }
    }
}
