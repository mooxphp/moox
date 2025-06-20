<?php

use Symplify\MonorepoBuilder\Config\MBConfig;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__ . '/packages/*',
    ]);
    $mbConfig->defaultBranch('main');
};