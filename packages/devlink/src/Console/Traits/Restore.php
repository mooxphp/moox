<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Restore
{
    private function restore(): void
    {
        $source = $this->composerJsonPath;
        $destination = $this->composerJsonPath.'-original';

        if (file_exists($source)) {
            if (file_exists($destination)) {
                unlink($source);
                rename($destination, $source);
                info('Restored composer.json from composer.json-original');
            } else {
                $this->errorMessage = 'composer.json-original not found!';
                error($this->errorMessage);
            }
        } else {
            $this->errorMessage = 'composer.json not found!';
            error($this->errorMessage);
        }
    }
}
