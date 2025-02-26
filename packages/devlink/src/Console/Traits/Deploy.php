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
        $this->moveComposerFiles();
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
        }
    }

    private function moveComposerFiles(): void
    {
        $linked = $this->composerJsonPath.'-linked';
        $deploy = $this->composerJsonPath.'-deploy';

        if (! file_exists($linked)) {
            $this->errorMessage = 'composer.json-linked not found!';
            error($this->errorMessage);

            return;
        }

        rename($this->composerJsonPath, $deploy);
        rename($linked, $this->composerJsonPath);
        info('Moved composer.json to composer.json-deploy');
        info('Moved composer.json-linked to composer.json');
    }
}
