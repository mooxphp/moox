<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;

trait Backup
{
    private function backup(): void
    {
        $source = $this->composerJsonPath;
        $backupFile = 'composer.json-original';

        if (file_exists($source)) {
            copy($source, $backupFile);
            info('Backed up composer.json to composer.json-original');
        } else {
            $this->errorMessage = 'composer.json not found!';
        }
    }
}
