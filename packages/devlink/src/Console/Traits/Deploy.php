<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Deploy
{
    private function deploy(): void
    {
        $this->removeSymlinks();
        $this->cleanupDirectory();
        $this->moveComposerFiles();
    }

    private function removeSymlinks(): void
    {
        if (is_dir($this->packagesPath)) {
            $i = 0;
            foreach (scandir($this->packagesPath) as $item) {
                if ($item !== '.' && $item !== '..' && is_link("$this->packagesPath/$item")) {
                    unlink("$this->packagesPath/$item");
                    $i++;
                    info("Removed $item");
                }
            }

            info("Removed $i symlinks");
        }
    }

    private function cleanupDirectory(): void
    {
        if (is_dir($this->packagesPath) && count(scandir($this->packagesPath)) === 2) {
            info('Removing packages directory...');
            rmdir($this->packagesPath);
        } else {
            info('Packages directory is not empty, skipping cleanup.');
        }
    }

    private function moveComposerFiles(): void
    {
        $linked = $this->composerJsonPath.'-linked';
        $deploy = $this->composerJsonPath.'-deploy';

        if (! file_exists($linked) && file_exists($deploy)) {
            info('Project is already in deployment state.');

            return;
        }

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
