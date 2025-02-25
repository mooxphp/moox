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
        $this->createDeployComposerJson();
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

    private function createDeployComposerJson(): void
    {
        if (! file_exists($this->composerJsonPath)) {
            $this->error('composer.json not found!');

            return;
        }

        $composerContent = file_get_contents($this->composerJsonPath);
        $composerJson = json_decode($composerContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid composer.json format: '.json_last_error_msg());

            return;
        }

        $deployJson = $composerJson;
        $repositories = $deployJson['repositories'] ?? [];
        $filteredRepos = [];

        foreach ($repositories as $repo) {
            if (($repo['type'] ?? '') !== 'path') {
                $filteredRepos[] = $repo;
            }
        }

        if (empty($filteredRepos)) {
            unset($deployJson['repositories']);
        } else {
            $deployJson['repositories'] = $filteredRepos;
        }

        $deployPath = dirname($this->composerJsonPath).'/composer.json-deploy';
        file_put_contents(
            $deployPath,
            json_encode($deployJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        info('Created composer.json-deploy with non-path repositories');
    }
}
