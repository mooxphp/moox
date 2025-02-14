<?php

namespace Moox\Devlink\Console\Traits;

trait Restore
{
    private function restore(): void
    {
        $source = $this->composerJsonPath;
        $destination = $this->composerJsonPath.'-original';

        if (file_exists($source)) {
            unlink($source);
            rename($destination, $source);
            $this->info('Restored composer.json from composer.json-original');
        } else {
            $this->error('composer.json-original not found!');
        }
    }
}
