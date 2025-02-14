<?php

namespace Moox\Devlink\Console\Traits;

trait Backup
{
    private function backup(): void
    {
        $source = $this->composerJsonPath;
        $backupFile = 'composer.json-original';

        if (file_exists($source)) {
            unlink($backupFile);
            copy($source, $backupFile);
            $this->info('Backed up composer.json to composer.json-original');
        } else {
            $this->error('composer.json not found!');
        }
    }
}
