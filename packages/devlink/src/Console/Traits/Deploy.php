<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Deploy
{
    private function deploy(): void
    {
        $this->unlink();
        $this->cleanup();
        $this->restore();
    }

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

    /**
     * Restore the composer.json file from the backup.
     */
    private function restore(): void
    {
        $source = $this->composerJsonPath.'-deploy';
        $destination = $this->composerJsonPath;

        if (file_exists($source)) {
            unlink($destination);
            copy($source, $destination);
            info('Restored composer.json from composer.json-deploy');
        } else {
            $this->errorMessage = 'composer.json-deploy not found!';
            error($this->errorMessage);
        }
    }
}
