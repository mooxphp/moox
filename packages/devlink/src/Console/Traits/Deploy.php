<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

trait Deploy
{
    private function deploy(): void
    {
        $source = $this->composerJsonPath.'-deploy';
        $destination = $this->composerJsonPath;
        $backup = $this->composerJsonPath.'-backup';

        if (file_exists($source)) {
            unlink($backup);
            copy($destination, $backup);
            unlink($destination);
            copy($source, $destination);
            info('Deployed composer.json to composer.json-deploy');
        } else {
            $this->errorMessage = 'composer.json-deploy not found!';
            error($this->errorMessage);
        }

        $devlinkStatus = 'deployed';
    }
}
