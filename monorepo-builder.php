<?php

return static function (Symplify\MonorepoBuilder\ValueObject\Configuration $config): void {
    $config->packages([
        __DIR__ . '/packages/*',
    ]);
    $config->defaultBranch('main');
};