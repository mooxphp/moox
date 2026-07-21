<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$packageRoot = dirname(__DIR__);
$repoRoot = dirname($packageRoot, 2);

$autoload = require $repoRoot.'/vendor/autoload.php';

if ($autoload instanceof ClassLoader) {
    $autoload->addPsr4('Moox\\VeraPdf\\', $packageRoot.'/src/');
    $autoload->addPsr4('Moox\\VeraPdf\\Tests\\', $packageRoot.'/tests/');
}

require __DIR__.'/Pest.php';

return $autoload;
